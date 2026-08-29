<?php
/**
 * UAGB Analytics Event Tracker.
 *
 * Registers hooks and detects state-based milestone events
 * for the BSF Analytics event tracking system.
 *
 * @since 2.19.22
 * @package UAGB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UAGB_Analytics_Event_Tracker' ) ) {

	/**
	 * Class UAGB_Analytics_Event_Tracker
	 *
	 * @since 2.19.22
	 */
	class UAGB_Analytics_Event_Tracker {

		/**
		 * Instance.
		 *
		 * @var UAGB_Analytics_Event_Tracker|null
		 */
		private static $instance = null;

		/**
		 * Get instance.
		 *
		 * @since 2.19.22
		 * @return UAGB_Analytics_Event_Tracker
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since 2.19.22
		 */
		private function __construct() {
			require_once UAGB_DIR . 'classes/analytics/class-uagb-analytics-events.php';

			add_action( 'admin_init', array( $this, 'track_plugin_activated' ) );
			add_action( 'admin_init', array( $this, 'detect_state_events' ) );
			add_action( 'update_option_spectra_usage_optin', array( $this, 'track_analytics_optin' ), 10, 2 );
			add_action( 'save_post', array( $this, 'track_first_spectra_block_used' ), 20, 2 );
			add_action( 'wp_ajax_ast_block_templates_importer', array( $this, 'track_first_template_imported' ), 5 );
			add_action( 'wp_ajax_ast_block_templates_import_template_kit', array( $this, 'track_first_template_imported' ), 5 );
		}

		/**
		 * Track plugin activation event.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		public function track_plugin_activated() {
			$properties = array();
			$referrers  = get_option( 'bsf_product_referers', array() );

			if ( is_array( $referrers ) && ! empty( $referrers['ultimate-addons-for-gutenberg'] ) && is_string( $referrers['ultimate-addons-for-gutenberg'] ) ) {
				$properties['source'] = sanitize_text_field( $referrers['ultimate-addons-for-gutenberg'] );
			}

			UAGB_Analytics_Events::track( 'plugin_activated', UAGB_VER, $properties );
		}

		/**
		 * Track analytics opt-in/opt-out event.
		 *
		 * @since 2.19.22
		 * @param string $old_value Old value.
		 * @param string $new_value New value.
		 * @return void
		 */
		public function track_analytics_optin( $old_value, $new_value ) {
			if ( 'yes' === $new_value ) {
				UAGB_Analytics_Events::track( 'analytics_optin', 'yes' );
			}
		}

		/**
		 * Track first time a Spectra block is used in a post.
		 *
		 * @since 2.19.22
		 * @param int      $post_id Post ID.
		 * @param \WP_Post $post    Post object.
		 * @return void
		 */
		public function track_first_spectra_block_used( $post_id, $post ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return;
			}

			if ( UAGB_Analytics_Events::is_tracked( 'first_spectra_block_used' ) ) {
				return;
			}

			if ( empty( $post->post_content ) ) {
				return;
			}

			// Check for any Spectra block (uagb/ or spectra/ namespace).
			if ( ! preg_match( '/<!-- wp:(uagb|spectra)\/(\S+)/', $post->post_content, $matches ) ) {
				return;
			}

			$block_slug = $matches[1] . '/' . $matches[2];

			UAGB_Analytics_Events::track(
				'first_spectra_block_used',
				$block_slug,
				array( 'post_type' => get_post_type( $post_id ) )
			);
		}

		/**
		 * Detect state-based events on admin load.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		public function detect_state_events() {
			$this->detect_spectra_pro_activated();
			$this->detect_ai_assistant_first_use();
			$this->detect_gbs_first_created();
			$this->detect_onboarding_completed();
			$this->detect_first_form_created();
			$this->detect_first_popup_created();
		}

		/**
		 * Detect if Spectra Pro is active.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		private function detect_spectra_pro_activated() {
			if ( UAGB_Analytics_Events::is_tracked( 'spectra_pro_activated' ) ) {
				return;
			}

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active( 'spectra-pro/spectra-pro.php' ) ) {
				$pro_version = defined( 'SPECTRA_PRO_VER' ) ? SPECTRA_PRO_VER : '';
				UAGB_Analytics_Events::track( 'spectra_pro_activated', $pro_version );
			}
		}

		/**
		 * Detect first use of AI assistant.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		private function detect_ai_assistant_first_use() {
			if ( UAGB_Analytics_Events::is_tracked( 'ai_assistant_first_use' ) ) {
				return;
			}

			if ( ! class_exists( '\ZipAI\Classes\Helper' ) || ! method_exists( '\ZipAI\Classes\Helper', 'is_authorized' ) ) {
				return;
			}

			if ( \ZipAI\Classes\Helper::is_authorized() ) {
				UAGB_Analytics_Events::track(
					'ai_assistant_first_use',
					'',
					array( 'module' => 'ai_assistant' )
				);
			}
		}

		/**
		 * Detect if Global Block Styles have been created.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		private function detect_gbs_first_created() {
			if ( UAGB_Analytics_Events::is_tracked( 'gbs_first_created' ) ) {
				return;
			}

			$gbs_enabled = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_gbs_extension', 'enabled' );

			if ( 'enabled' !== $gbs_enabled ) {
				return;
			}

			$gbs_fonts = get_option( 'spectra_gbs_google_fonts', array() );

			if ( ! empty( $gbs_fonts ) && is_array( $gbs_fonts ) ) {
				UAGB_Analytics_Events::track( 'gbs_first_created' );
			}
		}

		/**
		 * Detect if onboarding has been completed.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		private function detect_onboarding_completed() {
			if ( UAGB_Analytics_Events::is_tracked( 'onboarding_completed' ) ) {
				return;
			}

			$onboarding_status = get_option( 'ast-block-templates-show-onboarding', true );

			if ( 'no' === $onboarding_status ) {
				UAGB_Analytics_Events::track( 'onboarding_completed' );
			}
		}

		/**
		 * Track first template import via AJAX hook.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		public function track_first_template_imported() {
			UAGB_Analytics_Events::track( 'first_template_imported' );
		}

		/**
		 * Detect if a Spectra form block has been created.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		private function detect_first_form_created() {
			if ( UAGB_Analytics_Events::is_tracked( 'first_form_created' ) ) {
				return;
			}

			$block_stats = UAGB_Block_Stats_Processor::get_block_stats();

			if ( ! empty( $block_stats['uagb/forms'] ) && $block_stats['uagb/forms'] > 0 ) {
				UAGB_Analytics_Events::track( 'first_form_created' );
			}
		}

		/**
		 * Detect if a Spectra popup has been created.
		 *
		 * @since 2.19.22
		 * @return void
		 */
		private function detect_first_popup_created() {
			if ( UAGB_Analytics_Events::is_tracked( 'first_popup_created' ) ) {
				return;
			}

			if ( ! post_type_exists( 'spectra-popup' ) ) {
				return;
			}

			$popup_count = wp_count_posts( 'spectra-popup' );

			if ( is_object( $popup_count ) && ( $popup_count->publish > 0 || $popup_count->draft > 0 ) ) {
				UAGB_Analytics_Events::track( 'first_popup_created' );
			}
		}

	}
}
