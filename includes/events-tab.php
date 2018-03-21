<?php

/* Include / remove events tab
 */
 
 class BFT_Event {
	 /**
	 * Singleton instance
	 */
	static $instance = false;
	
	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		if(is_admin()) {
			add_action('admin_menu', array(__CLASS__, '_create_events_menu'));
		}
		// add_action		( 'plugins_loaded', 					array( $this, 'textdomain'				) 			);
		// add_action		( 'admin_enqueue_scripts',				array( $this, 'admin_scripts'			)			);
		// add_action		( 'do_meta_boxes',						array( $this, 'create_metaboxes'		),	10,	2	);
		// add_action		( 'save_post',							array( $this, 'save_custom_meta'		),	1		);
		// front end
		// add_action		( 'wp_enqueue_scripts',					array( $this, 'front_scripts'			),	10		);
		// add_filter		( 'comment_form_defaults',				array( $this, 'custom_notes_filter'		) 			);
	}
	
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return BF_Tickets
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public static function _create_events_menu() {
		// make the main menu item
		add_menu_page(
			'Balfolk Events',
			'Balfolk Events',
			'view_woocommerce_reports',
			'balfolk-tickets',
			array( __CLASS__, '_events_page' ),
			'dashicons-format-audio',
			21
		);

		// // reports menu item
		// self::$menu_page_hooks['main'] = add_submenu_page(
			// self::$menu_slugs['main'],
			// __( 'Reports', 'opentickets-community-edition' ),
			// __( 'Reports', 'opentickets-community-edition' ),
			// 'view_woocommerce_reports',
			// self::$menu_slugs['main'],
			// array( __CLASS__, 'ap_reports_page' ),
			// false,
			// 21
		// );

		// // settings menu item
		// self::$menu_page_hooks['settings'] = add_submenu_page(
			// self::$menu_slugs['main'],
			// __( 'Settings', 'opentickets-community-edition' ),
			// __( 'Settings', 'opentickets-community-edition' ),
			// 'manage_options',
			// self::$menu_slugs['settings'],
			// array( __CLASS__, 'ap_settings_page' )
		// );
	}
	
	function _events_page()
	{
		// check user capabilities
		if (!current_user_can('view_woocommerce_reports')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "wporg_options"
				//settings_fields('wporg_options');
				// output setting sections and their fields
				// (sections are registered for "wporg", each field is registered to a specific section)
				//do_settings_sections('wporg');
				// output save settings button
				submit_button('Save Settings');
				?>
			</form>
		</div>
		<?php
	}
 }
 
 // Instantiate Balfolk tickets events tab class
$BFT_Event = BFT_Event::getInstance();