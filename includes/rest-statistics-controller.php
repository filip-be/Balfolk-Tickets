<?php
/**
 * BalFolk Events REST API controller - tickets statistics
 *
 * Handles requests to the /bft/ticketsstats endpoints.
 *
 */

include_once 'event.php';
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BalFolk tickets Statistics REST API controller class.
 */
class BFT_REST_Statistics_Controller extends WP_REST_Controller {
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
	protected $rest_base = 'statistics';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_statistics' ),
					'permission_callback' => array( $this, 'bft_statistics_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			) );
		} );
	}

	/**
	 * Get order with key defined
	 */
	public function get_statistics( $request ) {
		global $wpdb;
		
		$query = 'SELECT 
    e.Name AS Event,
	p.post_title AS Product,
    (
		SELECT 
			COUNT(checkedTicket.PK_ID)
		FROM wp_bft_order_ticket checkedTicket
        WHERE
			checkedTicket.FK_TicketID = t.PK_ID 
            AND checkedTicket.Status = 2
	) AS CheckedTickets,
    COUNT(allTicket.PK_ID) AS TicketsCount
FROM wp_bft_ticket t 
JOIN wp_bft_event e ON e.PK_ID = t.FK_EventID
JOIN wp_posts p ON p.ID = t.FK_ProductID
JOIN wp_bft_order_ticket allTicket ON allTicket.FK_TicketID = t.PK_ID
JOIN wp_posts tp ON tp.ID = allTicket.FK_OrderId
WHERE
	EXISTS(SELECT 1 FROM wp_bft_order_ticket ot WHERE ot.FK_TicketID = t.PK_ID)
    AND tp.post_status IN (''wc-completed'')
GROUP BY
	e.Name,
    p.post_title
ORDER BY
	e.Name ASC
	, p.post_title ASC';
		$stats = $wpdb->get_results($query);
		
		$statistics = array();
		// Loop orders IDs
		foreach($stats as $stat)
		{
			array_push($statistics, $stat);
		}
		
		// Create the response object
		$response = new WP_REST_Response( $statistics );
		 
		// Add OK status code
		$response->set_status( 200 );
		return $response;
	}
	
	/**
	 * Makes sure the current user has access to EDIT the tickets APIs.
	 */
	public function bft_statistics_permissions_check( $request ) {
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

$BFT_REST_Statistics_Controller = BFT_REST_Statistics_Controller::getInstance();
$BFT_REST_Statistics_Controller->register_routes();