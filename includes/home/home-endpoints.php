<?php
/**
 * DT_Webform_Home_Endpoints
 *
 * @class      DT_Webform_Home_Endpoints
 * @since      0.1.0
 * @package    DT_Webform
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Initialize instance
 */
DT_Webform_Home_Endpoints::instance();

/**
 * Class DT_Webform_Home_Endpoints
 */
class DT_Webform_Home_Endpoints
{
    /**
     * DT_Webform_Home_Endpoints The single instance of DT_Webform_Home_Endpoints.
     *
     * @var     object
     * @access    private
     * @since     0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Home_Endpoints Instance
     * Ensures only one instance of DT_Webform_Home_Endpoints is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Webform_Home_Endpoints instance
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
        $namespace = 'dt-public/v' . $version;

        register_rest_route(
            $namespace, '/webform/site_link_check', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'site_link_check' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/webform/site_link_hash_check', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'site_link_hash_check' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/webform/trigger_collection/(?P<id>[\w]+)', [
                [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'trigger_collection' ],
                ],
            ]
        );
    }

    public function trigger_collection( WP_REST_Request $request )
    {
        $params = $request->get_params();

        if ( isset( $params['id'] ) && isset( $params['token'] ) ) {
            // check id
            $id_result = DT_Webform_Api_Keys::check_one_hour_encryption( 'id', $params['id'] );
            if ( is_wp_error( $id_result ) || ! $id_result ) {
                return new WP_Error( "site_check_error_1", "Malformed request", [ 'status' => 400 ] );
            }

            // check token
            $token_result = DT_Webform_API_Keys::check_token( $id_result, $params['token'] );
            if ( is_wp_error( $token_result ) || ! $token_result ) {
                return new WP_Error( "site_check_error_2", "Malformed request", [ 'status' => 400 ] );
            } else {
                // call async process to schedule collection
                $collector = new DT_Webform_Async_Collector();
                $collector->launch( $params['selected_records'] ); // @todo left off here.

                // return successful scheduled message
                return true;
            }
        } else {
            return new WP_Error( "site_check_error_3", "Malformed request", [ 'status' => 400 ] );
        }
    }

    /**
     * Verify is site is linked
     * @todo identical function hosted in remote-endpoints.php. Reevaluate if this is the DRYest option.
     *
     * @param  WP_REST_Request $request
     *
     * @access public
     * @since  0.1.0
     * @return string|WP_Error|array The contact on success
     */
    public function site_link_check( WP_REST_Request $request )
    {
        $params = $request->get_params();

        $prefix = 'dt_webform_site';

        if ( isset( $params['id'] ) && isset( $params['token'] ) ) {
            return DT_Webform_Api_Keys::check_api_key( $params['id'], $params['token'], $prefix );
        } else {
            return new WP_Error( "site_check_error", "Malformed request", [ 'status' => 400 ] );
        }
    }

    public function site_link_hash_check( WP_REST_Request $request )
    {
        $params = $request->get_params();

        if ( isset( $params['id'] ) && isset( $params['token'] ) ) {
            // check id
            $id_result = DT_Webform_Api_Keys::check_one_hour_encryption( 'id', $params['id'] );
            if ( is_wp_error( $id_result ) || ! $id_result ) {
                return new WP_Error( "site_check_error_1", "Malformed request", [ 'status' => 400 ] );
            }

            // check token
            $token_result = DT_Webform_API_Keys::check_token( $id_result, $params['token'] );
            if ( is_wp_error( $token_result ) || ! $token_result ) {
                return new WP_Error( "site_check_error_2", "Malformed request", [ 'status' => 400 ] );
            } else {
                return true;
            }
        } else {
            return new WP_Error( "site_check_error_3", "Malformed request", [ 'status' => 400 ] );
        }
    }
}