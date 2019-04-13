<?php

/* Product stock management
 */
require_once 'log.php';

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
 
class BFT_ProductStock {
	/**
	 * Singleton instance
	 */
	static $instance = false;
	
	const STOCK_REDUCE = 'reduce';
	const STOCK_INCREASE = 'increase';
	
	public function __construct()
	{
		// sync stock
        add_action('woocommerce_reduce_order_stock', array($this, 'reduceStock'));
		add_action('woocommerce_restore_order_stock', array($this, 'increaseStock'), 10, 1);
	}
	
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return BFT_ProductStock
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public function reduceStock($order)
	{
		// Reduce stock for each item in order
		foreach($order->get_items() as $item)
		{
			$this->changeStock($item, $order, self::STOCK_REDUCE);
		}
	}
	
	public function increaseStock($order)
	{
		// Restore stock for each item in order
		foreach($order->get_items() as $item)
		{
			$this->changeStock($item, $order, self::STOCK_INCREASE);
		}
	}
	
	protected function changeStock($item, $order, $action)
	{
		// Do nothing if Polylang is not installed
		if( ! function_exists('pll_get_post_language') ) {
			return;
		}
		
		// Single product
		$product = $item->get_product();

		if (! $product || ! $product->managing_stock() ) {
			return;
		}

		// Change quantity
		$qty = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
		
		// Stock operation
		$stockOperation = $action == self::STOCK_REDUCE ? 'decrease' : 'increase';
		
		// Get current product language
		$currentLanguage = pll_get_post_language($product->get_id());
		
		// All languages
		$allLanguages = pll_languages_list(array('slug'));
		
		foreach($allLanguages as $lang) {
			// Skip existing
			if($lang == $currentLanguage) {
				continue;
			}
			
			// Update stock for other product languages
			$otherProductId = pll_get_post($product->get_id(), $lang);
			
			if( ! $otherProductId ) {
				continue;
			}
			
			// Update product stock
			wc_update_product_stock($otherProductId, $qty, $stockOperation);
			
			// Log information
			$order->add_order_note($product->get_name() . ' - updated translated product stock (Language: '.$lang.')');
		}
	}
}

// Instantiate Balfolk product stock class
$BFT_ProductStock = BFT_ProductStock::getInstance();