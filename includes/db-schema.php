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
	
	private static $bft_db_version = '0.1.36';
	
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

		if ( !isset($installed_ver) || $installed_ver != self::$bft_db_version ) {
			require_once(BFT::$pluginDir.BFT::$classesDir.'event.php');	
			require_once(BFT::$pluginDir.BFT::$classesDir.'status.php');
			require_once(BFT::$pluginDir.BFT::$classesDir.'ticket.php');
			require_once(BFT::$pluginDir.BFT::$classesDir.'order_ticket.php');
			require_once(BFT::$pluginDir.BFT::$classesDir.'status_order_ticket.php');
			require_once(BFT::$pluginDir.BFT::$classesDir.'log.php');
			
			(new BFT_Log)->CreateTable();
			(new BFT_Event)->CreateTable();
			(new BFT_Status)->CreateTable();
			(new BFT_Ticket)->CreateTable();
			(new BFT_OrderTicket)->CreateTable();
			(new BFT_StatusOrderTicket)->CreateTable();
						
			update_option( "bft_db_version", self::$bft_db_version );
			BFT_Log::Info(__CLASS__, 'Database upgraded - ' . self::$bft_db_version);
		}
	}
}

$BFT_database = BFT_database::getInstance();