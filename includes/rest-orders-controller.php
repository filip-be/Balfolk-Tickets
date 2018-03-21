<?php
/**
 * BalFolk tickets Order REST API controller
 *
 * Handles requests to the /bft/settings endpoints.
 *
 */

include_once 'order.php';
include_once 'order_ticket.php';
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BalFolk tickets Order REST API controller class.
 */
class BFT_REST_Orders_Controller extends WP_REST_Controller {
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

	/**
	 * WP REST API namespace/version.
	 */
	protected $namespace = 'wc/v2/bft';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_orders' ),
					'permission_callback' => array( $this, 'bft_orders_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			) );
			
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<key>[a-zA-Z\d_]+)', array(
				'args' => array(
					'key' => array(
						'description' => __( 'Order key', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_order' ),
					'permission_callback' => array( $this, 'bft_orders_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_order' ),
					'permission_callback' => array( $this, 'bft_orders_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			) );
		} );
	}
	
	/**
	 * Get all orders
	 */
	public function get_orders( $request ) {
		// Query WooCommerce for all order ID's
		$query = new WC_Order_Query( array(
			'limit' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'return' => 'ids',
		) );
		$orderIDs = $query->get_orders();
		
		$bftOrders = array();
		// Loop orders IDs
		foreach($orderIDs as $orderId)
		{
			array_push($bftOrders, $this->get_order_item($orderId));
		}
		
		// Create the response object
		$response = new WP_REST_Response( $bftOrders );
		 
		// Add OK status code
		$response->set_status( 200 );
		return $response;
	}

	private function get_order_item($key)
	{
		// Get by order id
		$order = BFT_Order::GetByID($key);
		
		// Not found - Get by order key
		if(is_null($order)) {
			$order = BFT_Order::GetByKey($key);
		
			// Not found Get by single order ticket key
			if(is_null($order)) {
				$order = BFT_Order::GetByTicketHash($key);
			}
		}
		
		if(!is_null($order))
		{
			$order->load_notes();
		}
		
		return $order;
	}
	
	/**
	 * Get order with key defined
	 */
	public function get_order( $request ) {
		if(is_null($request['key'])) {
			return new WP_Error('bft_rest_missing_arg', __('Order key is missing', 'woocommerce'), array( 'status' => 404) );
		}
		
		$order = $this->get_order_item($request['key']);
		
		// Still not found, return an error!
		if(is_null($order)) {
			return new WP_Error('bft_rest_not_found', __('Order with such key not found', 'woocommerce'), array( 'status' => 404) );
		}
 
		// Create the response object
		$response = new WP_REST_Response( $order );
		 
		// Add OK status code
		$response->set_status( 200 );
		return $response;
	}
	
	/**
	 * Update order
	 */
	public function update_order($request) {
		
	}
	
	/**
	 * Makes sure the current user has access to EDIT the orders APIs.
	 */
	public function bft_orders_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list orders.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the groups schema, conforming to JSON Schema.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'order',
			'type'       => 'object',
			'properties' => array(
				'OrderId' => array(
					'description' => __( 'An unique woocommerce order ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'Status' => array(
					'description' => __( 'Order status', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'Type' => array(
					'description' => __( 'Order type - FULL / PARTIAL', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'Tickets' => array(
					'description' => __( 'Order tickets', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}

$BFT_REST_Orders_Controller = BFT_REST_Orders_Controller::getInstance();
$BFT_REST_Orders_Controller->register_routes();