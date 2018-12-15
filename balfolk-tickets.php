<?php
/*
Plugin Name: Balfolk Tickets
Plugin URI:  https://github.com/filip-be/Balfolk-Tickets
Description: WordPress ticketing plugin for balfolk events
Version:     1.2.0
Author:      Filip Bieleszuk
Author URI:  https://github.com/filip-be
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

	Copyright 2017 Filip Bieleszuk
	
	{Plugin Name} is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	any later version.
	 
	{Plugin Name} is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	 
	You should have received a copy of the GNU General Public License
	along with {Plugin Name}. If not, see {License URI}.
*/

class BFT
{
	/**
	 * Singleton instance
	 */
	static $instance = false;
	
	protected $templates;
	
	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		// Check woocommerce
		if(!self::_is_active_woocommerce()) {
			add_action('admin_notices', array($this, 'required_woocommerce'), 10);
			return;
		}
		
		// Load additional classes
		self::loadClasses('includes', array('db-schema.php', 'events-tab.php', 'event-page.php', 'order.php', 'rest-orders-controller.php', 'rest-tickets-controller.php', 'rest-events-controller.php', 'rest-statistics-controller.php', 'product-stock.php'));
		
		// Load style for admin pages
		if(is_admin()) {
			add_action('admin_head', array($this, 'add_admin_styles'));
		}
		
		// Polylang strings translation
		add_action( 'plugins_loaded', array($this, 'polylang_register_strings'), 10, 0);
		
		// WooCommerce
		add_filter( 'woocommerce_get_item_data',  array($this, 'render_event_id_on_cart'), 10, 2);
		add_filter( 'woocommerce_get_cart_item_from_session', array($this, 'update_cart_item_from_session'), 10, 2);
		add_filter( 'woocommerce_checkout_fields', array($this, 'update_woocommerce_checkout_fields'), 10, 1);
		add_action( 'woocommerce_checkout_create_order_line_item', array($this, 'save_event_id_meta'), 10, 4 );
		add_action( 'woocommerce_thankyou', array($this, 'order_completed'), 10, 1);
		add_action( 'woocommerce_billing_fields', array($this, 'remove_address_fields'), 10, 1);
		add_action( 'woocommerce_after_order_notes', array($this, 'add_order_notes'), 10, 1);
		add_action( 'woocommerce_checkout_process', array($this, 'wc_checkout_process'), 10, 0);
		add_action( 'woocommerce_checkout_update_order_meta', array($this, 'wc_checkout_update_meta'), 10, 1);
		add_filter( 'woocommerce_order_item_name', array($this, 'wc_remove_permalink_order_table'), 10, 3 );
		add_filter( 'woocommerce_cart_item_permalink', '__return_false' );
		
		// Mails
		add_action( 'woocommerce_email_order_details', array( $this, 'email_order_details' ), 10, 4 );
		
		// Custom page template - http://www.wpexplorer.com/wordpress-page-templates-plugin/
		$this->templates = array();
		// Add a filter to the attributes metabox to inject template into the cache.
		// Add a filter to the wp 4.7 version attributes metabox
		add_filter( 'theme_page_templates', array( $this, 'add_new_template' ) );

		// Add a filter to the save post to inject out template into the page cache
		add_filter( 'wp_insert_post_data', array( $this, 'register_project_templates' ) );

		// Add a filter to the template include to determine if the page has our 
		// template assigned and return it's path
		add_filter( 'template_include', array( $this, 'view_project_template') );

		// Add your templates to this array.
		$this->templates = array('tickets-template.php' => 'Tickets page');
	}
	
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return BFT
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public static $classesDir;
	public static $pluginDir;
	/**
	 * Load additional classes
	 */
	protected static function loadClasses($classesDir, $classes) {
		self::$pluginDir = trailingslashit(plugin_dir_path(__FILE__));
		self::$classesDir = trailingslashit($classesDir);
		
		foreach($classes as $class) {
			require_once(self::$pluginDir.self::$classesDir.$class);
		}
		register_activation_hook(__FILE__, array('BFT_database', '_create_bft_tables'));
	}
	
	protected function _get_active_and_valid_plugins() {
		$plugins = array();
		$active_plugins = (array) get_option( 'active_plugins', array() );
	 
		// Check for hacks file if the option is enabled
		if ( get_option( 'hack_file' ) && file_exists( ABSPATH . 'my-hacks.php' ) ) {
			_deprecated_file( 'my-hacks.php', '1.5.0' );
			array_unshift( $plugins, ABSPATH . 'my-hacks.php' );
		}
	 
		if ( empty( $active_plugins ) || wp_installing() )
			return $plugins;
	 
		$network_plugins = is_multisite() ? wp_get_active_network_plugins() : false;
	 
		foreach ( $active_plugins as $plugin ) {
			if ( ! validate_file( $plugin ) // $plugin must validate as file
				&& '.php' == substr( $plugin, -4 ) // $plugin must end with '.php'
				&& file_exists( WP_PLUGIN_DIR . '/' . $plugin ) // $plugin must exist
				// not already included as a network plugin
				&& ( ! $network_plugins || ! in_array( WP_PLUGIN_DIR . '/' . $plugin, $network_plugins ) )
				)
			$plugins[] = $plugin;
		}
		return $plugins;
	}
	
	protected function _is_active_woocommerce() {
		return in_array( 'woocommerce/woocommerce.php', self::_get_active_and_valid_plugins());
	}
	
	public static function required_woocommerce() {
		?>
			<div class="notice notice-error">
				<h2><?php _e('Missing Required Plugin','balfolk-tickets') ?></h2>
				<p>
					Balfolk Tickets plugin requires <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> plugin to be installed and activated.
				</p>
			</div>
		<?php
	}
	
	public static function add_admin_styles() {
		$siteurl = get_option('siteurl');
		$url = plugins_url('admin/css/style.css', __FILE__);
		echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
	}
	
	public function render_event_id_on_cart( $cart_data, $cart_item ) {
		$custom_items = array();
		/* Woo 2.4.2 updates */
		if( !empty( $cart_data ) ) {
			$custom_items = $cart_data;
		}
		
		/*
		if( isset( $cart_item['bft-event-id'] ) ) {
			$custom_items[] = array( "name" => 'Event ID', "value" => $cart_item['bft-event-id'] );
		}
		*/
		return $custom_items;
	}
	
	public function update_cart_item_from_session( $cart_item, $values ) {
		if ( isset( $values['btf-event-id'] ) ){
			$cart_item['btf-event-id'] = $values['btf-event-id'];
		}
		return $cart_item;
	}
	
	public function save_event_id_meta( $item, $cart_item_key, $values, $order) {
		if(isset($values['bft-event-id'])) {
			$item->add_meta_data('_bft-event-id', $values['bft-event-id']);
		}
	}
	
	public function order_completed( $order_id ) {
		$bft_order = BFT_Order::GetByID($order_id);
	}
	
	public function email_order_details($order, $sent_to_admin, $plain_text, $email) {
		// Add QR code only to completed orders
		if(!$sent_to_admin && $order->get_status() == 'completed') {
			$order_hash = htmlspecialchars($order->get_order_key());
			echo '<p style="float: right"><img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl='.$order_hash.'&chld=Q|3"/></p>';
						
			echo '<p>'.pll__('BFTOrderCompletedText').'</p>';
			
			
			echo '<p>';
			echo pll__('BFTTicketGeneratorText');
			echo ' <a href="'.pll__('BFTTicketGeneratorURI').'?id='.$order->get_order_key().'">';
			echo pll__('BFTDownloadTicketText');
			echo '</a></p>';
		}
	}
	
	//remove some fields from billing form
	//ref - https://docs.woothemes.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	public function remove_address_fields( $fields = array() ) {
		unset($fields['billing_company']);
		unset($fields['billing_address_1']);
		unset($fields['billing_address_2']);
		unset($fields['billing_state']);
		unset($fields['billing_city']);
		unset($fields['billing_postcode']);
		
		return $fields;
	}
	
	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}
	
	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public function register_project_templates( $atts ) {
		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list. 
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		} 

		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $atts;
	}
	
	/**
	 * Checks if the template is assigned to the page
	 */
	public function view_project_template( $template ) {
		// Get global post
		global $post;

		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}

		// Return default template if we don't have a custom one defined
		if ( !isset( $this->templates[get_post_meta( 
			$post->ID, '_wp_page_template', true 
		)] ) ) {
			return $template;
		} 

		$file = plugin_dir_path(__FILE__). get_post_meta( 
			$post->ID, '_wp_page_template', true
		);

		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}

		// Return template
		return $template;
	}
	
	public function update_woocommerce_checkout_fields( $fields ) {
		if(isset($fields) && isset($fields['order'])) {
			$fields['order']['order_comments']['placeholder'] = pll__('Notes about your order.');
		}
		return $fields;
	}
	
	public function add_order_notes( $checkout ) {
		if(pll__('BFTAdditionalAgree') != 'BFTAdditionalAgree') {
			woocommerce_form_field( 'bft_additional_check', array(
				'type'          => 'checkbox',
				'class'         => array('input-checkbox'),
				'label'         => pll__('BFTAdditionalAgree'),
				'required'  => true,
			), $checkout->get_value( 'bft_additional_check' ));
		}
	}
	
	public function wc_checkout_process() {
		global $woocommerce;

		// Check if set, if its not set add an error.
		if (pll__('BFTAdditionalAgree') != 'BFTAdditionalAgree' && 
			(!isset($_POST['bft_additional_check']) || !$_POST['bft_additional_check']))
			wc_add_notice( pll__('BFTAdditionalAgreeMissing'), 'error' );
	}
	
	public function wc_checkout_update_meta( $order_id ) {
		if ($_POST['bft_additional_check']) 
			update_post_meta( $order_id, 'BFT Additional Check', 1 );
	}
	
	public function wc_remove_permalink_order_table($name, $item, $order ) {
		$name = $item['name'];
		return $name;
	}
	
	public function polylang_register_strings() {
		pll_register_string('woocommerce_order_notes_placeholder', 'Notes about your order.');
		pll_register_string('bft_ticket_generator_uri', 'BFTTicketGeneratorURI', 'Bal Folk Tickets');
		pll_register_string('bft_ticket_generator_text', 'BFTTicketGeneratorText', 'Bal Folk Tickets');
		pll_register_string('bft_ticket_generator_download_text', 'BFTDownloadTicketText', 'Bal Folk Tickets');
		pll_register_string('bft_ticket_order_completed_text', 'BFTOrderCompletedText', 'Bal Folk Tickets');
		pll_register_string('bft_ticket_ticket', 'BFTTicket', 'Bal Folk Tickets');
		pll_register_string('bft_ticket_sale_not_starte_message', 'BFTSaleNotStarted', 'Bal Folk Tickets');
		pll_register_string('bft_order_additional_agree', 'BFTAdditionalAgree', 'Bal Folk Tickets');
		pll_register_string('bft_order_additional_agree_missing', 'BFTAdditionalAgreeMissing', 'Bal Folk Tickets');
		pll_register_string('bft_product_not_available', 'BFTProductNotAvailable', 'Bal Folk Tickets');
		pll_register_string('bft_product_add_to_card', 'BFTAddToCart', 'Bal Folk Tickets');
	}
	
/// end class
}

// Instantiate Balfolk tickets class
$BFT = BFT::getInstance();