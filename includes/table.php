<?php

/* Database table base class
 */
 
class BFT_Table {
	
	protected $table_name; 	// "Table"
	
	protected $columns;		// array(
							//	["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
							//	,["Name" => "Name", "Type" => "varchar(200)", "Options" => "NOT NULL"]
							//)
							
	protected $keys;		// array("PRIMARY KEY  (PK_ID)", "KEY Name (Name)");
	
	public function __construct() {}
	
	// You must put each field on its own line in your SQL statement.
	// You must have two spaces between the words PRIMARY KEY and the definition of your primary key.
	// You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
	// KEY must be followed by a SINGLE SPACE then the key name then a space then open parenthesis with the field name then a closed parenthesis.
	// You must not use any apostrophes or backticks around field names.
	// Field types must be all lowercase.
	// SQL keywords, like CREATE TABLE and UPDATE, must be uppercase.
	// You must specify the length of all fields that accept a length parameter. int(11), for example.
	protected function CreateTable()
	{
		global $wpdb;
		
		$tab_name = $wpdb->prefix . $this->table_name;
		
		$sql = "CREATE TABLE $tab_name ( ";
		foreach($this->columns as $column)
		{
			$sql .= "{$column["Name"]} {$column["Type"]} {$column["Options"]}, \n";
		}
		
		foreach($this->keys as $key)
		{
			$sql .= "{$key}, \n";
		}
		$sql = rtrim(rtrim($sql),',');
		$sql .= ");";
				
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}