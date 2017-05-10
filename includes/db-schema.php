<?php

/* Include / remove DB schema
 */
 
class BFT_database {
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
	}
	
	private static $bft_db_version = '0.1.21';
	
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return BFT_database
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public static function _create_bft_tables() {
		global $wpdb;
		$installed_ver = get_option( "bft_db_version" );
		error_log('_create_bft_tables');

		if ( !isset($installed_ver) || $installed_ver != self::$bft_db_version ) {
			
			require_once(BFT::$pluginDir.BFT::$classesDir.'event.php');
			(new BFT_Event)->CreateTable();
			require_once(BFT::$pluginDir.BFT::$classesDir.'status.php');
			(new BFT_Status)->CreateTable();
			require_once(BFT::$pluginDir.BFT::$classesDir.'ticket.php');
			(new BFT_Ticket)->CreateTable();
			require_once(BFT::$pluginDir.BFT::$classesDir.'order_ticket.php');
			(new BFT_OrderTicket)->CreateTable();
			require_once(BFT::$pluginDir.BFT::$classesDir.'status_order_ticket.php');
			(new BFT_StatusOrderTicket)->CreateTable();
			require_once(BFT::$pluginDir.BFT::$classesDir.'log.php');
			(new BFT_Log)->CreateTable();
						
			update_option( "bft_db_version", self::$bft_db_version );
		}
	}
}

$BFT_database = BFT_database::getInstance();