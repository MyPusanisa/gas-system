<?php

namespace WPTableBuilder\Admin\Api;
use WPTableBuilder\Admin\Authorization;
use WPTableBuilder\Blocks\LegacySaver\Table;
use WPTableBuilder\Core\Settings;
use WPTableBuilder\Core\Cpt;

class TablePost
{
    public static function register($apiBase)
    {
        register_rest_route($apiBase, '/trash', [
            'methods' => 'POST',
            'callback' => [self::class, 'trash_table'],
            'permission_callback' => [Authorization::class, 'can_edit'],
        ]);

        register_rest_route($apiBase, '/delete', [
            'methods' => 'POST',
            'callback' => [self::class, 'delete_permanently'],
            'permission_callback' => [Authorization::class, 'can_edit'],
        ]);

        register_rest_route($apiBase, '/restore', [
            'methods' => 'POST',
            'callback' => [self::class, 'restore_table'],
            'permission_callback' => [Authorization::class, 'can_edit'],
        ]);

        register_rest_route($apiBase, '/duplicate', [
            'methods' => 'POST',
            'callback' => [self::class, 'duplicate_table'],
            'permission_callback' => [Authorization::class, 'can_edit'],
        ]);

        register_rest_route($apiBase, '/trash_bulk', [
            'methods' => 'POST',
            'callback' => [self::class, 'trash_table_bulk'],
            'permission_callback' => [Authorization::class, 'can_edit'],
        ]);

        register_rest_route($apiBase, '/restore_bulk', [
            'methods' => 'POST',
            'callback' => [self::class, 'restore_table_bulk'],
            'permission_callback' => [Authorization::class, 'can_edit'],
        ]);

        register_rest_route($apiBase, '/save', [
            'methods' => 'POST',
            'callback' => [self::class, 'save_table'],
            'permission_callback' => '__return_true', // Handled in the method
        ]);
    }

    public static function save_table($request)
    {
        $data = $request->get_json_params();

        if (!isset($data['content'])) {
            return ApiHandler::response(['message' => 'Table content is required.'], 400);
        }

        $id = absint($data['id'] ?? 0);
        $content = $data['content'];

        if (!Settings::is_user_allowed($id)) {
            return ApiHandler::response(['message' => 'You are not allowed to save this table.'], 403);
        }

        try {
            $content = Table::render(json_decode($content, true), $id ?? 'startedid-0');
        } catch (\Exception $e) {
            return ApiHandler::response(['message' => $e->getMessage()], 500);
        }

        $preview_id = null;
        if (isset($data['preview_id']) && $data['preview_id']) {
            $preview_id = $data['preview_id'];
        }

        $title = '';
        if (isset($data['title']) && !empty($data['title'])) {
            $title = sanitize_text_field($data['title']);
        }

        $message = 'Table saved successfully.';
        $is_new = false;

        if (!$id) {
            $id = wp_insert_post([
                'post_title' => $title,
                'post_content' => '',
                'post_type' => Cpt::POST_TYPE,
                'post_status' => 'draft'
            ]);

            update_post_meta($id, '_wptb_content_', $content);

            $message = 'Table created successfully.';
            $is_new = true;
        } else {
            wp_update_post([
                'ID' => $id,
                'post_title' => $title,
                'post_content' => '',
                'post_type' => Cpt::POST_TYPE,
                'post_status' => 'draft'
            ]);
        }


        if (!$preview_id) {
            update_post_meta($id, '_wptb_content_', $content);
        } else {
            update_post_meta($id, '_wptb_content_preview_', $content);
            update_post_meta($id, '_wptb_preview_id_', $preview_id);
        }

        if (isset($data['prebuilt']) && $data['prebuilt']) {
            update_post_meta($id, '_wptb_prebuilt_', 1);
        } else {
            delete_post_meta($id, '_wptb_prebuilt_');
        }

        self::purge_caches($id);

        return ApiHandler::response(['message' => $message, 'id' => $id, 'is_new' => $is_new, 'preview_id' => $preview_id]);
    }
    private static function purge_caches(int $id): void
    {
        clean_post_cache($id);

        // LiteSpeed Cache
        do_action('litespeed_purge_all');

        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        // WP Fastest Cache
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache();
        }

        // SG Optimizer
        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
        }

        // Autoptimize
        if (function_exists('autoptimize_flush_pagecache')) {
            autoptimize_flush_pagecache();
        }

        // Generic hook used by some caching plugins
        do_action('wptb_after_table_save', $id);
    }

    private static function duplicate_table_internal($id)
    {
        $post = get_post($id);
        if (!$post) {
            return 404;
        }

        $id_new = wp_insert_post([
            'post_title' => sanitize_text_field($post->post_title),
            'post_content' => '',
            'post_type' => Cpt::POST_TYPE,
            'post_status' => 'draft'
        ]);
        $table = get_post_meta($id, '_wptb_content_', true);

        $table_new = add_post_meta($id_new, '_wptb_content_', $table);

        if ($id_new && $table_new) {
            wp_update_post([
                'ID' => $id_new,
                'post_title' => str_replace(' (ID #' . $id . ')', '', get_the_title($id_new) . ' (ID #' . $id_new . ')'),
                'post_content' => '',
                'post_type' => Cpt::POST_TYPE,
                'post_status' => 'draft'
            ]);

            return
                [
                    'id' => $id_new,
                    'title' => get_the_title($id_new),
                    'date' => get_the_date('h:i A, d M, Y'),
                    'modified' => get_the_modified_date('h:i A, d M, Y'),
                ]
            ;
        }

        return 500;
    }
    public static function duplicate_table($request)
    {
        $data = $request->get_json_params();
        if (!isset($data['id'])) {
            return ApiHandler::response(['message' => 'Table ID is required.'], 400);
        }

        $id = absint($data['id']);

        $duplicated = self::duplicate_table_internal($id);
        if ($duplicated) {
            return [
                'message' => 'Table duplicated successfully.',
                'posts' => [
                    $duplicated
                ]
            ];
        }

        return ApiHandler::response(['message' => 'Failed to duplicate table.'], 500);
    }

    public static function trash_table($req)
    {
        $id = absint($req->get_json_params()['id'] ?? 0);
        if (get_post_type($id) === Cpt::POST_TYPE) {
            wp_trash_post($id);
            return [
                'message' => 'Table trashed successfully.',
            ];
        }
        return ApiHandler::response(['message' => 'Table not found.'], 404);
    }
    public static function restore_table($req)
    {
        $id = absint($req->get_json_params()['id'] ?? 0);
        if (get_post_type($id) === Cpt::POST_TYPE) {
            wp_untrash_post($id);
            return [
                'message' => 'Table restored successfully.',
            ];
        }
        return ApiHandler::response(['message' => 'Table not found.'], 404);
    }

    public static function delete_permanently($req)
    {
        $id = absint($req->get_json_params()['id'] ?? 0);
        if (get_post_type($id) === Cpt::POST_TYPE) {
            wp_delete_post($id);
            return [
                'message' => 'Table deleted permanently.',
            ];
        }
        return ApiHandler::response(['message' => 'Table not found.'], 404);
    }

    public static function duplicate_table_bulk($request)
    {
        global $wpdb;
        $json = $request->get_json_params();
        $post_ids = $json['ids'] ?? [];
        $wpdb->query('START TRANSACTION');
        $posts = [];
        foreach ($post_ids as $post_id) {
            $dup = self::duplicate_table_internal($post_id);
            if (!is_array($dup)) {
                $wpdb->query('ROLLBACK');
                return ApiHandler::response(['message' => 'Failed to duplicate table.'], $dup);
            }
            $posts[] = $dup;
        }
        $wpdb->query('COMMIT');
        return ApiHandler::response([
            'message' => 'Table(s) duplicated successfully.',
            'posts' => $posts
        ]);
    }

    public static function trash_table_bulk($req)
    {
        global $wpdb;
        $json = $req->get_json_params();
        $post_ids = $json['ids'] ?? [];
        $wpdb->query('START TRANSACTION');
        foreach ($post_ids as $id) {
            if (!get_post_type($id) === Cpt::POST_TYPE) {
                $wpdb->query('ROLLBACK');
                return ApiHandler::response(['message' => 'Failed to trash table(s).']);
            }
            wp_trash_post($id);
        }
        $wpdb->query('COMMIT');
        return ApiHandler::response([
            'message' => 'Table(s) trashed successfully.',
        ]);
    }

    public static function restore_table_bulk($req)
    {
        global $wpdb;
        $json = $req->get_json_params();
        $post_ids = $json['ids'] ?? [];
        $wpdb->query('START TRANSACTION');
        foreach ($post_ids as $id) {
            if (!get_post_type($id) === Cpt::POST_TYPE) {
                $wpdb->query('ROLLBACK');
                return ApiHandler::response(['message' => 'Failed to restore table(s).']);
            }
            wp_untrash_post($id);
        }
        $wpdb->query('COMMIT');
        return ApiHandler::response([
            'message' => 'Table(s) restored successfully.',
        ]);
    }
}