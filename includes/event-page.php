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
		add_shortcode('bft_event_tickets', array($this, 'event_tickets'));
		add_action( 'woocommerce_add_cart_item_data', array($this, 'add_event_id_to_product'), 10, 1);
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
	
	function add_event_id_to_product( $cart_item_data ) {
		if( isset( $_REQUEST['bft-event-id'] ) ) {
			$cart_item_data[ 'bft-event-id' ] = $_REQUEST['bft-event-id'];
		}
		return $cart_item_data;
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
		$currentDate = new DateTime();
		$saleStartDate = new DateTime($event->SaleStartDate);
		
		if($currentDate < $saleStartDate)
		{
			$saleStartDate->setTimeZone(new DateTimeZone('Europe/Warsaw'));
			$res .= '<div class="bft-event-message">'.pll__('BFTSaleNotStarted').' '.$event->SaleStartDate.' UTC ('.$saleStartDate->format('Y-m-d H:i:s').' CET).</div>';
		}
		else
		{
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
				$originalProductId = pll_get_post($product->get_id(), pll_default_language('slug'));
				$originalProduct = new WC_Product($originalProductId);
				$stillAvailable = true;
				if($originalProduct->get_manage_stock() && $originalProduct->get_stock_quantity() == 0)
				{
					$stillAvailable = false;
				}
				$product_price = $product->get_price().' '.get_woocommerce_currency_symbol();
				$res .= '
				<form class="bft-et-tr cart" method="post" enctype="multipart/form-data" action="">
					<input type="hidden" name="bft-event-id" value="'.$event->ID.'"/>
					<div class="bft-et-td prod-thumb">'.$product->get_image().'</div>
					<div class="bft-et-td prod-title">'.$product->get_name();
				if($a['show-description'] == 1) {
					$res .= '<p class="prod-title-slug bft-p-after">'.nl2br($product->get_short_description()).'</p>';
				}
				$res .= '</div>';
				if(self::is_open_price_product($originalProduct))
				{
					$res .= '<div class="bft-et-td prod-price bft-open-price"><p class="bft-p-bold">'.__('Price', 'woocommerce').'</p><p class="bft-p-after">';
					
					global $post;
					$originalPost = $post;
					$post = get_post($originalProductId, OBJECT);
					
					ob_start();
					do_action('woocommerce_before_add_to_cart_button');
					$res .= ob_get_contents();
					ob_end_clean();
					
					$post = $originalPost;
					
					$res .= '</p></div>';
				}
				else
				{
					$res .= '<div class="bft-et-td prod-price"><p class="bft-p-bold">'.__('Price', 'woocommerce').'</p><p class="bft-p-after">'.$product_price.'</p></div>';
				}
				
				$res .= '<div class="bft-et-td prod-qty"><p class="bft-p-bold">'.__('Quantity', 'woocommerce').'</p>'.woocommerce_quantity_input( array('min_value' => 1), $product, false ).'</div>
				<div class="bft-et-td prod-total"><p class="bft-p-bold">'.__('Total', 'woocommerce').'</p><p class="bft-total bft-p-after" data-symbol="'.get_woocommerce_currency_symbol().'" data-price="'.$product->get_price().'">'.$product_price.'</p></div>';
					
				if($stillAvailable)
				{
					$res .= '<div class="bft-et-td prod-cart"><button type="submit" name="add-to-cart" value="'.$originalProductId.'" class="single_add_to_cart_button button alt">'.pll__('BFTAddToCart').'</button></div>';
				}
				else
				{
					$res .= '<div class="bft-et-td prod-cart">'.pll__('BFTProductNotAvailable').'</div>';
				}
				$res .= '</form>';
			}
			$res .= "</div>";	//bft-et-body
			$res .= '<div class="bft-et-foot"></div>';
			$res .= "</div>";	//bft-event-tickets
		}
		return $res;
	}
	
	/**
	 * Product Open Pricing for WooCommerce - Core Class
	 *
	 * @version 1.1.1
	 * @since   1.0.0
	 * @author  Algoritmika Ltd.
	 * is_open_price_product.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	protected static function is_open_price_product( $_product ) {
		return ( 'yes' === get_post_meta( self::get_product_or_variation_parent_id( $_product ), '_' . 'alg_wc_product_open_pricing_enabled', true ) );
	}
	
	/**
	 * get_product_or_variation_parent_id.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @todo    (maybe) just product id (i.e. no parent for variation)
	 */
	protected static function get_product_or_variation_parent_id( $_product ) {
		return ( $_product->is_type( 'variation' ) ? $_product->get_parent_id() : $_product->get_id() );
	}
 }
 
 // Instantiate Balfolk tickets events page class
$BFT_EventPage = BFT_EventPage::getInstance();