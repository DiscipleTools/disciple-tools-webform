<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class DT_Webform_Api_Keys
 * Generate api keys for DT. The api key can be used by external sites or
 * applications where there is no authenticated user.
 */
class DT_Webform_Api_Keys
{
    /**
     * Get site keys
     *
     * @param $prefix string
     * @return mixed
     */
    public static function get_keys( $prefix = '' ) {
        if ( empty( $prefix ) ) {
            $prefix = DT_Webform::$token;
        }

        $keys = get_option( $prefix . '_api_keys', [] );

        return $keys;
    }

    /**
     * Create, Update, and Delete api keys
     *
     * @param $prefix string
     * @return mixed
     */
    public static function update_keys( $prefix = '' ) {
        if ( empty( $prefix ) ) {
            $prefix = DT_Webform::$token;
        }

        $keys = get_option( $prefix . '_api_keys', [] );

        if ( isset( $_POST[ $prefix . '_nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST[ $prefix . '_nonce' ] ), $prefix . '_action' ) ) {

            if ( isset( $_POST[ $prefix .'_id' ] ) && !empty( $_POST[ $prefix .'_id' ] ) ) {

                $client_id = wordwrap( strtolower( sanitize_text_field( wp_unslash( $_POST[ $prefix .'_id' ] ) ) ), 1, '-', 0 );
                $token = bin2hex( random_bytes( 32 ) );
                $url = home_url();

                if ( !isset( $keys[ $client_id ] ) ) {
                    $keys[ $client_id ] = [
                        'id'    => $client_id,
                        'token' => $token,
                        'url'   => $url,
                    ];
                    update_option( $prefix . '_api_keys', $keys, false );
                } else {
                    self::admin_notice( 'ID already exists', 'error' );
                }
            } elseif ( isset( $_POST['delete'] ) ) {
                if ( $keys[ sanitize_text_field( wp_unslash( $_POST['delete'] ) ) ] ) {

                    unset( $keys[ sanitize_text_field( wp_unslash( $_POST['delete'] ) ) ] );
                    update_option( $prefix . '_api_keys', $keys, false );

                }
            }
        }
        return $keys;
    }

    /**
     * Check to see if an api key and token exist
     *
     * @param $client_id
     * @param $client_token
     *
     * @return bool
     */
    public static function check_api_key( $prefix, $id, $token )
    {
        if ( empty( $prefix ) ) {
            $prefix = DT_Webform::$token;
        }

        $keys = get_option( $prefix . '_api_keys', [] );

        return isset( $keys[ $id ] ) && $keys[ $id ]['token'] == $token;
    }

    /**
     * Display an admin notice on the page
     *
     * @param $notice , the message to display
     * @param $type   , the type of message to display
     *
     * @access private
     * @since  0.1.0
     */
    public static function admin_notice( $notice, $type )
    {
        echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>';
        echo esc_html( $notice );
        echo '</p></div>';
    }
}
