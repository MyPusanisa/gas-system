<?php

namespace WPTableBuilder\Admin\Api;

use DOMDocument;
use WP_REST_Request;
use WPTableBuilder\Admin\Authorization;
use ZipArchive;

class Export
{

    private const EXPORT_TYPES = [
        'xml' => 'xml',
        'csv' => 'csv',
    ];


    public static function register(string $apiBase): void
    {

        register_rest_route($apiBase, '/export', [
            'methods' => 'GET',
            'callback' => [self::class, 'export_tables'],
            'permission_callback' => [Authorization::class, 'can_view'],
            'args' => [
                'ids' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Comma-separated table IDs to export',
                ],
                'type' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['xml', 'csv'],
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Export format (xml or csv)',
                ],
            ],
        ]);
    }


    public static function export_tables(WP_REST_Request $request)
    {
        $ids_param = $request->get_param('ids');
        /** @var string $export_type */
        $export_type = $request->get_param('type');

        if (!isset(self::EXPORT_TYPES[$export_type])) {
            return ApiHandler::response(['message' => __('Invalid export type', 'wp-table-builder')], 400);
        }

        $table_ids = array_filter(array_map('absint', explode(',', $ids_param)));

        if (empty($table_ids)) {
            return ApiHandler::response(['message' => __('No valid table IDs provided', 'wp-table-builder')], 400);
        }

        $file_extension = self::EXPORT_TYPES[$export_type];

        if (count($table_ids) > 1) {
            return self::serve_zip_archive($table_ids, $file_extension);
        }

        return self::serve_single_file($table_ids[0], $file_extension);
    }

    private static function serve_single_file(int $table_id, string $file_extension): void
    {
        $content = self::prepare_table_content($file_extension, $table_id);
        $filename = self::prepare_file_name($file_extension, $table_id);

        self::send_file_headers($filename);
        echo $content;
        exit;
    }

    private static function serve_zip_archive(array $table_ids, string $file_extension)
    {
        if (!class_exists('ZipArchive')) {
            return ApiHandler::response(
                ['message' => __('Your server does not support ZIP archives', 'wp-table-builder')],
                500
            );
        }

        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/wptb-temp';

        if (!wp_mkdir_p($temp_dir)) {
            return ApiHandler::response(
                ['message' => __('Failed to create temporary directory', 'wp-table-builder')],
                500
            );
        }

        $temp_file = tempnam($temp_dir, 'wptb_export_');

        if ($temp_file === false) {
            return ApiHandler::response(
                ['message' => __('Failed to create temporary file', 'wp-table-builder')],
                500
            );
        }

        $zip = new ZipArchive();

        if ($zip->open($temp_file, ZipArchive::OVERWRITE) !== true) {
            wp_delete_file($temp_file);
            return ApiHandler::response(
                ['message' => __('Failed to create ZIP archive', 'wp-table-builder')],
                500
            );
        }

        foreach ($table_ids as $id) {
            $content = self::prepare_table_content($file_extension, $id);
            $zip->addFromString(self::prepare_file_name($file_extension, $id), $content);
        }

        $zip->close();

        $zip_content = file_get_contents($temp_file);
        wp_delete_file($temp_file);

        $filename = self::prepare_file_name('zip');

        self::send_file_headers($filename);
        echo $zip_content;
        exit;
    }

    private static function prepare_table_content(string $file_extension, int $table_id)
    {
        if ($file_extension === 'xml') {
            return self::prepare_xml_table($table_id);
        }

        if ($file_extension === 'csv') {
            return self::prepare_csv_table($table_id);
        }

        return '';
    }

    private static function send_file_headers(string $filename): void
    {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    private static function prepare_file_name(string $extension, int $id = -1): string
    {
        if ($id < 0) {
            $file_base = (string) time();
        } else {
            $title = get_the_title($id);
            $file_base = $title !== '' ? sanitize_file_name($title) : "Table{$id}";
        }

        return "{$file_base}.{$extension}";
    }

    private static function prepare_xml_table(int $id)
    {
        $content = get_post_meta($id, '_wptb_content_', true);

        if (empty($content)) {
            return '';
        }

        $dom_handler = new DOMDocument();
        $encoded_content = self::encode_html_content($content);

        libxml_use_internal_errors(true);
        $status = $dom_handler->loadHTML(
            $encoded_content,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );
        libxml_clear_errors();

        if (!$status) {
            return '';
        }

        $tables = $dom_handler->getElementsByTagName('table');
        $table = $tables->item(0);

        if ($table === null) {
            return '';
        }

        $post = get_post($id);
        $table->setAttribute('data-wptb-table-title', $post->post_title ?? '');

        $plugin_data = get_plugin_data(WPTB_PLUGIN_FILE);
        $table->setAttribute('data-wptb-export-version', $plugin_data['Version'] ?? '');

        return $dom_handler->saveHTML($table);
    }

    private static function prepare_csv_table(int $id): string
    {
        $table_html_content = get_post_meta($id, '_wptb_content_', true);

        if (empty($table_html_content)) {
            return '';
        }

        $table_html_content = self::encode_html_content($table_html_content);

        $dom_table = new DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);
        $dom_table->loadHTML($table_html_content);
        libxml_clear_errors();

        $table_body = $dom_table->getElementsByTagName('tbody')->item(0);

        if ($table_body === null) {
            return '';
        }

        $rows = $table_body->getElementsByTagName('tr');
        $row_data = self::extract_row_data($rows);

        return self::generate_csv($row_data);
    }

    private static function encode_html_content(string $content): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $content . '</body></html>';
    }

    private static function extract_row_data(\DOMNodeList $rows): array
    {
        $row_data = [];

        foreach ($rows as $index => $row) {
            /** @var \DOMElement $row */
            $cells = $row->getElementsByTagName('td');

            foreach ($cells as $cell) {
                $row_data[$index][] = $cell->nodeValue;
            }
        }

        return $row_data;
    }

    private static function generate_csv(array $row_data): string
    {
        ob_start();
        $file = fopen('php://output', 'w');

        if ($file === false) {
            ob_end_clean();
            return '';
        }

        foreach ($row_data as $row) {
            fputcsv($file, $row, ',', '"', '\\');
        }

        fclose($file);

        $csv = ob_get_clean();

        return $csv !== false ? $csv : '';
    }
}

