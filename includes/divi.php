<?php

if (!defined('ABSPATH')) die;

/**
 * Divi functionality.
 *
 * Provides Divi functionality.
 *
 * @since      1.0.0
 * @package    LogicHop
 */

class LogicHop_Divi {

	/**
	 * Logic Hop
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $logichop    Logic Hop class
	 */
	 private $logichop;

	 /**
 	 * Logic Hop Admin class
 	 *
 	 * @since    1.0.0
 	 * @access   private
 	 * @var      object    $public    Logic Hop Admin class
 	 */
 	private $admin = null;

	/**
	 * Logic Hop Public class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $public    Logic Hop Public class
	 */
	private $public = null;

	/**
	 * Divi Modules
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $modules    Array of Divi module names
	 */
	private $modules;

	/**
	 * Divi front-end builder activce
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      boolean    $visual    Is the Divi visual builder active
	 */
	private $visual = false;

	/**
	 * Logic Hop conditions
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $conditions    Logic Hop Condition titles and slugs
	 */
	private $conditions = array( '' => 'Always Display' );

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    	1.0.0
	 * @param       object    $logic	LogicHop_Core functionality & logic.
	 */
	public function __construct () {
		$this->modules = $this->get_modules();
		$this->add_hooks_filters();
	}

	/**
	 * Add actions
	 *
	 * @since    	1.0.0
	 */
	public function add_hooks_filters () {
		add_action( 'logichop_after_admin_hooks', array( $this, 'logichop_admin' ), 10, 1 );
		add_action( 'logichop_after_public_hooks', array( $this, 'logichop_public' ), 10, 1 );
		add_action( 'logichop_after_plugin_init', array( $this, 'logichop_plugin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// add_action( 'wp_footer', array( $this, 'editor_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'logichop_public_enqueue_scripts', array( $this, 'dequeue_scripts' ), 10, 2 );
		add_filter( 'et_builder_main_tabs', array( $this, 'add_tab' ) );

		if ( $this->modules ) {
			foreach ( $this->modules as $module ) {
				add_action( "et_pb_all_fields_unprocessed_{$module}", array( $this, 'add_section_setting' ) );
			}
		}
		add_action( 'et_module_shortcode_output', array( $this, 'render_et_module' ), 10, 3 );
	}

	/**
	 * Logic Hop plugin init complete
	 *
	 * @since    	1.0.0
	 */
	public function logichop_plugin_init ( $logichop ) {
		$this->logichop = $logichop;
	}

	/**
	 * Logic Hop Admin init complete
	 * Enqueue & Render Logic Hop Tool Pallete
	 *
	 * @since    	1.0.0
	 */
	public function logichop_admin ( $admin ) {
		$this->admin = $admin;

		// $conditions = $admin->conditions_get( true );
		//
		// if ( $conditions ) {
		// 	foreach ( $conditions as $c ) {
		// 		$this->conditions [ $c['slug'] ] = $c['name'];
		// 	}
		// }
		//
		// add_action( 'divi_extensions_init',
		// 	function () use ( $admin ) {
		// 		$admin->enqueue_styles( 'post.php' );
		// 		$admin->enqueue_scripts( 'post.php' );
		// 	}
		// );
		// add_action( 'et_fb_enqueue_assets',
		// 	function () use ( $admin ) {
		// 		$conditions = $admin->conditions_get( true );
		//
	 	// 		if ( $conditions ) {
	 	// 			foreach ( $conditions as $c ) {
	 	// 				$this->conditions [ $c['slug'] ] = $c['name'];
	 	// 			}
	 	// 		}
		//
		// 		// $admin->enqueue_styles( 'post.php' );
		// 		// $admin->enqueue_scripts( 'post.php' );
		// 		$admin->editor_shortcode_modal( true );
		// 	}
		// );
	}

	/**
	 * Set Conditions
	 *
	 * @since    	1.0.0
	 */
	public function logichop_get_conditions () {

		if ( ! $this->admin ) {
			return false;
		}

		if ( ! wp_doing_ajax() || count( $this->conditions ) > 1 ) {
				return;
		}

		$conditions = $this->admin->conditions_get( true );

		if ( $conditions ) {
			foreach ( $conditions as $c ) {
				$this->conditions [ $c['slug'] ] = $c['name'];
			}
		}
	}

	/**
	 * Logic Hop Public init complete
	 *
	 * @since    	1.0.0
	 */
	public function logichop_public ( $public ) {
		$this->public = $public;
	}

	/**
	 * Logic Hop enqueue admin scripts
	 *
	 * @since    	1.0.0
	 */
	public function enqueue_admin_scripts ( $hook ) {
		global $post_type;

		if ( strpos( $hook, 'logichop' ) !== false || strpos( $post_type, 'logichop' ) !== false ) {
			wp_enqueue_script( 'logichop-divi', plugin_dir_url( __FILE__ ) . 'divi-cache-clear.js' );
		}
	}

	/**
	 * Render Divi module
	 *
	 * @since    	1.0.0
	 */
	public function render_et_module ( $output, $render_slug, $module ) {

		if ( wp_doing_ajax() ) {
			return $output;
		}

		if ( isset( $_GET['et_fb'] ) && $_GET['et_fb'] == 1 ) {
			return $output;
		}

		$condition = ( isset( $module->props ) && isset( $module->props['logichop_condition'] ) ) ? $module->props['logichop_condition'] : false;
		$condition_not = ( isset( $module->props ) && isset( $module->props['logichop_condition_not'] ) && $module->props['logichop_condition_not'] == 'not' ) ? '!' : '';

		if ( $condition ) {
			if ( $this->public ) {
				$content = sprintf( '{%% if condition: %s%s %%}%s{%% endif %%}',
											$condition_not,
											$condition,
											$output
										);
				return $this->public->content_filter( $content );
			}
		}

		return $output;
	}

	/**
	 * Register the Logic Hop tab in the builder.
	 *
	 * @filter et_builder_main_tabs
	 *
	 * @since  1.1.0
	 *
	 * @param array $tabs List of tabs to display in the Visual Builder.
	 *
	 * @return array Modified list of tabs.
	 */
	public function add_tab( $tabs ) {
		$tabs['Logic Hop'] = esc_html__( 'Logic Hop', 'logichop' );

		return $tabs;
	}

	/**
	* Add Logic Hop settings to modules
	* Javascript cache clear: for(var prop in localStorage)localStorage.removeItem(prop);
	*
	* @since    	1.0.0
	*/
	public function add_section_setting ( $fields_unprocessed ) {

		$this->logichop_get_conditions();

		$fields = array();
		$fields['logichop_condition'] = array (
			'label' => 'Logic Hop Condition',
			'type' => 'select',
			'option_category' => 'configuration',
			'options' => $this->conditions,
			'tab_slug' => 'Logic Hop',
			//'toggle_slug' => 'content',
		);
		$fields['logichop_condition_not'] = array (
			'label' => 'Display When',
			'type' => 'select',
			'option_category' => 'configuration',
			'options' => array (
				'met' => 'Condition Met',
				'not' => 'Condition Not Met'
			),
			'tab_slug' => 'Logic Hop',
			//'toggle_slug' => 'content',	//visibility
		);
		return array_merge( $fields_unprocessed, $fields );
	}

	/**
	 * Build Divi module list
	 *
	 * @since    	1.0.0
	 * @return	array 	Divi module names
	 */
	public function get_modules () {
		$modules = array ( 		'et_pb_section','et_pb_row','et_pb_row_inner','et_pb_column','et_pb_accordion','et_pb_accordion_item','et_pb_audio','et_pb_counters','et_pb_counter','et_pb_blog','et_pb_blurb','et_pb_button','et_pb_circle_counter','et_pb_code','et_pb_comments','et_pb_contact_form','et_pb_contact_field','et_pb_countdown_timer','et_pb_cta','et_pb_divider','et_pb_filterable_portfolio','et_pb_fullwidth_code','et_pb_fullwidth_header','et_pb_fullwidth_image','et_pb_fullwidth_map','et_pb_fullwidth_menu','et_pb_fullwidth_portfolio','et_pb_fullwidth_post_slider','et_pb_fullwidth_post_title','et_pb_fullwidth_slider','et_pb_gallery','et_pb_image','et_pb_login','et_pb_map','et_pb_map_pin','et_pb_number_counter','et_pb_portfolio','et_pb_post_slider','et_pb_post_title','et_pb_post_nav','et_pb_pricing_tables','et_pb_pricing_table','et_pb_search','et_pb_shop','et_pb_sidebar','et_pb_signup','et_pb_signup_custom_field','et_pb_slider','et_pb_slide','et_pb_social_media_follow','et_pb_social_media_follow_network','et_pb_tabs','et_pb_tab','et_pb_team_member','et_pb_testimonial','et_pb_text','et_pb_toggle','et_pb_video','et_pb_video_slider','et_pb_video_slider_item' );

		$modules = apply_filters( 'logic_hop_divi_module_list', $modules );

		return $modules;
	}

	/**
	 * Enqueue and render Logic Hop tool palette
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts () {
		if ( $this->frontend_editor() ) {
			//$this->admin->enqueue_styles( 'post.php' );
			//wp_enqueue_style( 'logichop-divi', plugin_dir_url( __FILE__ ) . 'palette.css' );
 			//$this->admin->enqueue_scripts( 'post.php' );
			wp_enqueue_script( 'logichop-divi', plugin_dir_url( __FILE__ ) . 'editor.js', array( 'jquery' ) );
		}
	}

	/**
	 * Dequeue scripts during preview
	 *
	 * @since    1.0.0
	 */
	public function dequeue_scripts ( $hook, $post_type ) {
		if ( $this->frontend_editor() ) {
			wp_dequeue_script( 'logichop-generate_preview_data' );

			do_action( 'logichop_divi_dequeue', $hook, $post_type );
		}
	}

	/**
	 * Render Logic Hop tool palette after content
	 *
	 * @since    	1.0.0
	 */
	public function editor_content () {
		if ( $this->admin && $this->frontend_editor() ) {
			$this->admin->editor_shortcode_modal( true );
		}
	}

	/**
	 * Is frontend editor active
	 *
	 * @since    	1.0.0
	 */
	public function frontend_editor () {
		if ( isset( $_GET['et_fb'] ) && $this->admin ) {
			return true;
		}
		return false;
	}
}
