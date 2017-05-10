<?php

/* Status class
 */
require_once 'table.php';

class BFT_Status extends BFT_Table {
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = "bft_status";
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "Name", "Type" => "varchar(200)", "Options" => "NOT NULL"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
		$this->FillData();
	}
	
	protected function FillData()
	{
		global $wpdb;
		$statuses = array(
			["PK" => 1, "Name" => "Enabled"],
			["PK" => 2, "Name" => "Disabled"]
		);
		
		// change to OO
		foreach($statuses as $status) {
			$sql = "INSERT INTO {$wpdb->prefix}{$this->table_name} (PK_ID, Name) VALUES (%d, %s) ON DUPLICATE KEY UPDATE Name = %s";
			$sql = $wpdb->prepare($sql,$status["PK"],$status["Name"],$status["Name"]);
			$wpdb->query($sql);
		}
	}
}