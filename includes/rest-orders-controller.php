<?php
/**
 * BalFolk tickets Order REST API controller
 *
 * Handles requests to the /bft/settings endpoints.
 *
 */

include_once 'order.php';
 
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
		error_log($this->namespace);
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_orders' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			) );
		} );
	}

	/**
	 * Get all settings groups items.
	 */
	public function get_orders( $request ) {
		if(is_null($request['key'])) {
			return new WP_Error('bft_rest_missing_arg', __('Order key is missing', 'woocommerce'), array( 'status' => 404) );
		}
		$order = BFT_Order::GetByKey($request['key']);
		
		if(is_null($order)) {
			return new WP_Error('bft_rest_not_found', __('Order with such key not found', 'woocommerce'), array( 'status' => 404) );
		}
 
		// Create the response object
		$response = new WP_REST_Response( $order->get_tickets() );
		 
		// Add a custom status code
		$response->set_status( 201 );
		return $response;
	}
	/**
	 * Makes sure the current user has access to READ the settings APIs.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view2', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the groups schema, conforming to JSON Schema.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'setting_group',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'A unique identifier that can be used to link settings together.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'label' => array(
					'description' => __( 'A human readable label for the setting used in interfaces.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'A human readable description for the setting used in interfaces.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'parent_id' => array(
					'description' => __( 'ID of parent grouping.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'sub_groups' => array(
					'description' => __( 'IDs for settings sub groups.', 'woocommerce' ),
					'type'        => 'string',
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