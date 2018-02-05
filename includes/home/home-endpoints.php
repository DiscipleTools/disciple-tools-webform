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
            $namespace, '/webform/transfer_collection', [
                [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'transfer_collection' ],
                ],
            ]
        );
    }

    /**
     * Verify is site is linked
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

    /**
     * Respond to transfer request of files
     *
     * @param \WP_REST_Request $request
     */
    public function transfer_collection( WP_REST_Request $request ) {

        $params = $request->get_params();
        $test = DT_Webform_Admin::verify_param_id_and_token( $params );

        if ( ! is_wp_error( $test ) && $test ) {
            // @todo build the transfer reception

            dt_write_log( $params );



        }
    }
}