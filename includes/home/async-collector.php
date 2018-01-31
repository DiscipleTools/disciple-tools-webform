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
class DT_Webform_Async_Collector extends DT_Webform_Async_Task
{
    protected $action = 'async_collector';

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
        return $data;
    }

    /**
     * Send email
     */
    public function send_email()
    {
        /**
         * Nonce validation is done through a custom nonce process inside Disciple_Tools_Async_Task
         * to allow for asynchronous processing. This is a valid nonce but is not recognized by the WP standards checker.
         */
        // @codingStandardsIgnoreLine
        if( isset( $_POST[ 'action' ] ) && sanitize_key( wp_unslash( $_POST[ 'action' ] ) ) == 'dt_async_email_notification' && isset( $_POST[ '_nonce' ] ) && $this->verify_async_nonce( sanitize_key( wp_unslash( $_POST[ '_nonce' ] ) ) ) ) {

            // @codingStandardsIgnoreLine
            wp_mail( sanitize_email( $_POST[ 0 ][ 'email' ] ), sanitize_text_field( $_POST[ 0 ][ 'subject' ] ), sanitize_text_field( $_POST[ 0 ][ 'message' ] ) );

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
function dt_load_async_email()
{
    if ( isset( $_POST['_wp_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wp_nonce'] ) ) ) && isset( $_POST['action'] ) && sanitize_key( wp_unslash( $_POST['action'] ) ) == 'dt_async_email_notification' ) {
        $send_email = new DT_Webform_Async_Collector();
        $send_email->send_email();
    }
}
add_action( 'init', 'dt_load_async_email' );



