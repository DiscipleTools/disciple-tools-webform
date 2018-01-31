<?php

/**
 * DT_Webform_Async_Collector
 *
 * @see     https://github.com/techcrunch/wp-async-task
 * @class   DT_Webform_Async_Collector
 * @since   0.1.1
 * @package DT_Webform
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class DT_Webform_Async_Collector
 */
class DT_Webform_Collector extends DT_Webform_Async_Task
{
    protected $action = 'webform_collector';

    /**
     * Prepare data for the asynchronous request
     *
     * @throws Exception If for any reason the request should not happen.
     *
     * @param array $data An array of data sent to the hook
     *
     * @return array
     */
    protected function prepare_data( $data )
    {
        dt_write_log( 'Prepare data' );
        dt_write_log( $data );
        return $data;
    }

    /**
     * Send email
     */
    public function collect_leads()
    {
        dt_write_log( 'collect_leads' );
        /**
         * Nonce validation is done through a custom nonce process inside Disciple_Tools_Async_Task
         * to allow for asynchronous processing. This is a valid nonce but is not recognized by the WP standards checker.
         */
        // @codingStandardsIgnoreLine
        if( isset( $_POST[ 'action' ] ) && sanitize_key( wp_unslash( $_POST[ 'action' ] ) ) == 'dt_async_webform_collector' && isset( $_POST[ '_nonce' ] ) && $this->verify_async_nonce( sanitize_key( wp_unslash( $_POST[ '_nonce' ] ) ) ) ) {

            dt_write_log( 'collect leads inside' ); // @todo remove
            dt_write_log( $_POST ); // @todo remove

            // get Post variables
            $id = sanitize_key( wp_unslash( $_POST[0] ) );
            $md5_hash_id = DT_Webform_API_Keys::one_hour_encryption( $id );
            $token = sanitize_key( wp_unslash( $_POST[1] ) );
            $get_all = sanitize_key( wp_unslash( $_POST[2] ) );
            $selected_records = array_map( 'sanitize_key', wp_unslash( $_POST[3] ) );

            // 1. get url
            $keys = get_option( 'dt_webform_site_api_keys' );
            if ( empty( $keys ) ) {
                return new WP_Error( 'no_site_options', 'Failed to retrieve site options' );
            }
            if ( ! isset( $keys[ $id ] ) && empty( $keys[ $id ]['url'] ) ) {
                return new WP_Error( 'no_site_key_found', 'Failed to retrieve site key or url.' );
            }
            $url = $keys[ $id ]['url'];

            // 2. Begin remote collection
            if ( $get_all ) {
                // if get all is true, then get all available leads from remote source
            } else {
                // else, get selected records

                $args = [
                    'method' => 'GET',
                    'body' => [
                        'id' => $md5_hash_id,
                        'token' => $token,
                        'get_all' => $get_all,
                        'selected_records' => $selected_records,
                    ]
                ];
                $result = wp_remote_get( $url . '/wp-json/dt-public/v1/webform/get_collection', $args );
                dt_write_log( $result['body'] );
                if ( is_wp_error( $result ) ) {
                    return new WP_Error( 'failed_remote_get', $result->get_error_message() );
                }
            }

            // copy and save to local new_leads


            // send success confirmation back to remote site to delete leads.

        }
    }

    /**
     * Run the async task action
     * Used when loading long running process with add_action
     * Not used when launching via the dt_send_email() function.
     */
    protected function run_action()
    {
        $email = sanitize_email( $_POST[0]['email'] );
        $subject = sanitize_text_field( $_POST[0]['subject'] );
        $message = sanitize_text_field( $_POST[0]['message'] );

        do_action( "dt_async_$this->action", $email, $subject, $message );

    }
}

/**
 * This hook function listens for the prepared async process on every page load.
 */
function dt_async_webform_collector()
{
    if ( isset( $_POST['_wp_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wp_nonce'] ) ) ) && isset( $_POST['action'] ) && sanitize_key( wp_unslash( $_POST['action'] ) ) == 'dt_async_webform_collector' ) {
        dt_write_log( 'dt_webform_async_collector() inside' );
        $send_email = new DT_Webform_Collector();
        $send_email->collect_leads();
    }
}
add_action( 'init', 'dt_async_webform_collector' );



