<?php
/**
 * Vertical Timeline Widget for Elementor.
 *
 * @since 1.0.0
 */
namespace twe\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Repeater;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Typography;
use Elementor\Scheme_Color;
use Elementor\Group_Control_Text_Shadow;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class TweTimelineWidget extends Widget_Base {
	/**
	 * Get widget name.
	 *
	 * Retrieve widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'be-timeline';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Timeline', '3r-elementor-timeline-widget' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-time-line';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'basic' ];
	}

	/**
	 * Register widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Timeline', '3r-elementor-timeline-widget' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		$repeater = new \Elementor\Repeater();
			
		$repeater->start_controls_tabs(
			'twae_story_tabs'
		);

		// Story Tab - Content - START
		$repeater->start_controls_tab(
			'twe_content_tab',
			array(
				'label' => __( 'Content', '3r-elementor-timeline-widget' ),
			)
		);

		// Story Year / Label Show/Hide
		$repeater->add_control(
			'twe_show_year_label',
			array(
				'label'        => __( 'Year / Label (Top) <a href="https://cooltimeline.com/elementor-widget/vertical-timeline-widget-for-elementor/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=demo&utm_content=content_tab_settings" target="_blank" style=" pointer-events: all; color:  #EDACFB;">(Demo ⇗)</a>', '3r-elementor-timeline-widget' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'twae' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- using shared text domain intentionally
				'label_off'    => __( 'Hide', 'twae' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- using shared text domain intentionally
				'return_value' => 'yes',
				'default'      => 'no',

			)
		);
	

		// Story Label / Date
		$repeater->add_control(
			'twe_date_label',
			array(
				'label'   => __( 'Label / Date', '3r-elementor-timeline-widget' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Jan 2020',
			)
		);
	

				// Story Media
		$repeater->add_control(
			'twe_media',
			array(
				'label'     => __( 'Add Video/Slideshow', '3r-elementor-timeline-widget' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'video'     => array(
						'title' => __( 'Video', '3r-elementor-timeline-widget' ),
						'icon'  => 'fa fa-video',
					),
					'slideshow' => array(
						'title' => __( 'Slideshow', '3r-elementor-timeline-widget' ),
						'icon'  => 'fa fa-images',
					),
				),
				'default'   => 'image',
				'toggle'    => true,
			)
		);

			$repeater->add_control(
				'twe_label_upgrade_button',
				[
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw'  => '
						<div class="twae-upgrade-content-notice">
							<a href="https://cooltimeline.com/plugin/elementor-timeline-widget-pro/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=content_tab_settings#pricing" 
							target="_blank" 
							class="twae-upgrade-link">
								UPGRADE TO PRO 💎
							</a>
						</div>
					',
					'content_classes' => 'twae-upgrade-container',
				]
			);
		
		$repeater->add_control(
			'image',
			[
				'label' => __( 'Choose Image', '3r-elementor-timeline-widget' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);
		$repeater->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name' => 'thumbnail', // Usage: `{name}_size` and `{name}_custom_dimension`, in this case `thumbnail_size` and `thumbnail_custom_dimension`.
				'separator' => 'none',
			]
		);
		$repeater->add_control(
			'list_title', [
				'label' => __( 'Title', '3r-elementor-timeline-widget' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Timeline' , '3r-elementor-timeline-widget' ),
				'label_block' => true,
			]
		);
		

		$repeater->add_control(
			'list_content', [
				'label' => __( 'Timelines', '3r-elementor-timeline-widget' ),
				'type' => \Elementor\Controls_Manager::WYSIWYG,
				'default' => __( 'Item content. Click the edit button to change this text.' , '3r-elementor-timeline-widget' ),
				'show_label' => false,
			]
		);
	$repeater->end_controls_tab();
		$repeater->start_controls_tab(
				'twe_advanced_tab',
				array(
					'label' => __( 'Advanced', '3r-elementor-timeline-widget' ),
				)
			);
				$repeater->add_control(
				'twe_color_upgrade_button',
				[
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw'  => '
						<div class="twae-upgrade-color-notice">
							<a href="https://cooltimeline.com/plugin/elementor-timeline-widget-pro/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=advanced_tab_settings#pricing" 
							target="_blank" 
							class="twae-upgrade-link">
								UPGRADE TO PRO 💎
							</a>
						</div>
					',
					'content_classes' => 'twae-upgrade-container',
				]
			);
	

		// Story Box Background
		$repeater->add_control(
			'twe_custom_story_bgcolor',
			array(
				'label'       => __( 'Background Color', '3r-elementor-timeline-widget' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'render_type' => 'template',
				'selectors'   => array(
					'{{WRAPPER}} .twae-wrapper {{CURRENT_ITEM}}' => '--tw-cbx-bg: {{VALUE}};
					--tw-cbx-bg-gradient: {{VALUE}};
					--tw-arw-bg: {{VALUE}};',
				),
			)
		);
		// Story Box Border Color
		$repeater->add_control(
			'twe_custom_story_bdcolor',
			array(
				'label'     => esc_html__( 'Border Color', '3r-elementor-timeline-widget' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .twae-wrapper {{CURRENT_ITEM}}' => '--tw-cbx-bd-color: {{VALUE}};
					--tw-cbx-bd-color: {{VALUE}};
					--tw-arw-bd-color: {{VALUE}};',
				),
			)
		);
		// Story Title Color
		$repeater->add_control(
			'twe_custom_story_title_color',
			array(
				'label'     => __( 'Title Color', '3r-elementor-timeline-widget' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .twae-wrapper {{CURRENT_ITEM}}' => '--tw-cbx-title-color: {{VALUE}}',
				),
			)
		);
	
		// Story Description Color
		$repeater->add_control(
			'twe_custom_description_color',
			array(
				'label'     => __( 'Description Color', '3r-elementor-timeline-widget' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .twae-wrapper {{CURRENT_ITEM}}' => '--tw-cbx-des-color: {{VALUE}}',
				),
			)
		);

				$repeater->add_control(
				'twe_add_icon_on_line',
				[
					'label'        => __( 'Add Icon on Line', '3r-elementor-timeline-widget' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => __( 'Yes', '3r-elementor-timeline-widget' ),
					'label_off'    => __( 'No', '3r-elementor-timeline-widget' ),
					'return_value' => 'yes',
					'default'      => 'no',
				]
			);
 
		// Story Read More Show/Hide
		$repeater->add_control(
			'twe_title_link',
			array(
				'label'        => __( 'Read More Button', '3r-elementor-timeline-widget' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'twae' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- using shared text domain intentionally
				'label_off'    => __( 'Hide', 'twae' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- using shared text domain intentionally
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$repeater->end_controls_tab();
		$repeater->end_controls_tabs(); 
		$this->add_control(
			'list',
			[
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' =>  $repeater->get_controls(),
				'default' => [
					[
						'list_title' => __( 'Timeline', '3r-elementor-timeline-widget' ),
						'list_content' => __( 'Item content. Click the edit button to change this text.', '3r-elementor-timeline-widget' ),
					],
					[
						'list_title' => __( 'Timeline', '3r-elementor-timeline-widget' ),
						'list_content' => __( 'Item content. Click the edit button to change this text.', '3r-elementor-timeline-widget' ),
					],
				],
				'title_field' => '{{{ list_title }}}',
			]
		);
          
              
           
			
                
		if ( !file_exists( WP_PLUGIN_DIR . '/timeline-widget-addon-for-elementor-pro/timeline-widget-addon-pro-for-elementor.php' )){
				if ( get_option( 'twae_hide_upgrade_notice_editor' ) !== 'yes' ) {
				$this->add_control(
					'twae_upgrade_notice',
					[
						'type' => \Elementor\Controls_Manager::RAW_HTML,
						'raw'  => '<div class="elementor-control-raw-html">
							<div class="elementor-control-notice elementor-control-notice-type-info twae-upgrade-pro-notice" style="position: relative;">
								<button type="button" class="elementor-control-notice-dismiss twae_hide_upgrade_notice_editor" style="position: absolute; top: 5px; right: 5px; z-index: 10;">
									<i class="eicon-close"></i>
								</button>
								<div class="elementor-control-notice-icon">
									<img class="twae-highlight-icon" src="'.esc_url( TWE_PLUGIN_URL . 'assets/images/twae-highlight-icon.svg' ).'" width="250" alt="Highlight Icon" style="filter: brightness(0) saturate(100%) invert(32%) sepia(84%) saturate(627%) hue-rotate(190deg) brightness(92%) contrast(92%);" />
								</div>
								<div class="elementor-control-notice-main">
									<div class="elementor-control-notice-main-content">
										Want more advanced features? Upgrade to the Pro version.
									</div>
									<div class="elementor-control-notice-main-actions">
										<a class="elementor-button e-btn e-info e-btn-1" style="color:white;"
										href="https://cooltimeline.com/plugin/elementor-timeline-widget-pro/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=content_tab_settings#pricing"
										target="_blank">
											Get Pro
										</a>
									</div>
								</div>
							</div>
						</div>',
						'content_classes' => 'twae-upgrade-box',
					]
				);
			}
	}

		if ( defined( 'TWAE_PRO_VERSION' ) || defined( 'TWAE_VERSION' ) ) { 
			if (get_option('twe_hide_migration_notice') !== 'yes' ) {

				$this->add_control(
					'twae_migrate_notice',
					[
						'type' => \Elementor\Controls_Manager::RAW_HTML,
						'raw'  => '<div class="elementor-control-raw-html">
							<div class="elementor-control-notice elementor-control-notice-type-info twae-migration-notice" style="position: relative;">
								<button type="button" class="elementor-control-notice-dismiss twae_hide_migration_notice_editor" style="position: absolute; top: 5px; right: 5px; z-index: 10;">
									<i class="eicon-close"></i>
								</button>
								<div class="elementor-control-notice-icon">
									<img class="twae-highlight-icon" src="'.esc_url( TWE_PLUGIN_URL . 'assets/images/twae-highlight-icon.svg' ).'" width="250" alt="Highlight Icon" style="filter: brightness(0) saturate(100%) invert(32%) sepia(84%) saturate(627%) hue-rotate(190deg) brightness(92%) contrast(92%);" />
								</div>
								<div class="elementor-control-notice-main">
									<div class="elementor-control-notice-main-content">
										Do you want to migrate this timeline into Timeline Widget Pro to use the advanced features?
									</div>
									<div class="elementor-control-notice-main-actions">
										<button type="button" class="elementor-button e-btn e-info e-btn-1" id="twae-run-migration">Migrate Now</button>
									</div>
								</div>
							</div>
						</div>',
						'content_classes' => 'twae-migrate-box',
					]
				);
			}
		}
		
	  $this->end_controls_section();
       
	  $this->start_controls_section(
			'twe_layout_section',
			array(
				'label' => __( 'Layout Settings', '3r-elementor-timeline-widget' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		// Select Layout
		$this->add_control(
			'twe_layout',
			array(
				'label'   => __( 'Layout', '3r-elementor-timeline-widget' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'centered',
				'options' => array(
					'centered'               => 'Vertical Right / Left (Free)',
					'one-sided'              => 'Vertical Right Only(Free)',
					'left-sided'             => 'Vertical Left Only(Pro)',
					'compact'                => 'Vertical Compact(Pro)',
					'modern'                    => 'Vertical Tab(Pro)',
					'horizontal'             => 'Horizontal Top(Pro)',
					'horizontal-bottom'      => 'Horizontal Bottom(Pro)',
					'horizontal-highlighted' => 'Horizontal Highlighted(Pro)',
				),
			)
		);

	
		// Story Content Alignment
		$this->add_control(
			'twe_content_alignment',
			array(
				'label'     => esc_html__( 'Content Alignment', '3r-elementor-timeline-widget' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'separator' => 'before',
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', '3r-elementor-timeline-widget' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', '3r-elementor-timeline-widget' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', '3r-elementor-timeline-widget' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'toggle'    => true,
				'selectors' => array(
					'{{WRAPPER}} .twae-wrapper' => '--tw-cbx-text-align: {{VALUE}};',
				),
			)
		);

			$this->add_control(
			'twe_display_icons',
			array(
				'label'   => esc_html__( 'Display Icons', '3r-elementor-timeline-widget' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'options' => array(
					'displayicons' => array(
						'title' => esc_html__( 'Icons', '3r-elementor-timeline-widget' ),
						'icon'  => 'eicon-clock',
					),
					'displaydots'  => array(
						'title' => esc_html__( 'Dots', '3r-elementor-timeline-widget' ),
						'icon'  => 'eicon-circle',
				
					),
					'displaynone'  => array(
						'title' => esc_html__( 'None', '3r-elementor-timeline-widget' ),
						'icon'  => 'eicon-ban',
					),
				),
				'default' => 'displayicons',
				'toggle'  => false,
				
			)
		);
		$this->add_control(
			'twe_animation',
			array(
				'label'     => __( 'Animations', '3r-elementor-timeline-widget' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'fade',
				'options' => array(
					'none'            => 'none',
					'fade'            => 'fade',
					'zoom-in'         => 'zoom-in',
					'flip-right'      => 'flip-right',
					'zoom-out'        => 'zoom-out',
					'fade-up'         => 'fade-up',
					'fade-down'       => 'fade-down',
				),
				
			)
		);
			$this->add_control(
				'twe_upgrade_button_layout_section',
				[
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw'  => '
						<div class="twe-upgrade-button-notice-layout-section">
							<a href="https://cooltimeline.com/plugin/elementor-timeline-widget-pro/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=layout_tab_settings#pricing" 
							target="_blank" 
							class="twae-upgrade-link">
								UPGRADE TO PRO 💎
							</a>
						</div>
					',
					'content_classes' => 'twae-upgrade-container',
				]
			);
		$this->end_controls_section();

	/*------- BoxStyle ------------*/
	$this->start_controls_section(
		'content_style',
		[
			'label' => __( 'Content Style', '3r-elementor-timeline-widget' ),
			'tab' => Controls_Manager::TAB_STYLE,
		]
	);
	$this->add_control(
		'header_size',
		[
			'label' => esc_html__( 'Title HTML Tag', '3r-elementor-timeline-widget' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'h1' => 'H1',
				'h2' => 'H2',
				'h3' => 'H3',
				'h4' => 'H4',
				'h5' => 'H5',
				'h6' => 'H6',
				'div' => 'div',
				'span' => 'span',
				'p' => 'p',
			],
			'default' => 'h2',
		]
	);
	$this->add_control(
		'title_color', [
		'label' => __( 'Title Fonts Color', '3r-elementor-timeline-widget' ),
		'type' => \Elementor\Controls_Manager::COLOR,
		'selectors' => [
				'{{WRAPPER}} .tl-heading .tl-content .be-desc .be-title' => 'color: {{VALUE}}',
			],
		'default' => '#333333',
	]
	);
	
    $this->add_group_control(
		Group_Control_Typography::get_type(),
		[
			'label' => 'Title Typography',
			'name' => 'tile_typography',
			'selector' => '{{WRAPPER}} .be-pack .tl-heading .be-title',
		]
	);
    $this->add_group_control(
		Group_Control_Text_Shadow::get_type(),
		[
			'label' => 'Title Text Shadow',
			'name' => 'text_shadow',
			'selector' => '{{WRAPPER}} .tl-heading .be-title',
		]
	);
    $this->add_control(
		'title_margin',
		[
			'label' => __( 'Title Margin', '3r-elementor-timeline-widget' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors' => [
				'{{WRAPPER}} .be-pack.timeline .be-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
            'separator'=>'after',
		]
	);
	$this->add_control(
		'content_color', [
		'label' => __( 'Content Fonts Color', '3r-elementor-timeline-widget' ),
		'type' => \Elementor\Controls_Manager::COLOR,
		'selectors' => [
			'{{WRAPPER}} .be-pack .timeline-panel, {{WRAPPER}} .be-pack .timeline-panel p' => 'color: {{content_color}}',
		],
		'default' => '#333333',
	]
	);
	
	$this->add_group_control(
		Group_Control_Typography::get_type(),
		[	'label' => 'Content Typography',
			'name' => 'content_typography',
			'selector' => '{{WRAPPER}} .be-pack .timeline-panel',
		]
	);
   
	$this->end_controls_section();
	//=======Content style =======
		$this->start_controls_section(
			'section_title_style',
			[
				'label' => __( 'Box Style', '3r-elementor-timeline-widget' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_control(
				'theme_color',
				[
					'label' => __( 'Border Color', '3r-elementor-timeline-widget' ),
					'type'  => \Elementor\Controls_Manager::COLOR,
					'selectors' => [

						'{{WRAPPER}} .timeline li .tl-circ' =>
							'background: {{theme_color}}; border:5px solid #e6e6e6 !important;',

						'{{WRAPPER}} .timeline li .timeline-panel' =>
							'border-color: {{theme_color}};',

						'{{WRAPPER}} .timeline:before' =>
							'background-color: {{theme_color}};',

						'{{WRAPPER}} .timeline li:not(.timeline-inverted) .timeline-panel:before' =>
							'border-left-color: {{theme_color}};',

						'{{WRAPPER}} .timeline li.timeline-inverted .timeline-panel:before' =>
							'border-right-color: {{theme_color}};',

						'{{WRAPPER}} .timeline.timeline-one-sided li .timeline-panel:before' =>
							'border-right-color: {{theme_color}}; border-left-width: 0;',
					],
				]
			);


				
			$this->add_control(
			'bg_color',
			[
				'label' => __( 'Background Color', '3r-elementor-timeline-widget' ),
				'type'  => \Elementor\Controls_Manager::COLOR,
				'default' => '#fff',
				'selectors' => [

					'{{WRAPPER}} .be-pack .timeline-panel' =>
						'--twe-panel-bg: {{VALUE}}; background-color: {{VALUE}};',

					'{{WRAPPER}} .timeline li:not(.timeline-inverted) .timeline-panel:after' =>
						'border-left-color: var(--twe-panel-bg);',

					'{{WRAPPER}} .timeline li.timeline-inverted .timeline-panel:after' =>
						'border-right-color: var(--twe-panel-bg);',
						
						'{{WRAPPER}} .timeline.timeline-one-sided li .timeline-panel:after' =>
                    'border-right-color: var(--twe-panel-bg); border-left-width:0;',

				],
			]
		);

		$this->add_control(
		'circle_color',
		[
			'label' => __( 'Icon Background / Connector Color', '3r-elementor-timeline-widget' ),
			'type'  => \Elementor\Controls_Manager::COLOR,
			'selectors' => [

				'{{WRAPPER}} .timeline li .tl-circ' =>
					'background: {{VALUE}};',

				'{{WRAPPER}} .timeline li:not(.timeline-inverted) .timeline-panel:before' =>
					'border-left-color: {{VALUE}};',

				'{{WRAPPER}} .timeline li.timeline-inverted .timeline-panel:before' =>
					'border-right-color: {{VALUE}};',

				'{{WRAPPER}} .timeline.timeline-one-sided li .timeline-panel:before' =>
					'border-right-color: {{VALUE}}; border-left-width:0;',
			],
		]
	);




		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .timeline-panel',
			]
		);
         $this->add_control(
			'tl_change_direction',
			[
				'label' => __( 'Direction', '3r-elementor-timeline-widget' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Left', '3r-elementor-timeline-widget' ),
				'label_off' => __( 'Right', '3r-elementor-timeline-widget' ),
				'return_value' => 'left',
				'default' => 'left',
                'separator'=>'before',
				 'condition' => [
            'twe_layout!' => 'one-sided',
        ],
			]
		);
	
		$this->end_controls_section();
		$this->start_controls_section(
			'image_style',
			[
				'label' => __( 'Image Style', '3r-elementor-timeline-widget' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'border_radius',
			[
				'label' => __( 'Border Radius', '3r-elementor-timeline-widget' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .be-pack.timeline .timeline_pic img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'=>[
					'top' =>15,
					'right' => 15,
					'bottom' => 15,
					'left' => 15,
					'isLinked' => true,
				],
			]
		);
		$this->add_control(
			'gap',
			[
				'label' => __( 'Image Padding', '3r-elementor-timeline-widget' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .be-pack.timeline .timeline_pic' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'=>[
					'top' =>15,
					'right' => 15,
					'bottom' => 15,
					'left' => 15,
					'isLinked' => true,
				],
			]
		);
		$this->add_control(
			'img_margin',
			[
				'label' => __( 'Image Margin', '3r-elementor-timeline-widget' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .be-pack.timeline .timeline_pic' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'=>[
					'top' =>15,
					'right' => 15,
					'bottom' => 15,
					'left' => 15,
					'isLinked' => true,
				],
			]
		);

		$this->end_controls_section();

	}
		

	/**
	 * Render widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$is_one_sided = ( isset( $settings['twe_layout'] ) && $settings['twe_layout'] === 'one-sided' );
		$direction = isset($settings['tl_change_direction']) ? $settings['tl_change_direction'] : '';
		$data	  = $settings['list'];
		$this->add_render_attribute( 'title', 'class', 'be-title' );
        $direction = in_array($direction, array('left', ''), true) ? $direction : '';
        $count = $direction =='left' ? 1 : 2;

		$layout_class = ( isset( $settings['twe_layout'] ) && $settings['twe_layout'] === 'one-sided' )
			? 'timeline-one-sided'
			: '';

		echo '<ul class="be-pack timeline ' . esc_attr( $layout_class ) . '">';
        
		// echo '<ul class="be-pack timeline">';
		$count = ( $direction === 'left' ) ? 1 : 0;
		foreach($data as $index=>$content){
		    $title_html = sprintf(
				'<%1$s %2$s>%3$s</%1$s>',
				Utils::validate_html_tag( $settings['header_size'] ),
				$this->get_render_attribute_string( 'title' ),
				esc_html( $content['list_title'] )
			);

         $content_html = '<div class="be-content">' . wp_kses_post( $content['list_content'] ) . '</div>';
		    
		    $image = '';

			if ( ! empty( $content['image']['id'] ) ) {

				$image_html = Group_Control_Image_Size::get_attachment_image_html(
					$content,
					'thumbnail', 
					'image'
				);

				if ( $image_html ) {
					$image = '<div class="timeline_pic pull-left">' . $image_html . '</div>';
				}
			} 


			else {
				$class = 'd-block';
			}

			if ( $is_one_sided ) {

				echo '<li class="timeline-right">';

			} else {

				$count++;

				if ( $count % 2 === 0 ) {
					echo '<li class="timeline-inverted">';
				} else {
					echo '<li class="timeline-right">';
				}
			}


			?> 
			<div class="tl-circ"></div>
		 <div class="timeline-panel">
		   <div class="tl-heading">
			 <div class="tl-content">

			    <?php if ( $is_one_sided ) : ?>

						<div class="be-desc">
							<?php echo $title_html;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped in $title_html ?>
						</div>

						<?php
						echo wp_kses(
							$image,
							[
								'img' => [
									'src'    => [],
									'title'  => [],
									'width'  => [],
									'height' => [],
									'class'  => [],
								],
								'div' => [
									'class' => [],
								],
							]
						);
						?>
						<?php echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped in $content_html ?>

			     <?php else : ?>

						<?php
						echo wp_kses(
							$image,
							[
								'img' => [
									'src'    => [],
									'title'  => [],
									'width'  => [],
									'height' => [],
									'class'  => [],
								],
								'div' => [
									'class' => [],
								],
							]
						);
						?>

						<div class="be-desc">
							<?php echo $title_html;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped in $title_html ?>
							<?php echo $content_html;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped in $content_html ?>
						</div>

					<?php endif; ?>

				</div>

			</div>
		</div>
			</li>
			<?php } ?> 
      </ul>

	<?php }


}
\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new TweTimelineWidget() );