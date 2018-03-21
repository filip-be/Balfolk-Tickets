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
	
	private static $bft_db_version = '0.1.11';
	
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
			$table_name = $wpdb->prefix . 'bft_event';

			$sql = "CREATE TABLE $table_name (
				PK_ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				Name varchar(200) NOT NULL,
				Timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				Status smallint(4) NOT NULL DEFAULT 1,
				
				PRIMARY KEY  (PK_ID)
			); ";
			
			$table_name = $wpdb->prefix . 'bft_status';

			$sql .= "CREATE TABLE $table_name (
				PK_ID bigint(20) UNSIGNED NOT NULL,
				Name varchar(200) NOT NULL,
				
				PRIMARY KEY  (PK_ID)
			); ";
			
			$table_name = $wpdb->prefix . 'bft_ticket';

			$sql .= "CREATE TABLE $table_name (
				PK_ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				FK_EventID bigint(20) UNSIGNED NOT NULL,
				FK_ProductID bigint(20) UNSIGNED NOT NULL,
				Timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				Status smallint(4) NOT NULL DEFAULT 1,
				
				PRIMARY KEY (PK_ID),
				KEY FK_EventID (FK_EventID),
				KEY FK_ProductID (FK_ProductID)				
			); ";
			
			$table_name = $wpdb->prefix . 'bfp_order_ticket';

			$sql .= "CREATE TABLE $table_name (
				PK_ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				FK_EventID bigint(20) UNSIGNED NOT NULL,
				FK_TicketID bigint(20) UNSIGNED NOT NULL,
				FK_OrderID bigint(20) UNSIGNED NOT NULL,
				Timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				Status smallint(4) NOT NULL DEFAULT 1,
				
				PRIMARY KEY (PK_ID),
				KEY FK_EventID (FK_EventID),
				KEY FK_TicketID (FK_TicketID),
				KEY FK_OrderID (FK_OrderID)
			); ";
			
			$table_name = $wpdb->prefix . 'bfp_status_order_ticket';

			$sql .= "CREATE TABLE $table_name (
				PK_ID bigint(20) UNSIGNED NOT NULL,
				Name varchar(200) NOT NULL,
				
				PRIMARY KEY  (PK_ID)
			); ";
			
			$table_name = $wpdb->prefix . 'bfp_log';

			$sql .= "CREATE TABLE $table_name (
				PK_ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				FK_EventID bigint(20) UNSIGNED NULL,
				FK_TicketID bigint(20) UNSIGNED NULL,
				FK_OrderID bigint(20) UNSIGNED NULL,
				FK_OrderTicketID bigint(20) UNSIGNED NULL,
				Timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				Level smallint(4) NOT NULL DEFAULT 1,
				Soure varchar(200) NOT NULL,
				Message longtext NOT NULL,
				
				PRIMARY KEY (PK_ID),
				KEY FK_EventID (FK_EventID),
				KEY FK_TicketID (FK_TicketID),
				KEY FK_OrderID (FK_OrderID),
				KEY FK_OrderTicketID (FK_OrderTicketID)
			); ";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			self::_fill_tables_data();
			
			update_option( "bft_db_version", self::$bft_db_version );
		}
	}
		
	protected static function _fill_tables_data() {
		global $wpdb;
		
		$statuses = array(
			["PK" => 1, "Name" => "Enabled"],
			["PK" => 2, "Name" => "Disabled"]
		);
		foreach($statuses as $status) {
			$sql = "INSERT INTO {$wpdb->prefix}bft_status (PK_ID, Name) VALUES (%d, %s) ON DUPLICATE KEY UPDATE Name = %s";
		
			$sql = $wpdb->prepare($sql,$status["PK"],$status["Name"],$status["Name"]);
		
			$wpdb->query($sql);
		}
		
		$statuses = array(
			["PK" => 1, "Name" => "New"],
			["PK" => 10, "Name" => "Checked"],
			["PK" => 11, "Name" => "Returned"],
			["PK" => 20, "Name" => "Unknown"],
		);
		foreach($statuses as $status) {
			$sql = "INSERT INTO {$wpdb->prefix}bfp_status_order_ticket (PK_ID, Name) VALUES (%d, %s) ON DUPLICATE KEY UPDATE Name = %s";
		
			$sql = $wpdb->prepare($sql,$status["PK"],$status["Name"],$status["Name"]);
		
			$wpdb->query($sql);
		}
	}
}

$BFT_database = BFT_database::getInstance();