<?php
/*
* Plugin Name: Advanced Image Hover Effect for Elementor
* Plugin URI: https://kapasias.com/advanced-image-hover-effect-for-elementor/
* Description: Advanced Image Hover Effect for Elementor Page Builder is customized hover effects for your box layout.
* Version: 1.11.22
* Author: KAP ASIAs
* Author URI: http://kapasias.com
* Text Domain: aihee
* Elementor tested up to: 4.0
* Elementor Pro tested up to: 4.0
*/

// Prevent direct access to files
if (!defined('ABSPATH')) {
    exit;
}
// Plugin version
defined( 'AIHEE_VERSION' ) or define( 'AIHEE_VERSION', '1.11.22' );
define('AIHEE_PATH', plugin_dir_path(__FILE__));
define('AIHEE_URL', plugin_dir_url(__FILE__));

// Check elementor
require_once AIHEE_PATH . 'include/aihee-plugin-check.php';

class Ka_Advanced_Image_Hover_Effect_Addon {

    private static $_instance = null;

    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }

    public function __construct() {
		add_action('plugins_loaded', [$this, 'init']);
    }
	
	//Init
    public function init() {

		if (!did_action('elementor/loaded')) {
			add_action('admin_notices', 'aihee_addon_load_fail');
			return;
        }        
		
        add_action( 'elementor/init', [ $this, 'add_elementor_category' ] );
		add_action('elementor/frontend/after_enqueue_styles', [$this, 'includes']);
		add_action('elementor/widgets/register', [$this, 'register_widgets']);
		
		add_action('admin_init', [$this, 'display_notice']);		
		add_action( 'admin_enqueue_scripts', [$this, 'admin_includes'] );
		//load_plugin_textdomain('aihee', false, dirname(plugin_basename(__FILE__)) . '/languages' );
		add_action( 'init', function() {
			load_plugin_textdomain(
				'aihee',
				false,
				basename( dirname( __FILE__ ) ) . '/languages'
			);
		});
	}
	
	// Admin dismiss notice
	public function display_notice() {

		if(isset($_GET['aihee_dismiss']) && $_GET['aihee_dismiss'] == 1) {
	        add_option('aihee_dismiss' , true);
	    }
		
		$dismiss = get_option('aihee_dismiss');
		
		if(!get_option('aihee-top-notice')){
			add_option('aihee-top-notice',strtotime(current_time('mysql')));
		}
		
		if(get_option('aihee-top-notice') && get_option('aihee-top-notice') != 0) {
			if( get_option('aihee-top-notice') < strtotime('-100 days')) {			
				add_action('admin_notices', 			array($this,'aihee_top_admin_notice'));
				add_action('wp_ajax_aihee_top_notice',	array($this,'aihee_top_notice_dismiss'));
			}
		}
	}
	
	// Admin dismiss notice
	public function aihee_top_notice_dismiss(){
		update_option('aihee-top-notice',strtotime(current_time('mysql')));
		exit();
	}
	
	// Admin notice
	public function aihee_top_admin_notice(){
		?>
			<style>.aihee-notice.notice-success{border-left-color:#d84242;padding:50px;background:linear-gradient(135deg, rgba(216, 66, 66, 0.15), rgba(66, 133, 216, 0.2), rgba(66, 216, 146, 0.15))}</style>
			<div class="aihee-notice notice notice-success is-dismissible" style="text-align:center;padding:10px 0;display:flex;align-items:center;justify-content:center;flex-direction:column;">
			<div><br><strong style="font-size:24px;"><?php echo esc_html__( 'Supercharge Your Elementor websites with Essential Classy Addons', 'aihee' ); ?></strong>
				<br/><br/><?php echo esc_html__( '🔥 Enhance your Elementor Page Builder with highly customizable Essential Classy Addons. With over 150+ widgets, you can build stunning websites faster and without any coding. Both Elementor and Essential Classy Addons take your WordPress website to the next level, offering you the tools to create creative and functional websites effortlessly. Explore the most popular elements for the Elementor Builder, complete with a Theme Builder to streamline your design process in Elementor Page Builder.', 'aihee' ); ?>
				<br/><br/><a href="https://ecaddons.com/elements/" class="button button-secondary" target="_blank" style="background:#d84242;color:#fff;border-radius:50px;outline:none;border:1px solid #d84242;">
				<?php echo esc_html__('Visit Essential Classy Addons Now','aihee'); ?></a><br/></div>
			</div>
		<?php
	}
	
	// Elementor category
	public function add_elementor_category() {
			
		$elementor = \Elementor\Plugin::$instance;
		$elementor->elements_manager->add_category('kap-asia', 
			[
				'title' => esc_html__( 'KAP ASIAs', 'aihee' ),				
			],
			1
		);	
	}
	
	// Register widget
    public function register_widgets() {
        require_once(AIHEE_PATH . 'include/aihee-widget.php');
    }
	// Js and Css
	 public function includes() {
		wp_register_style( 'aihee-css', AIHEE_URL . 'assets/css/aihee_css.min.css', array(), AIHEE_VERSION,false);

        if (isset($_GET['elementor-preview']) || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'elementor')) {
           wp_enqueue_style('aihee-css');
        }
    }
	// Admin side notice
	public function admin_includes() {
		wp_enqueue_script( 'aihee-editor-js-note', AIHEE_URL . 'assets/js/admin/aihee_script_note.js',array('jquery'), AIHEE_VERSION,true);
    }
    
}

Ka_Advanced_Image_Hover_Effect_Addon::get_instance();

?>