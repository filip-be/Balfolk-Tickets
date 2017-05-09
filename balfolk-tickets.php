<?php
/*
Plugin Name: Balfolk Tickets
Plugin URI:  https://github.com/filip-be/Balfolk-Tickets
Description: WordPress ticketing plugin for balfolk events
Version:     0.3.0
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
	
	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		// Check woocommerce
		if(!self::_is_active_woocommerce()) {
			add_action('admin_notices', array(__CLASS__, 'required_woocommerce'), 10);
			return;
		}
		
		// Load additional classes
		self::loadClasses('includes', array('db-schema.php', 'events-tab.php'));
				
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
	 * @return BFT
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	/**
	 * Load additional classes
	 */
	protected static function loadClasses($classesDir, $classes) {
		$pluginDir = trailingslashit(plugin_dir_path(__FILE__));
		$classesDir = trailingslashit($classesDir);
		
		foreach($classes as $class) {
			require_once($pluginDir.$classesDir.$class);
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
	
/// end class
}

// Instantiate Balfolk tickets class
$BFT = BFT::getInstance();