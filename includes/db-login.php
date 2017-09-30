<?php

/* DB login - encrypt / decrypt
 */
 
class BFT_DBLogin {
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
			add_filter( 'woocommerce_settings_tabs_array', array($this, 'bft_tab'), 10, 1 );
			add_filter( 'woocommerce_settings_tabs_settings_tab_bft', array($this, 'bft_settings') );
			add_action( 'woocommerce_update_options_settings_tab_bft', array($this, 'bft_settings_update') );
		}
	}
	
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return BFT_DBLogin
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public static function bft_tab( $settings_tabs ) {
        $settings_tabs['settings_tab_bft'] = __( 'BalFolk Tickets', 'woocommerce-settings-tab-bft' );
        return $settings_tabs;
    }
	
	public function bft_settings() {
		woocommerce_admin_fields($this->get_bft_settings());
	}
	
	public function get_bft_settings() {
		$settings = array(
			'section_title' => array(
				'name'     => __( 'Section Title', 'woocommerce-settings-tab-bft' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wc_settings_tab_bft_section_title'
			),
			'login' => array(
				'name' => __( 'DB login', 'woocommerce-settings-tab-bft' ),
				'type' => 'text',
				'desc' => __( 'Login used in mobile app', 'woocommerce-settings-tab-bft' ),
				'id'   => 'wc_settings_tab_bft_login'
			),
			'password' => array(
				'name' => __( 'DB password', 'woocommerce-settings-tab-bft' ),
				'type' => 'password',
				'desc' => __( 'DB connection password', 'woocommerce-settings-tab-bft' ),
				'id'   => 'wc_settings_tab_bft_password'
			),
			// 'encryption_key' => array(
				// 'name' => __( 'Encryption key', 'woocommerce-settings-tab-bft' ),
				// 'type' => 'password',
				// 'desc' => __( 'Key used to encrypt the DB password', 'woocommerce-settings-tab-bft' ),
				// 'id'   => 'wc_settings_tab_bft_key'
			// ),
			'section_end' => array(
				 'type' => 'sectionend',
				 'id' => 'wc_settings_tab_bft_section_end'
			)
		);
		return apply_filters( 'wc_settings_tab_bft_settings', $settings );
	}
	
	public function bft_settings_update() {
		// // Encrypt password if
		// // 	POST data contains wc_settings_tab_bft_password
		// // 	POST data contains wc_settings_tab_bft_key
		// //	previous wc_settings_tab_bft_password is different than data in POST
		// if( !empty($_POST)
			// && !empty($_POST['wc_settings_tab_bft_password'])
			// && WC_Admin_Settings::get_option('wc_settings_tab_bft_password') !== null
			// && $_POST['wc_settings_tab_bft_password'] != WC_Admin_Settings::get_option('wc_settings_tab_bft_password')
			// && !empty($_POST['wc_settings_tab_bft_key']) ) {
			// $_POST['wc_settings_tab_bft_password'] = 
				// $this->encrypt_string($_POST['wc_settings_tab_bft_password'], $_POST['wc_settings_tab_bft_key']);
		// }
		
		$settings = $this->get_bft_settings();
		woocommerce_update_options( $settings );
	}
	
	// private function encrypt_string($data, $key) {
		// error_log("encrypting $data with key: $key");
	// }
}

$BFT_DBLogin = BFT_DBLogin::getInstance();