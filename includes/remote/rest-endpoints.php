<?php

/**
 * DT_Webform_Remote_Endpoints
 *
 * @class      DT_Webform_Remote_Endpoints
 * @version    0.1.0
 * @since      0.1.0
 * @package    Disciple_Tools
 */

/**
 * @todo
 * 1. Create endpoint to deliver new contacts
 * 2. Create endpoint to confirm successful transfer of contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Webform_Remote_Endpoints
 */
class DT_Webform_Remote_Endpoints
{
    /**
     * DT_Webform_Remote_Endpoints The single instance of DT_Webform_Remote_Endpoints.
     *
     * @var     object
     * @access    private
     * @since     0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Remote_Endpoints Instance
     * Ensures only one instance of DT_Webform_Remote_Endpoints is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Webform_Remote_Endpoints instance
     */
    public static function instance()
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access  public
     * @since   0.1.0
     */
    public function __construct()
    {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    } // End __construct()

    public function add_api_routes()
    {
        $version = '1';
        $namespace = 'dt-webform/v' . $version;

        register_rest_route(
            $namespace, '/trigger_check/(?P<notification_id>\d+)', [
            [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'trigger_check' ],
            ],
            ]
        );
    }

    /**
     * Get tract from submitted address
     *
     * @param  WP_REST_Request $request
     *
     * @access public
     * @since  0.1.0
     * @return string|WP_Error|array The contact on success
     */
    public function trigger_check( WP_REST_Request $request )
    {
        $params = $request->get_params();
        if ( isset( $params['notification_id'] ) ) {
            $result = Disciple_Tools_Notifications::mark_viewed( $params['notification_id'] );
            if ( $result["status"] ) {
                return $result['rows_affected'];
            } else {
                return new WP_Error( "mark_viewed_processing_error", $result["message"], [ 'status' => 400 ] );
            }
        } else {
            return new WP_Error( "notification_param_error", "Please provide a valid array", [ 'status' => 400 ] );
        }
    }
}
