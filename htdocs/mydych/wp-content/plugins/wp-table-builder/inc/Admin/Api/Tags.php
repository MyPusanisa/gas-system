<?php

namespace WPTableBuilder\Admin\Api;

use WP_REST_Request;
use WP_REST_Response;
use WPTableBuilder\Admin\Authorization;
use WPTableBuilder\Core\Cpt;

class Tags
{
    public static function register(string $apiBase): void
    {
        register_rest_route($apiBase, '/tags', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_tags'],
            'permission_callback' => [Authorization::class, 'can_edit'],
        ]);

        register_rest_route($apiBase, '/tags', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'create_tag'],
            'permission_callback' => [Authorization::class, 'can_edit'],
            'args'                => [
                'name' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'Tag name',
                ],
                'slug' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_title',
                    'description'       => 'Tag slug',
                ],
                'description' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'Tag description',
                ],
            ],
        ]);
    }

    public static function get_tags(): WP_REST_Response
    {
        $terms = get_terms([
            'taxonomy'   => Cpt::TAX_ID,
            'hide_empty' => false,
        ]);

        $tags = [];

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $tags[$term->term_id] = $term->name;
            }
        }

        return ApiHandler::response(['tags' => $tags]);
    }

    public static function create_tag(WP_REST_Request $request): WP_REST_Response
    {
        $name = $request->get_param('name');

        if (empty($name)) {
            return ApiHandler::response(
                ['message' => __('Tag name is required', 'wp-table-builder')],
                400
            );
        }

        $slug = $request->get_param('slug') ?: $name;
        $description = $request->get_param('description') ?: '';

        $result = wp_insert_term($name, Cpt::TAX_ID, [
            'slug'        => $slug,
            'description' => $description,
        ]);

        if (is_wp_error($result)) {
            return ApiHandler::response(
                ['message' => $result->get_error_message()],
                400
            );
        }

        // Get all tags to return updated list
        $terms = get_terms([
            'taxonomy'   => Cpt::TAX_ID,
            'hide_empty' => false,
        ]);

        $tags = [];

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $tags[$term->term_id] = $term->name;
            }
        }

        return ApiHandler::response([
            'message' => __('Tag created successfully', 'wp-table-builder'),
            'tags'    => $tags,
            'term_id' => $result['term_id'],
        ]);
    }
}