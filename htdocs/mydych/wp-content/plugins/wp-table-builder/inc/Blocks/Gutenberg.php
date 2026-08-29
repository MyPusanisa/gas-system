<?php

namespace WPTableBuilder\Blocks;

class Gutenberg
{

	public static function render($block, $content)
	{
		$wrapper_attributes = get_block_wrapper_attributes();
		
		if (!isset($block['id']) || !$block['id']) {
			return '<div ' . $wrapper_attributes . '>' . do_shortcode($content) . '</div>';
		}
		
		$shortcode_output = do_shortcode('[wptb id="' . $block['id'] . '"]');
		return '<div ' . $wrapper_attributes . '>' . $shortcode_output . '</div>';
	}
	public static function init()
	{
		$json = WPTB_PLUGIN_DIR . '/build/block.json';
		register_block_type_from_metadata(
			$json,
			[
				'attributes' => json_decode(file_get_contents($json), true)['attributes'],
				'render_callback' => [self::class, 'render'],
			]
		);
	}
}