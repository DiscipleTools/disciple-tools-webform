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
     * @return mixed|\WP_Error
     */
    public static function update_keys( $prefix = '' ) {
        if ( empty( $prefix ) ) {
            $prefix = DT_Webform::$token;
        }
        $keys = get_option( $prefix . '_api_keys', [] );

        if ( isset( $_POST[ $prefix . '_nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST[ $prefix . '_nonce' ] ), $prefix . '_action' ) ) {

            if ( ! isset( $_POST['action'] ) ) {
                self::admin_notice( 'No action field defined in form submission.', 'error' );
                return $keys;
            }
            $action = sanitize_text_field( wp_unslash( $_POST['action'] ) );

            switch ( $action ) {

                case 'create':
                    if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) || ! isset( $_POST['url'] ) || empty( $_POST['url'] ) ) {
                        self::admin_notice( 'ID or URL fields required', 'error' );
                        return $keys;
                    }

                    $id = trim( wordwrap( strtolower( sanitize_text_field( wp_unslash( $_POST['id'] ) ) ), 1, '-', 0 ) );
                    $token = self::generate_token( 32 );
                    $url = trim(sanitize_text_field( wp_unslash( $_POST['url'] ) ) );

                    if ( ! isset( $keys[ $id ] ) ) {
                        $keys[ $id ] = [
                            'id'    => $id,
                            'token' => $token,
                            'url'   => $url,
                        ];

                        update_option( $prefix . '_api_keys', $keys, false );

                        return $keys;
                    } else {
                        self::admin_notice( 'ID already exists.', 'error' );
                        return $keys;
                    }
                    break;

                case 'update':

                    if ( ! isset( $_POST['id'] )
                        || empty( $_POST['id'] )
                        || ! isset( $_POST['token'] )
                        || empty( $_POST['token'] )
                        || ! isset( $_POST['url'] )
                        || empty( $_POST['url'] )
                    ){
                        self::admin_notice( 'Missing id, token, or url fields.', 'error' );
                        return $keys;
                    }

                    $id     = trim( wordwrap( strtolower( sanitize_text_field( wp_unslash( $_POST['id'] ) ) ), 1, '-', 0 ) );
                    $token  = trim( sanitize_key( wp_unslash( $_POST['token'] ) ) );
                    $url    = trim( sanitize_text_field( wp_unslash( $_POST['url'] ) ) );

                    $keys[ $id ] = [
                        'id'    => $id,
                        'token' => $token,
                        'url'   => $url,
                    ];

                    update_option( $prefix . '_api_keys', $keys, false );

                    return $keys;
                    break;

                case 'delete':
                    if ( ! isset( $_POST['id'] ) ) {
                        self::admin_notice( 'Delete: Key not found.', 'error' );
                        return $keys;
                    }
                    unset( $keys[ $_POST['id'] ] );

                    update_option( $prefix . '_api_keys', $keys, false );

                    return $keys;
                    break;
            }
        }
        return $keys;
    }

    public static function generate_token( $length = 32 ) {
        return bin2hex( random_bytes( $length ) );
    }

    /**
     * This method ecrypts with md5 and the GMT date. So every day, this encryption will change. Using this method
     * requires that both of the servers have their timezone in Settings > General > Timezone correctly set.
     *
     * @note Key changes every hour
     *
     * @param $value
     *
     * @return string
     */
    public static function one_hour_encryption( $value ) {
        return md5( $value . current_time( 'Y-m-dH', 1 ) );
    }

    /**
     * Tests id or token against options values. Decrypts md5 hash created with one_hour_encryption
     *
     * @param $type     ( Can be either 'id' or 'token' )
     * @param $value
     *
     * @return string|\WP_Error
     */
    public static function check_one_hour_encryption( $type, $value ) {
        switch( $type ) {
            case 'id':
                $keys = get_option( 'dt_webform_site_api_keys', [] );
                foreach( $keys as $key => $array ) {
                    if( md5( $key . current_time( 'Y-m-dH', 1 ) ) == $value ) {
                        return $key;
                    }
                }
                return new WP_Error('no_match_found', 'The id supplied was not found');
                break;
            case 'token':
                $keys = get_option( 'dt_webform_site_api_keys', [] );
                foreach( $keys as $key => $array ) {
                    if( isset( $array['token'] ) && md5( $array['token'] . current_time( 'Y-m-dH', 1 ) ) == $value ) {
                        return $key;
                    }
                }
                return new WP_Error('no_match_found', 'The token supplied was not found');
                break;
        }
        return new WP_Error('no_match_found', 'Missing $type designation.');
    }

    public static function check_token( $id, $token )
    {
        $keys = get_option( 'dt_webform_site_api_keys');
        return isset( $keys[ $id ] ) && $keys[ $id ]['token'] == $token;
    }


    /**
     * Check to see if an api key and token exist
     *
     * @param $id
     * @param $token
     * @param $prefix
     *
     * @return bool
     */
    public static function check_api_key( $id, $token, $prefix = '' )
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
