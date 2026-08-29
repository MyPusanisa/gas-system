<?php
namespace Elementor;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Image_Size;

// Prevent direct access to files
if (!defined('ABSPATH')) {
    exit;
}

class Advanced_Image_Hover_Effect_Kap_Asias extends Widget_Base {
	
	public function get_name() {
		return 'aihee_addon';
	}

	public function get_title() {
		return esc_html__('Advanced Image Hover Effect', 'aihee');
	}

	public function get_icon() {
		 return 'eicon-favorite';
	}
	public function get_categories() {
        return array('kap-asia');
    }
	public function get_keywords() {
		return ['image hover','advanced image hover','image effect','infobox','infobanner','button','box','skin','tags','title','heading','servicebox','image','layout','creative','html','animation','builder','custom','loop','social'];
	}
	
	public function has_widget_inner_wrapper(): bool {
		return false;
	}
	
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Advanced Image Hover Effect', 'aihee' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);	
		$this->add_control(
			'layout',
			[
				'label' => esc_html__( 'Layout', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'layout-1',
				'options' => [
					'layout-1'  => esc_html__( 'Layout 1', 'aihee' ),
					'layout-2' => esc_html__( 'Layout 2', 'aihee' ),
					'layout-3' => esc_html__( 'Layout 3', 'aihee' ),
					'layout-4' => esc_html__( 'Layout 4', 'aihee' ),
					'layout-5' => esc_html__( 'Layout 5', 'aihee' ),
					'layout-6' => esc_html__( 'Layout 6', 'aihee' ),
					'layout-7' => esc_html__( 'Layout 7', 'aihee' ),
					'layout-8' => esc_html__( 'Layout 8', 'aihee' ),
					'layout-9' => esc_html__( 'Layout 9', 'aihee' ),
					'layout-10' => esc_html__( 'Layout 10', 'aihee' ),
					'layout-11' => esc_html__( 'Layout 11', 'aihee' ),
					'layout-12' => esc_html__( 'Layout 12', 'aihee' ),
					'layout-13' => esc_html__( 'Layout 13', 'aihee' ),
					'layout-14' => esc_html__( 'Layout 14', 'aihee' ),
					'layout-15' => esc_html__( 'Layout 15', 'aihee' ),
					'layout-16' => esc_html__( 'Layout 16', 'aihee' ),
					'layout-17' => esc_html__( 'Layout 17', 'aihee' ),
					'layout-18' => esc_html__( 'Layout 18', 'aihee' ),
					'layout-19' => esc_html__( 'Layout 19', 'aihee' ),
					'layout-20' => esc_html__( 'Layout 20', 'aihee' ),
					'layout-21' => esc_html__( 'Layout 21', 'aihee' ),
					'layout-22' => esc_html__( 'Layout 22', 'aihee' ),
					'layout-23' => esc_html__( 'Layout 23', 'aihee' ),
					'layout-24' => esc_html__( 'Layout 24', 'aihee' ),
					'layout-25' => esc_html__( 'Layout 25', 'aihee' ),
					'layout-26' => esc_html__( 'Layout 26', 'aihee' ),
					'layout-27' => esc_html__( 'Layout 27 (Comming Soon)', 'aihee' ),
					'layout-28' => esc_html__( 'Layout 28 (Comming Soon)', 'aihee' ),
					'layout-29' => esc_html__( 'Layout 29 (Comming Soon)', 'aihee' ),
					'layout-30' => esc_html__( 'Layout 30 (Comming Soon)', 'aihee' ),
					'layout-31' => esc_html__( 'Layout 31 (Comming Soon)', 'aihee' ),
					'layout-32' => esc_html__( 'Layout 32 (Comming Soon)', 'aihee' ),
					'layout-33' => esc_html__( 'Layout 33 (Comming Soon)', 'aihee' ),
					'layout-34' => esc_html__( 'Layout 34 (Comming Soon)', 'aihee' ),
					'layout-35' => esc_html__( 'Layout 35 (Comming Soon)', 'aihee' ),
					'layout-36' => esc_html__( 'Layout 36 (Comming Soon)', 'aihee' ),
					'layout-37' => esc_html__( 'Layout 37 (Comming Soon)', 'aihee' ),
					'layout-38' => esc_html__( 'Layout 38 (Comming Soon)', 'aihee' ),
					'layout-39' => esc_html__( 'Layout 39 (Comming Soon)', 'aihee' ),
					'layout-40' => esc_html__( 'Layout 40 (Comming Soon)', 'aihee' ),
					'layout-41' => esc_html__( 'Layout 41 (Comming Soon)', 'aihee' ),
					'layout-42' => esc_html__( 'Layout 42 (Comming Soon)', 'aihee' ),
					'layout-43' => esc_html__( 'Layout 43 (Comming Soon)', 'aihee' ),
					'layout-44' => esc_html__( 'Layout 44 (Comming Soon)', 'aihee' ),
					'layout-45' => esc_html__( 'Layout 45 (Comming Soon)', 'aihee' ),
					'layout-46' => esc_html__( 'Layout 46 (Comming Soon)', 'aihee' ),
					'layout-47' => esc_html__( 'Layout 47 (Comming Soon)', 'aihee' ),
					'layout-48' => esc_html__( 'Layout 48 (Comming Soon)', 'aihee' ),
					'layout-49' => esc_html__( 'Layout 49 (Comming Soon)', 'aihee' ),
					'layout-50' => esc_html__( 'Layout 50 (Comming Soon)', 'aihee' ),
					'layout-51' => esc_html__( 'Layout 51 (Comming Soon)', 'aihee' ),
					'layout-52' => esc_html__( 'Layout 52 (Comming Soon)', 'aihee' ),
					'layout-53' => esc_html__( 'Layout 53 (Comming Soon)', 'aihee' ),
					'layout-54' => esc_html__( 'Layout 54 (Comming Soon)', 'aihee' ),
					'layout-55' => esc_html__( 'Layout 55 (Comming Soon)', 'aihee' ),
					'layout-56' => esc_html__( 'Layout 56 (Comming Soon)', 'aihee' ),
					'layout-57' => esc_html__( 'Layout 57 (Comming Soon)', 'aihee' ),
					'layout-58' => esc_html__( 'Layout 58 (Comming Soon)', 'aihee' ),
					'layout-59' => esc_html__( 'Layout 59 (Comming Soon)', 'aihee' ),
					'layout-60' => esc_html__( 'Layout 60 (Comming Soon)', 'aihee' ),
					'layout-61' => esc_html__( 'Layout 61 (Comming Soon)', 'aihee' ),
					'layout-62' => esc_html__( 'Layout 62 (Comming Soon)', 'aihee' ),
					'layout-63' => esc_html__( 'Layout 63 (Comming Soon)', 'aihee' ),
					'layout-64' => esc_html__( 'Layout 64 (Comming Soon)', 'aihee' ),
					'layout-65' => esc_html__( 'Layout 65 (Comming Soon)', 'aihee' ),
					'layout-66' => esc_html__( 'Layout 66 (Comming Soon)', 'aihee' ),
					'layout-67' => esc_html__( 'Layout 67 (Comming Soon)', 'aihee' ),
					'layout-68' => esc_html__( 'Layout 68 (Comming Soon)', 'aihee' ),
					'layout-69' => esc_html__( 'Layout 69 (Comming Soon)', 'aihee' ),
					'layout-70' => esc_html__( 'Layout 70 (Comming Soon)', 'aihee' ),
				],
			]
		);
		$this->add_control(
			'aihee_image',
			[
				'label' => esc_html__( 'Image', 'aihee' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
				'separator'	=> 'before',
			]
		);
		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name' => 'thumbnail',
				'default' => 'full',
				'separator' => 'none',
			]
		);
		$this->add_control(
			'heading_title',
			[
				'label' => esc_html__( 'Title', 'aihee' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true,],
				'default' => esc_html__( 'Image Hover Effect', 'aihee' ),
				'placeholder' => esc_html__( 'Enter Title', 'aihee' ),
				'separator' => 'before',
			]
		);		
		$this->add_control(
			'heading_title_tag',
			[
				'label' => esc_html__( 'Title Tag', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'h1',
				'options' => [
					'h1'  => esc_html__( 'H1', 'aihee' ),
					'h2' => esc_html__( 'H2', 'aihee' ),
					'h3' => esc_html__( 'H3', 'aihee' ),
					'h4' => esc_html__( 'H4', 'aihee' ),
					'h5' => esc_html__( 'H5', 'aihee' ),
					'h6' => esc_html__( 'H6', 'aihee' ),
				],
			]
		);
		$this->add_control(
			'description_text',
			[
				'label' => esc_html__( 'Description', 'aihee' ),
				'type' => Controls_Manager::WYSIWYG,
				'default' => esc_html__( 'I am text block.', 'aihee' ),
				'dynamic' => ['active' => true,],
			]
		);
		$this->add_control(
			'button_heading',
			[
				'label' => esc_html__( 'Button', 'aihee' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_control(
			'button_type',
			[
				'label' => esc_html__( 'Type', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'icon',
				'options' => [
					'icon'  => esc_html__( 'Icon', 'aihee' ),
					'text' => esc_html__( 'Text', 'aihee' ),
				],
			]
		);
		$this->add_control(
			'button_icon_1',
			[
				'label' => esc_html__( 'First Icon', 'aihee' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fab fa-facebook-square',
					'library' => 'solid',
				],
				'condition' => [
					'button_type' => 'icon',
				],	
			]
		);
		$this->add_control(
			'button_text_1',
			[
				'label' => esc_html__( 'First Text', 'aihee' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true,],
				'default' => esc_html__( 'A', 'aihee' ),
				'placeholder' => esc_html__( 'Enter First Text', 'aihee' ),
				'condition' => [
					'button_type' => 'text',
				],
			]
		);
		$this->add_control(
			'button_url_1',
			[
				'label' => esc_html__( 'First Button URL', 'aihee' ),
				'type' => Controls_Manager::URL,				
				'placeholder' => esc_html__( 'https://www.demo-link.com', 'aihee' ),
				'dynamic' => ['active' => true,],
				'default' => ['url' => '#',],
				'show_external' => true,
			]
		);
		$this->add_control(
			'button_icon_2',
			[
				'label' => esc_html__( 'Second Icon', 'aihee' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fab fa-twitter-square',
					'library' => 'solid',
				],
				'separator' => 'before',
				'condition' => [
					'button_type' => 'icon',
				],	
			]
		);
		$this->add_control(
			'button_text_2',
			[
				'label' => esc_html__( 'Second Text', 'aihee' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true,],
				'default' => esc_html__( 'B', 'aihee' ),
				'placeholder' => esc_html__( 'Enter Second Text', 'aihee' ),
				'separator' => 'before',
				'condition' => [
					'button_type' => 'text',
				],
			]
		);
		$this->add_control(
			'button_url_2',
			[
				'label' => esc_html__( 'Second Button URL', 'aihee' ),
				'type' => Controls_Manager::URL,				
				'placeholder' => esc_html__( 'https://www.demo-link.com', 'aihee' ),
				'dynamic' => ['active' => true,],
				'default' => ['url' => '#',],
				'show_external' => true,
			]
		);
		$this->add_control(
			'button_icon_3',
			[
				'label' => esc_html__( 'Third Icon', 'aihee' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fab fa-linkedin',
					'library' => 'solid',
				],
				'separator' => 'before',
				'condition' => [
					'button_type' => 'icon',
				],	
			]
		);
		$this->add_control(
			'button_text_3',
			[
				'label' => esc_html__( 'Third Text', 'aihee' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true,],
				'default' => esc_html__( 'C', 'aihee' ),
				'placeholder' => esc_html__( 'Enter Third Text', 'aihee' ),
				'separator' => 'before',
				'condition' => [
					'button_type' => 'text',
				],
			]
		);
		$this->add_control(
			'button_url_3',
			[
				'label' => esc_html__( 'Third Button URL', 'aihee' ),
				'type' => Controls_Manager::URL,				
				'placeholder' => esc_html__( 'https://www.demo-link.com', 'aihee' ),
				'dynamic' => ['active' => true,],
				'default' => ['url' => '#',],
				'show_external' => true,
			]
		);	
		$this->end_controls_section();
		
		/*style start*/
		/*title style start*/
		$this->start_controls_section(
            'title_styling',
            [
                'label' => esc_html__('Title Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,				
            ]
        );
		$this->add_responsive_control(
			'title_padding',
			[
				'label' => esc_html__( 'Padding', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'title_margin',
			[
				'label' => esc_html__( 'Margin', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title_typography',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title',
			]
		);
		$this->start_controls_tabs( 'tabs_title_style' );
		$this->start_controls_tab(
			'title_normal',
			[
				'label' => esc_html__( 'Normal', 'aihee' ),
			]
		);
		$this->add_control(
			'title_n_color',
			[
				'label' => esc_html__( 'Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'title_n_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'title_n_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title',
			]
		);
		$this->add_responsive_control(
			'title_n_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'title_n_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title',
			]
		);
		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'title_n_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title',
			]
		);
		$this->add_control(
			'title_n_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'title_n_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'title_n_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-heading-title',
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'title_hover',
			[
				'label' => esc_html__( 'Hover', 'aihee' ),
			]
		);
		$this->add_control(
			'title_h_color',
			[
				'label' => esc_html__( 'Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'title_h_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'title_h_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title',
			]
		);
		$this->add_responsive_control(
			'title_h_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'title_h_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title',
			]
		);
		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'title_h_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title',
			]
		);
		$this->add_control(
			'title_h_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'title_h_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'title_h_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-heading-title',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*title style end*/
		
		/*description style start*/
		$this->start_controls_section(
            'description_styling',
            [
                'label' => esc_html__('Description Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,				
            ]
        );
		$this->add_responsive_control(
			'description_padding',
			[
				'label' => esc_html__( 'Padding', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-description' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'description_margin',
			[
				'label' => esc_html__( 'Margin', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'description_typography',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-description,{{WRAPPER}} .aihee-main-wrapper .aihee-description p',
			]
		);
		$this->start_controls_tabs( 'tabs_description_style' );
		$this->start_controls_tab(
			'description_normal',
			[
				'label' => esc_html__( 'Normal', 'aihee' ),
			]
		);
		$this->add_control(
			'description_n_color',
			[
				'label' => esc_html__( 'Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-description' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'description_n_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-description',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'description_n_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-description',
			]
		);
		$this->add_responsive_control(
			'description_n_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-description' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'description_n_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-description',
			]
		);
		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'description_n_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-description',
			]
		);
		$this->add_control(
			'description_n_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-description' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'description_n_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-description' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'description_n_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-description',
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'description_hover',
			[
				'label' => esc_html__( 'Hover', 'aihee' ),
			]
		);
		$this->add_control(
			'description_h_color',
			[
				'label' => esc_html__( 'Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'description_h_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'description_h_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description',
			]
		);
		$this->add_responsive_control(
			'description_h_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'description_h_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description',
			]
		);
		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'description_h_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description',
			]
		);
		$this->add_control(
			'description_h_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'description_h_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'description_h_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-description',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*description style end*/
		
		/*button icon style start*/
		$this->start_controls_section(
            'button_icon_text_styling',
            [
                'label' => esc_html__('Button Icon/Text Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
		$this->add_responsive_control(
			'button_icon_padding',
			[
				'label' => esc_html__( 'Padding', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'button_icon_Inner_padding',
			[
				'label' => esc_html__( 'Icon Padding', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon i' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'button_icon_margin',
			[
				'label' => esc_html__( 'Margin', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'button_icon_size',
			[
				'label' => esc_html__( 'Size', 'aihee' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px','%'],
				'range' => [
					'px' => [
						'max' => 1,
						'min' => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 1,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon svg' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};',
				],				
				'condition' => [
					'button_type' => 'icon',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'button_text_typography',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon',
				'condition' => [
					'button_type' => 'text',	
				],
			]
		);		
		$this->start_controls_tabs( 'tabs_button_icon_style' );
		$this->start_controls_tab(
			'button_icon_normal',
			[
				'label' => esc_html__( 'Normal', 'aihee' ),
			]
		);
		$this->add_control(
			'button_icon_n_color',
			[
				'label' => esc_html__( 'Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon i,{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon svg' => 'fill: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'button_icon_n_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon',
				'condition' => [
					'buttonIconEffectH!' => 'yes',
				]
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'button_icon_n_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon',
			]
		);
		$this->add_responsive_control(
			'button_icon_n_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_icon_n_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon',
			]
		);		
		$this->end_controls_tab();
		$this->start_controls_tab(
			'button_icon_hover',
			[
				'label' => esc_html__( 'Hover', 'aihee' ),
			]
		);
		$this->add_control(
			'buttonIconEffectH',
			[
				'label'     => esc_html__( 'Hover Effect', 'aihee' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Enable', 'aihee' ),
				'label_off'    => esc_html__( 'Disable', 'aihee' ),
				'default' => 'no',
			]
		);
		$this->add_control(
			'buttonIconEffectPosH',
			[
				'label' => esc_html__( 'Position', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => esc_html__( 'Bottom', 'aihee' ),
					'aihee-icons-he-top' => esc_html__( 'Top', 'aihee' ),
					'aihee-icons-he-left' => esc_html__( 'Left', 'aihee' ),
					'aihee-icons-he-right' => esc_html__( 'Right', 'aihee' ),
				],
				'condition' => [
					'buttonIconEffectH' => 'yes',
				]
			]
		);
		$this->add_control(
			'button_icon_h_color',
			[
				'label' => esc_html__( 'Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon:hover i,{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon:hover svg' => 'fill: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'button_icon_h_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon:hover',
				'condition' => [
					'buttonIconEffectH!' => 'yes',
				]
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'buttonIconHEffectBg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons.aihee-icons-he .aihee-icon:hover:after',
				'condition' => [
					'buttonIconEffectH' => 'yes',
				]
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'button_icon_h_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon:hover',
			]
		);
		$this->add_responsive_control(
			'button_icon_h_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_icon_h_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-icons .aihee-icon:hover',
			]
		);		
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*button icon/text style end*/
		
		/*image/background style start*/
		$this->start_controls_section(
            'image_background_styling',
            [
                'label' => esc_html__('Image Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout' => ['layout-3','layout-4','layout-5','layout-6','layout-7','layout-8','layout-9','layout-10','layout-11'],
				],
            ]
        );
		$this->add_responsive_control(
            'ib_max_width',
            [
                'type' => Controls_Manager::SLIDER,
				'label' => esc_html__('Max Width', 'aihee'),
				'size_units' => [ 'px' ],				
				'range' => [
					'px' => [
						'min'	=> 1,
						'max'	=> 500,
						'step' => 1,
					],
				],
				'render_type' => 'ui',				
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-image' => 'max-width: {{SIZE}}{{unit}};',
				],
            ]
        );
		$this->start_controls_tabs( 'tabs_ib_style' );
		$this->start_controls_tab(
			'ib_normal',
			[
				'label' => esc_html__( 'Normal', 'aihee' ),
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'ib_n_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-image',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ib_n_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-image',
			]
		);
		$this->add_responsive_control(
			'ib_n_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'ib_n_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-image',
			]
		);
		$this->add_control(
			'ib_n_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-image' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'ib_n_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-image' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'ib_n_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper .aihee-image',
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'ib_hover',
			[
				'label' => esc_html__( 'Hover', 'aihee' ),
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'ib_h_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-image',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ib_h_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-image',
			]
		);
		$this->add_responsive_control(
			'ib_h_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'ib_h_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-image',
			]
		);
		$this->add_control(
			'ib_h_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-image' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'ib_h_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover .aihee-image' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'ib_h_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover .aihee-image',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*image style end*/
		
		/*background style start*/
		$this->start_controls_section(
            'background_21_26_styling',
            [
                'label' => esc_html__('Background Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout' => ['layout-21','layout-22','layout-23','layout-24','layout-25','layout-26'],
				],
            ]
        );
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'background_21_26_opt',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper.aihee-21-70,
				{{WRAPPER}} .aihee-main-wrapper.layout-21 .aihee-hover-21-70',
			]
		);
		$this->end_controls_section();
		/*image style end*/
		/*background style start*/
		$this->start_controls_section(
            'background_13_20_styling',
            [
                'label' => esc_html__('Background Image Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout' => ['layout-13','layout-14','layout-15','layout-16','layout-17','layout-18','layout-19','layout-20'],
				],
            ]
        );
		$this->add_responsive_control(
            'bg_13_20_min_height',
            [
                'type' => Controls_Manager::SLIDER,
				'label' => esc_html__('Min. Height', 'aihee'),
				'size_units' => [ 'px' ],				
				'range' => [
					'px' => [
						'min'	=> 1,
						'max'	=> 1000,
						'step' => 10,
					],
				],
				'render_type' => 'ui',				
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-bg-img-12' => 'min-height: {{SIZE}}{{unit}};',
				],
            ]
        );
		$this->add_control(
			'bg_13_20_size',
			[
				'label' => esc_html__( 'Background Size', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'cover',
				'options' => [
					'auto' => esc_html__( 'Auto', 'aihee' ),
					'cover' => esc_html__( 'Cover', 'aihee' ),
					'contain' => esc_html__( 'Contain', 'aihee' ),
				],
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-bg-img-12' => 'background-size: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'bg_13_20_repeat',
			[
				'label' => esc_html__( 'Background Repeat', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'no-repeat',
				'options' => [
					'no-repeat' => esc_html__( 'No-repeat', 'aihee' ),
					'repeat' => esc_html__( 'Repeat', 'aihee' ),
					'repeat-x' => esc_html__( 'Repeat-x','aihee' ),
					'repeat-y' => esc_html__( 'Repeat-y','aihee' ),
				],
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-bg-img-12' => 'background-repeat: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'bg_13_20_position',
			[
				'label' => esc_html__( 'Background Position', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'center center',
				'options' => [
					'center center' => esc_html__( 'Center Center', 'aihee' ),
					'center left' => esc_html__( 'Center Left', 'aihee' ),
					'center right' => esc_html__( 'Center Right', 'aihee' ),
					'top center' => esc_html__( 'Top Center',  'aihee' ),
					'top left' => esc_html__( 'Top Left','aihee' ),
					'top right' => esc_html__( 'Top Right','aihee' ),
					'bottom center' => esc_html__( 'Bottom Center', 'aihee' ),
					'bottom left' => esc_html__( 'Bottom Left','aihee' ),
					'bottom right' => esc_html__( 'Bottom Right','aihee' ),
				],
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-bg-img-12' => 'background-position: {{VALUE}};',
				],
			]
		);
		$this->end_controls_section();
		/*background style end*/
		
		/*main box style start*/
		$this->start_controls_section(
            'main_wrapper_styling',
            [
                'label' => esc_html__('Main Wrapper Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,				
            ]
        );
		$this->add_responsive_control(
			'main_wrapper_padding',
			[
				'label' => esc_html__( 'Padding', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} ' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'main_wrapper_margin',
			[
				'label' => esc_html__( 'Margin', 'aihee' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],			
				'selectors' => [
					'{{WRAPPER}} ' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->start_controls_tabs( 'tabs_main_wrapper_style' );
		$this->start_controls_tab(
			'main_wrapper_normal',
			[
				'label' => esc_html__( 'Normal', 'aihee' ),
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'main_wrapper_n_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'main_wrapper_n_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper',
			]
		);
		$this->add_responsive_control(
			'main_wrapper_n_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'main_wrapper_n_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper',
			]
		);
		$this->add_control(
			'main_wrapper_n_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'main_wrapper_n_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'main_wrapper_n_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper',
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'main_wrapper_hover',
			[
				'label' => esc_html__( 'Hover', 'aihee' ),
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'main_wrapper_h_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'main_wrapper_h_border',
				'label' => esc_html__( 'Border', 'aihee' ),
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover',
			]
		);
		$this->add_responsive_control(
			'main_wrapper_h_br',
			[
				'label'      => esc_html__( 'Border Radius', 'aihee' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .aihee-main-wrapper:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'main_wrapper_h_shadow',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover',
			]
		);
		$this->add_control(
			'main_wrapper_h_transition',
			[
				'label' => esc_html__( 'Transition css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'all 0.3s linear', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover' => '-webkit-transition: {{VALUE}};-moz-transition: {{VALUE}};-o-transition: {{VALUE}};-ms-transition: {{VALUE}};'
				],
				'separator' => 'before',
			]
		);
		$this->add_control(
			'main_wrapper_h_transform',
			[
				'label' => esc_html__( 'Transform css', 'aihee' ),
				'type' => Controls_Manager::TEXT,				
				'placeholder' => esc_html__( 'rotate(25deg) scale(1.2)', 'aihee' ),
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper:hover' => 'transform: {{VALUE}};-ms-transform: {{VALUE}};-moz-transform: {{VALUE}};-webkit-transform: {{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'main_wrapper_h_filters',
				'selector' => '{{WRAPPER}} .aihee-main-wrapper:hover',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_responsive_control(
            'mw_z_index',
            [
                'type' => Controls_Manager::SLIDER,
				'label' => esc_html__('Z-Index', 'aihee'),
				'size_units' => [ 'px' ],				
				'range' => [
					'px' => [
						'min'	=> 0,
						'max'	=> 10000,
						'step' => 1,
					],
				],
				'render_type' => 'ui',				
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper' => 'z-index: {{SIZE}};',
				],
				'separator' => 'before',
            ]
        );
		$this->add_control(
			'mw_overflow_switch', [
				'label'   => esc_html__( 'Overflow', 'aihee' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
				'label_on' => esc_html__( 'Enable', 'aihee' ),
				'label_off' => esc_html__( 'Disable', 'aihee' ),				
				'separator' => 'before',
			]
		);
		$this->add_control(
			'mw_overflow',
			[
				'label' => esc_html__( 'Overflow', 'aihee' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'hidden',
				'options' => [
					'unset'  => esc_html__( 'Unset', 'aihee' ),
					'visible'  => esc_html__( 'Visible', 'aihee' ),
					'hidden'  => esc_html__( 'Hidden', 'aihee' ),
					'scroll'  => esc_html__( 'Scroll', 'aihee' ),
					'auto'  => esc_html__( 'Auto', 'aihee' ),
					'initial'  => esc_html__( 'Initial', 'aihee' ),
					'inherit'  => esc_html__( 'Inherit', 'aihee' ),
				],
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper' => 'overflow:{{VALUE}};',
				],
				'condition' => [
					'mw_overflow_switch' =>  'yes',
				],
			]
		);
		$this->end_controls_section();
		/*main box style end*/
		
		/*extra styling start*/
		$this->start_controls_section(
            'extra_styling',
            [
                'label' => esc_html__('Extra Styling', 'aihee'),
                'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'layout' => ['layout-1','layout-2','layout-10','layout-11','layout-12','layout-13','layout-14','layout-15','layout-16','layout-17','layout-20'],
				],
            ]
        );
		$this->add_control(
			'st1_inner_border',
			[
				'label' => esc_html__( 'Inner Border Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper.layout-1 .aihee-inner-content:before, {{WRAPPER}} .aihee-main-wrapper.layout-1 .aihee-inner-content:after' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'layout' => 'layout-1',
				],
			]
		);
		$this->add_control(
			'st2_content_bg',
			[
				'label' => esc_html__( 'Content Background Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper.layout-2 .aihee-inner-content' => 'background: {{VALUE}};',
				],
				'condition' => [
					'layout' => 'layout-2',
				],
			]
		);
		$this->add_control(
			'st_10_11_offset',
			[
				'label' => esc_html__( 'Offset', 'aihee' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'max' => 500,
						'min' => -500,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper.layout-10 .aihee-image,
					{{WRAPPER}} .aihee-main-wrapper.layout-11 .aihee-image' => 'top: {{SIZE}}{{UNIT}};',
				],				
				'condition' => [
					'layout' => ['layout-10','layout-11'],
				],
			]
		);
		$this->add_control(
			'st12_17_after_color',
			[
				'label' => esc_html__( 'After Effect Color', 'aihee' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper .aihee-hover-12,
					{{WRAPPER}} .aihee-main-wrapper .aihee-bg-img-12:after' => 'background: {{VALUE}};',
				],
				'condition' => [
					'layout' => ['layout-12','layout-13','layout-14','layout-15','layout-16','layout-17'],
				],
			]
		);
		$this->add_control(
			'st_20_rotate',
			[
				'label' => esc_html__( 'Rotate', 'aihee' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'max' => 2000,
						'min' => -2000,
						'step' => 90,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1080,
				],
				'selectors' => [
					'{{WRAPPER}} .aihee-main-wrapper.layout-20.aihee-afc-11.aihee-12-16 .aihee-hover-12' => '-webkit-transform: scale(.5) rotate(-{{SIZE}}deg);
					-ms-transform: scale(.5) rotate(-{{SIZE}}deg);transform: scale(.5) rotate(-{{SIZE}}deg);',
					'{{WRAPPER}} .aihee-main-wrapper.layout-20.aihee-afc-11.aihee-12-16:hover .aihee-bg-img-12' => '-webkit-transform: scale(.5) rotate({{SIZE}}deg);
					-ms-transform: scale(.5) rotate({{SIZE}}deg);transform: scale(.5) rotate({{SIZE}}deg);',
				],				
				'condition' => [
					'layout' => 'layout-20',
				],
			]
		);
		$this->end_controls_section();
		/*extra styling end*/
		/*style end*/
	}
	
	public function html_tag_check_and_verify(){
		return [ 'h1',
			'h2',
			'h3',
			'h4',
			'h5',
			'h6',
			'div',
			'span',
			'p',
			'main',
			'nav',		
			'section',
			'header',
			'footer',
			'article',
			'aside',		
		];
	}		

	public function check_and_validate_html_tag( $chk_tag ) {
		return in_array( strtolower( $chk_tag ), $this->html_tag_check_and_verify() ) ? $chk_tag : 'div';
	}
	
	protected function render() {
		 $settings = $this->get_settings_for_display();
		 
		 $layout= $settings['layout'];
		 $heading_title= $settings['heading_title'];
		 $heading_title_tag= !empty($settings['heading_title_tag']) ? $settings['heading_title_tag'] : 'h1';
		 $description_text= $settings['description_text'];
		 
		 $button_type= $settings['button_type'];
		 
		 $bit1=$bit2=$bit3=$bu1=$bu1_target=$bu1_nofollow=$bu2=$bu2_target=$bu2_nofollow=$bu3=$bu3_target=$bu3_nofollow='';
		 
		 if(!empty($button_type) && $button_type=='icon'){
			$button_icon_1= $settings['button_icon_1'];
			$button_icon_2= $settings['button_icon_2'];
			$button_icon_3= $settings['button_icon_3'];
			
			if(!empty($button_icon_1)){
				ob_start();
				\Elementor\Icons_Manager::render_icon($button_icon_1, [ 'aria-hidden' => 'true' ]);
				$bit1 = ob_get_contents();
				ob_end_clean();	
			}
			if(!empty($button_icon_2)){
				ob_start();
				\Elementor\Icons_Manager::render_icon($button_icon_2, [ 'aria-hidden' => 'true' ]);
				$bit2 = ob_get_contents();
				ob_end_clean();	
			}
			if(!empty($button_icon_3)){
				ob_start();
				\Elementor\Icons_Manager::render_icon($button_icon_3, [ 'aria-hidden' => 'true' ]);
				$bit3 = ob_get_contents();
				ob_end_clean();	
			}
		 }
		 
		 if(!empty($button_type) && $button_type=='text'){
			$button_text_1= $settings['button_text_1'];
			$button_text_2= $settings['button_text_2'];
			$button_text_3= $settings['button_text_3'];
			
			if(!empty($button_text_1)){
				$bit1 = esc_attr($button_text_1);
			}
			if(!empty($button_text_2)){
				$bit2 = esc_attr($button_text_2);
			}
			if(!empty($button_text_3)){
				$bit3 = esc_attr($button_text_3);
			}
		 }
		 
		$button_url_1= $settings['button_url_1'];
		$button_url_2= $settings['button_url_2'];
		$button_url_3= $settings['button_url_3'];
		
		if(!empty($button_url_1)){
			$bu1 = $settings['button_url_1']['url'];
			$bu1_target = $settings['button_url_1']['is_external'] ? ' target="_blank"' : '';
			$bu1_nofollow = $settings['button_url_1']['nofollow'] ? ' rel="nofollow"' : '';
		}
		if(!empty($button_url_2)){
			$bu2 = $settings['button_url_2']['url'];
			$bu2_target = $settings['button_url_2']['is_external'] ? ' target="_blank"' : '';
			$bu2_nofollow = $settings['button_url_2']['nofollow'] ? ' rel="nofollow"' : '';
		}
		if(!empty($button_url_3)){
			$bu3 = $settings['button_url_3']['url'];
			$bu3_target = $settings['button_url_3']['is_external'] ? ' target="_blank"' : '';
			$bu3_nofollow = $settings['button_url_3']['nofollow'] ? ' rel="nofollow"' : '';
		}
		
		$image_src='';
		if(!empty($settings['aihee_image']['id'])){			
			$image_id=$settings['aihee_image']['id'];
			$image_src=wp_get_attachment_image_src($image_id, $settings['thumbnail_size']);
			if(!empty($image_src[0])){
				$image_src=$image_src[0];
			}			
		}else{
			$image_src = AIHEE_URL.'assets/image/placeholder.png';
		}
		
		$final_ht=$final_desc=$final_button='';
		/*title start*/		
		if(!empty($heading_title)){
			$final_ht ='<'.esc_attr($this->check_and_validate_html_tag($heading_title_tag)).' class="aihee-heading-title">'.esc_html($heading_title).'</'.esc_attr($this->check_and_validate_html_tag($heading_title_tag)).'>';
		}		
		/*title end*/
		
		/*description start*/
		if(!empty($description_text)){
			$final_desc ='<div class="aihee-description">'.wp_kses_post($description_text).'</div>';
		}
		/*description end*/
		
		/*button start*/
		if(!empty($bit1) || !empty($bit2) || !empty($bit3)){
			$buttonIconEffectH = (isset($settings['buttonIconEffectH']) && $settings['buttonIconEffectH'] == 'yes') ? 'aihee-icons-he '.esc_attr($settings['buttonIconEffectPosH']) : '';
			$final_button .='<div class="aihee-icons '.esc_attr($buttonIconEffectH).'">';
				if(!empty($bit1)){
					$final_button .='<a href="'.esc_url($bu1).'" class="aihee-icon aihee-icon-first">'.$bit1.'</a>';
				}
				if(!empty($bit2)){
					$final_button .='<a href="'.esc_url($bu2).'" class="aihee-icon aihee-icon-second">'.$bit2.'</a>';
				}
				if(!empty($bit3)){
					$final_button .='<a href="'.esc_url($bu3).'" class="aihee-icon aihee-icon-third">'.$bit3.'</a>';
				}		
			$final_button .='</div>';
		}						
		/*button end*/
		
		if(!empty($layout)){
			wp_enqueue_style('aihee-css');
			$layoutclass=$layoutextraclass='';
			if($layout=='layout-3' || $layout=='layout-4' || $layout=='layout-5' || $layout=='layout-6' || $layout=='layout-7' || $layout=='layout-8' || $layout=='layout-9' || $layout=='layout-10' || $layout=='layout-11'){
				$layoutclass=" aihee-3-11";
			}
			
			if($layout=='layout-12' || $layout=='layout-13' || $layout=='layout-14' || $layout=='layout-15' || $layout=='layout-16' || $layout=='layout-17' || $layout=='layout-18' || $layout=='layout-19' || $layout=='layout-20'){
				$layoutclass=" aihee-afc-11";
				$layoutextraclass=" aihee-12-16";
			}
			
			if($layout=='layout-21' || $layout=='layout-22' || $layout=='layout-23' || $layout=='layout-24' || $layout=='layout-25' || $layout=='layout-26' || $layout=='layout-27' || $layout=='layout-28' || $layout=='layout-29' || $layout=='layout-30' || $layout=='layout-31' || $layout=='layout-32' || $layout=='layout-33' || $layout=='layout-34' || $layout=='layout-35' || $layout=='layout-36' || $layout=='layout-37' || $layout=='layout-38' || $layout=='layout-39' || $layout=='layout-40' || $layout=='layout-41' || $layout=='layout-42' || $layout=='layout-43' || $layout=='layout-44' || $layout=='layout-45' || $layout=='layout-46' || $layout=='layout-47' || $layout=='layout-48' || $layout=='layout-49' || $layout=='layout-50' || $layout=='layout-51' || $layout=='layout-52' || $layout=='layout-53' || $layout=='layout-54' || $layout=='layout-55' || $layout=='layout-56' || $layout=='layout-57' || $layout=='layout-58' || $layout=='layout-59' || $layout=='layout-60' || $layout=='layout-61' || $layout=='layout-62' || $layout=='layout-63' || $layout=='layout-64' || $layout=='layout-65' || $layout=='layout-66' || $layout=='layout-67' || $layout=='layout-68' || $layout=='layout-69' || $layout=='layout-70'){
				$layoutclass=" aihee-21-70";
			}
			
			$output ='<div class="aihee-main-wrapper '.esc_attr($layout).' '.esc_attr($layoutclass).' '.esc_attr($layoutextraclass).'">';
				if($layout=='layout-1'){
					$output .='<img src="'.esc_url($image_src).'" class="aihee-image">';
					$output .='<div class="aihee-inner-content">';
						$output .=$final_ht;
						$output .=$final_desc;
						$output .=$final_button;
					$output .='</div>';
				}
				
				if($layout=='layout-2'){					
					$output .='<img src="'.esc_url($image_src).'" class="aihee-image">';
					$output .='<div class="aihee-inner-content">';
						$output .=$final_ht;							
						$output .=$final_button;
						$output .=$final_desc;
					$output .='</div>';
				}
				
				if($layout=='layout-3' || $layout=='layout-4' || $layout=='layout-5' || $layout=='layout-6' || $layout=='layout-7' || $layout=='layout-9' || $layout=='layout-10' || $layout=='layout-11'){
					$output .='<img src="'.esc_url($image_src).'" class="aihee-image">';					
					$output .=$final_ht;
					$output .=$final_desc;							
					$output .=$final_button;
				}
				
				if($layout=='layout-8'){
					$output .='<div class="aihee-inner-content">';
						$output .='<img src="'.esc_url($image_src).'" class="aihee-image">';
						$output .=$final_ht;
					$output .='</div>';					
					$output .=$final_desc;							
					$output .=$final_button;
				}
				
				if($layout=='layout-12' || $layout=='layout-13' || $layout=='layout-14' || $layout=='layout-15' || $layout=='layout-16' || $layout=='layout-17' || $layout=='layout-18' || $layout=='layout-19' || $layout=='layout-20'){
					$output .='<div class="aihee-bg-img-12" style=" background-image:url('.esc_url($image_src).');"></div>';
					$output .='<div class="aihee-hover-12">';
						$output .=$final_ht;
						$output .=$final_desc;
						$output .=$final_button;
					$output .='</div>';
				}
				
				if($layout=='layout-21' || $layout=='layout-22' || $layout=='layout-23' || $layout=='layout-24' || $layout=='layout-25' || $layout=='layout-26'){
					$output .='<img src="'.esc_url($image_src).'" class="aihee-image">';
					$output .='<div class="aihee-hover-21-70">';
						$output .=$final_ht;
						$output .=$final_desc;
						$output .=$final_button;
					$output .='</div>';
				}
				
			$output .='</div>';
			
			echo wp_kses_normalize_entities($output);
		}
	}
}

Plugin::instance()->widgets_manager->register( new Advanced_Image_Hover_Effect_Kap_Asias() );