<?php

/* Include / remove events tab
 */
 
class BFT_EventTab {
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
		// Check user capabilities
		if (!current_user_can('view_woocommerce_reports')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		// Variable for the fields
		$hidden_field_name = 'bft_events_tab_hidden_submit';
		$data_field_name = 'bft_event_name';

		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
			// Read their posted value
			$event_name = $_POST[$data_field_name];

			// Create new event
			
			// Put a "settings saved" message on the screen
			?><div class="updated"><p><strong><?php _e('Event added.', 'bft_event' ); ?></strong></p></div><?php
		}

		// Now display the settings editing screen?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
			<form name="New_Event" method="POST" action="">
				<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
				<p>
					<?php _e("Event name:", 'menu-test' ); ?> 
					<input type="text" name="<?php echo $data_field_name; ?>" value="" size="20">
					<input type="submit" name="Submit" class="button-primary" value="Add new event" />
				</p>
			</form>
			<hr />
			<?// Display existing events - EDIT / REMOVE
				
			?>
		</div>
		<?php
	}
 }
 
 // Instantiate Balfolk tickets events tab class
$BFT_EventTab = BFT_EventTab::getInstance();