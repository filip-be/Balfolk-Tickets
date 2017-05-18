<?php

/* Display event page
 */
require_once 'event.php';
 
class BFT_EventPage {
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
		add_shortcode('bft_event_tickets', array(__CLASS__, 'event_tickets'));
	}
	
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return BF_Tickets
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public static function event_tickets($atts) {
		$a = shortcode_atts( array(
			'id' => 0
		), $atts );
		
		return "Event tickets {$a['id']}";
	}
 }
 
 // Instantiate Balfolk tickets events page class
$BFT_EventPage = BFT_EventPage::getInstance();