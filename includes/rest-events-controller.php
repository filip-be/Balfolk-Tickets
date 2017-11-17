<?php
/**
 * BalFolk tickets Events REST API controller
 *
 * Handles requests to the /bft/events endpoints.
 *
 */

include_once 'event.php';
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BalFolk tickets Order REST API controller class.
 */
class BFT_REST_Events_Controller extends WP_REST_Controller {
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
	protected $rest_base = 'events';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_event' ),
					'permission_callback' => array( $this, 'bft_events_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			) );
			
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<key>[\d]+)', array(
				'args' => array(
					'key' => array(
						'description' => __( 'Event id', 'woocommerce' ),
						'type'        => 'int',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_event' ),
					'permission_callback' => array( $this, 'bft_events_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			) );
		} );
	}

	/**
	 * Get order with key defined
	 */
	public function get_event( $request ) {
		if(is_null($request['key'])) {
			return new WP_Error('bft_rest_missing_arg', __('Event id is missing', 'woocommerce'), array( 'status' => 404) );
		}
		// Get by order id
		$event = BFT_Event::GetByID($request['key']);
				
		// Still not found, return an error!
		if(is_null($event)) {
			return new WP_Error('bft_rest_not_found', __('Event with such ID not found', 'woocommerce'), array( 'status' => 404) );
		}
 
		// Create the response object
		$response = new WP_REST_Response( $event );
		 
		// Add OK status code
		$response->set_status( 200 );
		return $response;
	}
	
	/**
	 * Makes sure the current user has access to EDIT the tickets APIs.
	 */
	public function bft_events_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list events.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
	
	/**
	 * Get the groups schema, conforming to JSON Schema.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'event',
			'type'       => 'object',
			'properties' => array(
				'ID' => array(
					'description' => __( 'An unique woocommerce event ID.', 'woocommerce' ),
					'type'        => 'int',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'Name' => array(
					'description' => __( 'Event name', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'Timestamp' => array(
					'description' => __( 'Event timestamp', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'Status' => array(
					'description' => __( 'Event status', 'woocommerce' ),
					'type'        => 'int',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}

$BFT_REST_Events_Controller = BFT_REST_Events_Controller::getInstance();
$BFT_REST_Events_Controller->register_routes();