<?php

/* Include / remove events tab
 */
require_once 'event.php';
require_once 'event-tickets-list.php';
 
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
			'balfolk-events',
			array( __CLASS__, '_events_page' ),
			'dashicons-format-audio',
			21
		);
		
		add_submenu_page(
			'balfolk-events'
			,'Edit event'
			,null
			,'view_woocommerce_reports'
			,'balfolk-event'
			,array( __CLASS__, '_event_page')
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
	
	public static function _events_page()
	{
		// Check user capabilities
		if (!current_user_can('view_woocommerce_reports')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		$field_event_name = 'bft_event_name';
		if( isset($_POST[$field_event_name]) && strlen($_POST[$field_event_name]) > 0) {
			// Create new event
			BFT_Event::Create($_POST[$field_event_name]);
			
			// Put a "settings saved" message on the screen
			?><div class="updated"><p><strong><?php _e('Event added.', 'bft_event' ); ?></strong></p></div><?php
		}
		else if( isset($_POST['ArchiveEventID']) && strlen($_POST['ArchiveEventID']) > 0) {
			// Archive (disable) event
			BFT_Event::Archive($_POST['ArchiveEventID']);
			
			// Put a "settings saved" message on the screen
			?><div class="updated"><p><strong><?php _e('Event archived.', 'bft_event' ); ?></strong></p></div><?php
		}

		// Now display the settings editing screen?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
			<form name="New_Event" method="POST" action="">
				<p>
					<?php _e("Event name:", 'bft_events' ); ?> 
					<input type="text" name="<?= $field_event_name; ?>" value="" size="20">
					<input type="submit" name="Submit" class="button-primary" value="Add new event" />
				</p>
			</form>
			<hr />
		<?php
			$events = BFT_Event::GetEvents();
			if(isset($events) && sizeof($events) > 0)
			{ 
		?>
				<table class="tab-events">
					<thead>
						<tr>
							<td>Manage events</td>
							<td></td>
							<td></td>
						</tr>
					</thead>
					<tbody>
				<?	// Display existing events - EDIT / Archive
					foreach($events as $event)
					{ 
				?>
						<tr>
							<td><?= $event->Name; ?></td>
							<td>
								<form name="Edit_Event" method="POST" action="<?= menu_page_url('balfolk-event', false); ?>">
									<input type="hidden" name="EventID" value="<?= $event->ID; ?>"/>
									<input type="submit" class="button-secondary" value="Edit event"/>
								</form>
							</td>
							<td>
								<form name="Edit_Event" method="POST" action="">
									<input type="hidden" name="ArchiveEventID" value="<?= $event->ID; ?>"/>
									<input type="submit" class="button-primary bft-archive-event" value="Archive event"/>
								</form>
							</td>
						</tr>
				<?
					}
				?>
					</tbody>
				</table>
				<div id="confirmation-dialog"></div>
				<script>
					jQuery(".bft-archive-event").on("click", function() {
						return confirm("Do you really want to delete this event?");
					});
				</script>
		<?php
			}
			else
			{
				echo "<p>There is no events yet. Please create new event using field above.</p>";
			}
		?>
		</div>
		<?php
	}
	
	public static function _event_page()
	{
		// Check user capabilities
		if (!current_user_can('view_woocommerce_reports')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		if(!isset($_POST["EventID"])) {
			wp_die(__('This page can be entered only by selecting event in "Balfolk events" page.'));
		}

		$EventID = $_POST["EventID"];
		$event = BFT_Event::GetByID($_POST["EventID"]);
		
		if( false ) {
			// Put a "settings saved" message on the screen
			?><div class="updated"><p><strong><?php _e('Event added.', 'bft_event' ); ?></strong></p></div><?php
		}

		// Now display the settings editing screen?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
			<form name="EditEvent" method="POST" action="">
				<input type="hidden" name="EventID" value="<?= $EventID ?>">
				<p>
					<?php _e("Event name:", 'bft_event' ); ?> 
					<input type="text" name="bft_name" value="<?= $event->Name ?>" size="20">
				</p>
				<p><input type="submit" name="Submit" class="button-primary" value="Update" /></p>
			</form>
			<hr />
			<?// Display existing events - EDIT / REMOVE
				$EventTickets = new BFT_Event_Tickets_List();
				$EventTickets->prepare_items($EventID, false);
				$EventTickets->display();
				// $args = array( 'post_type' => 'product', 'posts_per_page' => 10 );
				// $loop = new WP_Query( $args );

				// while ( $loop->have_posts() ) : $loop->the_post(); 
					// global $product; 
					// echo '<br /><a href="'.get_permalink().'">' . woocommerce_get_product_thumbnail().' '.get_the_title().'</a>';
				// endwhile; 


				// wp_reset_query(); 
			?>
		</div>
		<?php
	}
 }
 
 // Instantiate Balfolk tickets events tab class
$BFT_EventTab = BFT_EventTab::getInstance();