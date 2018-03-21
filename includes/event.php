<?php

/* Event class
 */
require_once 'table.php';

class BFT_Event extends BFT_Table {
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = "bft_event";
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "Name", "Type" => "varchar(200)", "Options" => "NOT NULL"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Status", "Type" => "smallint(4)", "Options" => "NOT NULL DEFAULT 1"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
}