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
			,'show-description' => 1
		), $atts );
		
		$event = BFT_Event::GetByID($a['id']);
		$res = '<table class="bft-event-tickets">';
		$res .= '<thead></thead>';
		
		$res .= '<tbody>';
		foreach($event->GetProducts() as $product) {
			$res .= ' 
			<tr>
				<td class="prod-thumb">'.$product->get_image().'</td>
				<td class="prod-title">'.$product->get_name();
			if($a['show-description'] == 1) {
				$res .= '<span class="prod-title-slug">'.$product->get_short_description().'</span>';
			}
			$res .= '</td>
				<td class="prod-qty">QUANTITY</td>
				<td class="prod-price">'.$product->get_price().' '.get_woocommerce_currency_symbol().'</td>
				<td class="prod-total">TOTAL</td>
				<td class="prod-cart"><a class="prod-add-to-cart" href="'.$product->add_to_cart_url().'"/>'.$product->add_to_cart_text().'</a></td>
			</tr>
			';
		}
		$res .= "</tbody>";
		$res .= "</table>";
		
		return "{$res}<br/>Event tickets {$a['id']}";
	}
 }
 
 // Instantiate Balfolk tickets events page class
$BFT_EventPage = BFT_EventPage::getInstance();