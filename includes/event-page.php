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
		// add_action('wp_enqueue_scripts', BFT::$pluginDir.'public/css/event-page.css');
		wp_enqueue_style('bftEventPageStyle', plugins_url('public/css/event-page.css', dirname(__FILE__)));
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
		$res = '';
		wp_enqueue_script('bftEventPageScript', plugins_url('public/js/event-tickets.js', dirname(__FILE__)));
		
		wc_print_notices();
		
		$a = shortcode_atts( array(
			'id' => 0
			,'show-description' => 1
		), $atts );
		
		$event = BFT_Event::GetByID($a['id']);
		
		$res .= '<div class="bft-event-tickets">';
		$res .= '<div class="bft-et-head">
					<div class="bft-et-tr">
						<div class="bft-et-td prod-thumb">&nbsp;</div>
						<div class="bft-et-td prod-title">'.__('Item', 'woocommerce').'</div>
						<div class="bft-et-td prod-price">'.__('Price', 'woocommerce').'</div>
						<div class="bft-et-td prod-qty">'.__('Quantity', 'woocommerce').'</div>
						<div class="bft-et-td prod-total">'.__('Total', 'woocommerce').'</div>
						<div class="bft-et-td prod-cart">&nbsp;</div>
					</div>
				</div>';
		
		$res .= '<div class="bft-et-body">';
		foreach($event->GetProducts() as $product) {
			$product_price = $product->get_price().' '.get_woocommerce_currency_symbol();
			$res .= '
			<form class="bft-et-tr cart" method="post" enctype="multipart/form-data" action="">
				<div class="bft-et-td prod-thumb">'.$product->get_image().'</div>
				<div class="bft-et-td prod-title">'.$product->get_name();
			if($a['show-description'] == 1) {
				$res .= '<p class="prod-title-slug bft-p-after">'.$product->get_short_description().'</p>';
			}
			$res .= '</div>
				<div class="bft-et-td prod-price"><p class="bft-p-bold">'.__('Price', 'woocommerce').'</p><p class="bft-p-after">'.$product_price.'</p></div>
				<div class="bft-et-td prod-qty"><p class="bft-p-bold">'.__('Quantity', 'woocommerce').'</p>'.woocommerce_quantity_input( array('min_value' => 1), $product, false ).'</div>
				<div class="bft-et-td prod-total"><p class="bft-p-bold">'.__('Total', 'woocommerce').'</p><p class="bft-total bft-p-after" data-symbol="'.get_woocommerce_currency_symbol().'" data-price="'.$product->get_price().'">'.$product_price.'</p></div>
				<div class="bft-et-td prod-cart"><button type="submit" name="add-to-cart" value="'.$product->get_id().'" class="single_add_to_cart_button button alt">'.$product->add_to_cart_text().'</button></div>
			</form>';
		}
		$res .= "</div>";	//bft-et-body
		$res .= '<div class="bft-et-foot"></div>';
		$res .= "</div>";	//bft-event-tickets
		
		return "{$res}<br/>Event tickets {$a['id']}";
	}
 }
 
 // Instantiate Balfolk tickets events page class
$BFT_EventPage = BFT_EventPage::getInstance();