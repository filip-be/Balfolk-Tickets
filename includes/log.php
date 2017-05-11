<?php

/* Log class
 */
require_once 'table.php';

abstract class BFT_LogLevel
{
	const Trace	= 1;
	const Debug	= 2;
	const Info	= 3;
	const Warn	= 4;
	const Error	= 5;
	const Fatal	= 6;
}

class BFT_Log extends BFT_Table {
	
	public function __construct()
	{
		parent::__construct();
		$this->table_name = "bft_log";
		$this->columns = array (
			["Name" => "PK_ID", "Type" => "bigint(20)", "Options" => "UNSIGNED NOT NULL AUTO_INCREMENT"]
			,["Name" => "Timestamp", "Type" => "datetime", "Options" => "DEFAULT CURRENT_TIMESTAMP NOT NULL"]
			,["Name" => "Level", "Type" => "smallint(4)", "Options" => "NULL"]
			,["Name" => "Source", "Type" => "varchar(200)", "Options" => "NULL"]
			,["Name" => "Message", "Type" => "longtext", "Options" => "NULL"]
		);
		
		$this->keys = array (
			"PRIMARY KEY  (PK_ID)"
		);
	}
	
	public function CreateTable()
	{
		parent::CreateTable();
	}
	
	protected static function LogMessage($level, $source, $message)
	{
		global $wpdb;
		$wpdb->insert
		(
			$wpdb->prefix . 'bft_log'
			,array
			(
				'Level' => $level
				,'Source' => $source
				,'Message' => $message
				
			)
			,array
			(
				'%d'
				,'%s'
				,'%s'
			)
		);
	}
	
	public static function Trace($source, $message)
	{
		self::LogMessage(BFT_LogLevel::Trace, $source, $message);
	}
	
	public static function Debug($source, $message)
	{
		self::LogMessage(BFT_LogLevel::Debug, $source, $message);
	}
	
	public static function Info($source, $message)
	{
		self::LogMessage(BFT_LogLevel::Info, $source, $message);
	}
	
	public static function Warn($source, $message)
	{
		self::LogMessage(BFT_LogLevel::Warn, $source, $message);
	}
	
	public static function Error($source, $message)
	{
		self::LogMessage(BFT_LogLevel::Error, $source, $message);
	}
	
	public function Fatal($source, $message)
	{
		$this->LogMessage(BFT_LogLevel::Fatal, $source, $message);
	}
}