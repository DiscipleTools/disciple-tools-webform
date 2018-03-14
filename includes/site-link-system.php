<?php
/**
 * Site Link System and API Keys
 *
 * @class DT_Site_Link_System
 * @class DT_Api_Keys
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( ! class_exists( 'DT_Site_Link_System' )  ) {
    /**
     * Class DT_Site_Link_System
     */
    class DT_Site_Link_System
    {
        public static $token = 'dt_webform';

        private static $_instance = null;

        /**
         * DT_Site_Link_System Instance
         * Ensures only one instance of DT_Site_Link_System is loaded or can be loaded.
         *
         * @since 0.1.0
         * @static
         * @return DT_Site_Link_System instance
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
            add_action( 'admin_head', [ $this, 'scripts' ], 20 );
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        } // End __construct()

        /**
         * Include this deactivation step into any deactivation hook for the plugin / theme
         *
         * @example  DT_Site_Link_System::deactivate()
         */
        public static function deactivate() {
            $prefix = DT_Site_Link_System::$token;
            delete_option( $prefix . '_api_keys' );
        }

        /**
         * Metabox for creating multiple site links
         */
        public static function metabox_multiple_link() {

            $prefix = self::$token;
            $keys = DT_Api_Keys::update_keys( );

            ?>
            <h3><?php esc_html_e( 'API Keys' ) ?></h3>
            <p></p>
            <form action="" method="post">
                <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                <h2><?php esc_html_e( 'Token Generator' ) ?></h2>
                <table class="widefat striped">
                    <tr>
                        <td><label for="id"><?php esc_html_e( 'Name' ) ?></label></td>
                        <td><input type="text" id="id" name="id" required> <?php esc_html_e( '(Case Sensitive)' ) ?></td>
                    </tr>
                    <tr>
                        <td><label for="url"><?php esc_html_e( 'Remote URL' ) ?></label></td>
                        <td><input type="text" id="url" name="url" placeholder="http://www.website.com" required>
                            <button type="submit" class="button" name="action" value="create"><?php esc_html_e( 'Generate Token' ) ?></button>
                        </td>
                    </tr>
                </table>
            </form>
            <br>
            <h2><?php esc_html_e( 'Existing Keys' ) ?></h2>
            <?php
            if ( ! empty( $keys ) || ! is_wp_error( $keys ) ) :
                foreach ( $keys as $key ): ?>
                    <form action="" method="post"><!-- begin form -->
                        <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                        <input type="hidden" name="id" value="<?php echo esc_html( $key['id'] ); ?>" />

                        <table class="widefat">
                            <thead>
                            <tr>
                                <td colspan="2"><?php esc_html_e( 'Setup information for ' ) ?>"<?php echo esc_html( $key['id'] ); ?>"</td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td><?php esc_html_e( 'ID' ) ?></td>
                                <td><?php echo esc_html( $key['id'] ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Token' ) ?></td>
                                <td><?php echo esc_html( $key['token'] ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Home URL' ) ?></td>
                                <td><?php echo esc_html( home_url() ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Remote URL' ) ?></td>
                                <td><?php echo esc_html( $key['url'] ); ?></td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <button type="button" class="button-like-link" onclick="jQuery('#delete-<?php echo esc_html( $key['id'] ); ?>').show();">
                                        <?php esc_html_e( 'Delete' ) ?>
                                    </button>
                                    <p style="display:none;" id="delete-<?php echo esc_html( $key['id'] ); ?>">
                                        <?php esc_html_e( 'Are you sure you want to delete this record? This is a permanent action.' ) ?><br>
                                        <button type="submit" class="button" name="action" value="delete">
                                            <?php esc_html_e( 'Permanently Delete' ) ?>
                                        </button>
                                    </p>
                                    <span style="float:right">
                                <?php esc_html_e( 'Status:' ) ?>
                                        <strong>
                                        <span id="<?php echo esc_attr( $key['id'] ); ?>-status">
                                            <?php esc_html_e( 'Checking Status' ) ?>
                                        </span>
                                    </strong>

                                </span>
                                    <p id="<?php echo esc_attr( $key['id'] ); ?>-message" style="text-align:center; display:none;">
                                        <?php esc_attr_e( sprintf('Your GMT server time is: (%s). This number must be identical on the linked site.', current_time( 'Y-m-dH', 1 ) ) ); ?>
                                    </p>
                                    <script>
                                        jQuery(document).ready(function() {
                                            check_link_status( '<?php echo esc_attr( DT_Api_Keys::one_hour_encryption( $key['id'], $key['token'] ) ); ?>', '<?php echo esc_attr( $key['url'] ); ?>', '<?php echo esc_attr( $key['id'] ); ?>' );
                                        })
                                    </script>
                                </td>
                            </tr>
                            </tbody>
                            <br>
                        </table>

                    </form><!-- end form -->
                <?php endforeach;  ?>
            <?php else : ?>
                <p><?php echo esc_attr__( 'No stored keys. To add a key use the token generator to create a key.' ) ?></p>
            <?php endif; ?>
            <?php
        }

        /**
         * Metabox for creating a single site link.
         */
        public static function metabox_single_link()
        {
            $prefix = DT_Site_Link_System::$token;
            $keys = DT_Api_Keys::update_keys();
            $key = self::clean_site_records( $keys );
            ?>
            <form method="post" action="">
                <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <td colspan="2">
                            <?php esc_html_e( 'Link to Remote Site to Home' ) ?>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td style="max-width:20px;">
                            <label for="id"><?php esc_html_e( 'ID' ) ?></label>
                        </td>
                        <td>
                            <input type="text" name="id" id="id" class="regular-text"
                            <?php echo ( isset( $key['id'] ) ) ? 'value="' . esc_attr( $key['id'] ) . '" readonly' : '' ?> /> (case sensitive)
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="token"><?php esc_html_e( 'Token' ) ?></label>
                        </td>
                        <td>
                            <input type="text" name="token" id="token" class="regular-text"
                            <?php echo ( isset( $key['token'] ) ) ? 'value="' . esc_attr( $key['token'] ) . '" readonly' : '' ?> />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="url"><?php esc_html_e( 'Home URL' ) ?></label>
                        </td>
                        <td>
                            <input type="text" name="url" id="url" class="regular-text" placeholder="http://www.website.com"
                            <?php echo ( isset( $key['url'] ) ) ? 'value="' . esc_attr( $key['url'] ) . '" readonly' : '' ?> />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <?php if ( isset( $key['id'] ) ) : ?>
                                <button type="submit" class="button" name="action" value="delete"><?php esc_html_e( 'Unlink Site' ) ?></button>
                            <?php else : ?>
                                <button type="submit" class="button" name="action" value="update"><?php esc_html_e( 'Update' ) ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ( isset( $key['id'] ) && ! empty( $key ) ) : ?>
                        <tr>
                            <td colspan="2">
                            <span style="float:right">
                                    <?php esc_html_e( 'Status: ' ) ?>
                                <strong>
                                        <span id="<?php echo esc_attr( $key['id'] ); ?>-status">
                                            <?php esc_html_e( 'Checking Status' ) ?>
                                        </span>
                                </strong>
                            </span>
                                <p id="<?php echo esc_attr( $key['id'] ); ?>-message" style="text-align:center; display:none;">
                                    <?php esc_attr_e( sprintf('Your GMT server time is: (%s). This number must be identical on the linked site.', current_time( 'Y-m-dH', 1 ) ) ); ?>
                                </p>
                                <script>
                                    jQuery(document).ready(function() {
                                        check_link_status( '<?php echo esc_attr( DT_Api_Keys::one_hour_encryption( $key['id'], $key['token'] ) ); ?>', '<?php echo esc_attr( $key['url'] ); ?>', '<?php echo esc_attr( $key['id'] ); ?>' );
                                    })
                                </script>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
            <br>
            <?php
        }

        public function scripts() {
            echo "<script type='text/javascript'>
                
            function check_link_status( transfer_token, url, id ) {
                
            let linked = '" .  esc_attr__( 'Linked' ) . "';
            let not_linked = '" .  esc_attr__( 'Not Linked' ) . "';
            let not_found = '" .  esc_attr__( 'Failed to connect with the URL provided.' ) . "';
            
            return jQuery.ajax({
                type: 'POST',
                data: JSON.stringify({ \"transfer_token\": transfer_token } ),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                url: url + '/wp-json/dt-public/v1/webform/site_link_check',
            })
                .done(function (data) {
                    if( data ) {
                        jQuery('#' + id + '-status').html( linked )
                    } else {
                        jQuery('#' + id + '-status').html( not_linked );
                        jQuery('#' + id + '-message').show();
                    }
                })
                .fail(function (err) {
                    jQuery( document ).ajaxError(function( event, request, settings ) {
                         if( request.status === 0 ) {
                            jQuery('#' + id + '-status').html( not_found )
                          
                         } else {
                            jQuery('#' + id + '-status').html( JSON.stringify( request.statusText ) )
                                
                         }
                    });
                });
            }
            </script>";
        }

        /**
         * Cleans potentially extra site records from previous configurations of the plugin.
         *
         * @param $keys
         *
         * @return mixed
         */
        private static function clean_site_records( $keys ) {
            $prefix = DT_Site_Link_System::$token;

            if ( empty( $keys ) ) {
                return $keys;
            }

            if ( count( $keys ) > 1 ) {

                foreach ( $keys as $key => $value ) {
                    $home_link = $value;
                    $cleaned[ $key ] = $value;
                    update_option( $prefix . '_api_keys', $cleaned, true );
                    break;
                }
            } else {
                foreach ( $keys as $key ) {
                    $home_link = $key;
                    break;
                }
            }

            return $home_link;
        }

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

        }

        /**
         * Verify site is linked
         *
         * @param  WP_REST_Request $request
         *
         * @return string|WP_Error|array The contact on success
         */
        public function site_link_check( WP_REST_Request $request )
        {
            $params = $request->get_params();

            if ( isset( $params['transfer_token'] ) ) {

                $status = DT_Api_Keys::check_one_hour_encryption( $params['transfer_token'] );

                if( $status ) {
                    return true;
                } else {
                    return false;
                }

            } else {
                return new WP_Error( "site_check_error", "Malformed request", [ 'status' => 400 ] );
            }
        }
    }
    DT_Site_Link_System::instance();
}


if( ! class_exists( 'DT_Api_Keys' ) ) {
    /**
     * Class DT_Api_Keys
     */
    class DT_Api_Keys
    {
        /**
         * Create, Update, and Delete api keys
         *
         * @param $prefix string
         * @return mixed|\WP_Error
         */
        public static function update_keys() {
            $prefix = DT_Site_Link_System::$token;
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
                        $url = trim( sanitize_text_field( wp_unslash( $_POST['url'] ) ) );

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

        /**
         * Generate token for api key
         *
         * @param int $length
         *
         * @return string
         */
        public static function generate_token( $length = 32 ) {
            return bin2hex( random_bytes( $length ) );
        }

        /**
         * This method encrypts with md5 and the GMT date. So every day, this encryption will change. Using this method
         * requires that both of the servers have their timezone in Settings > General > Timezone correctly set.
         *
         * @note Key changes every hour
         *
         * @param $value
         *
         * @return string
         */
        public static function one_hour_encryption( $id, $token ) {
            return md5( $id . $token . current_time( 'Y-m-dH', 1 ) );
        }

        /**
         * Tests id or token against options values. Decrypts md5 hash created with one_hour_encryption
         *
         * @param $value
         *
         * @return string|\WP_Error
         */
        public static function check_one_hour_encryption( $transfer_token ) {

            $keys = get_option( DT_Site_Link_System::$token . '_api_keys' );

            if ( empty( $keys ) ) {
                return new WP_Error( __METHOD__, 'No sites available' );
            }

            foreach ( $keys as $key => $array ) {
                if ( isset( $array['token'] ) && isset( $array['id'] ) && ( md5( $array['id'] . $array['token'] . current_time( 'Y-m-dH', 1 ) ) == $transfer_token
                || md5( $array['id'] . $array['token'] . current_time( 'Y-m-dH', 1 ) - 3600 ) == $transfer_token ) ) {
                    return $key;
                }
            }
            return false;
        }

        public static function check_token( $id, $token )
        {
            $prefix = DT_Site_Link_System::$token;
            $keys = get_option( $prefix . '_api_keys' );
            return isset( $keys[ $id ] ) && $keys[ $id ]['token'] == $token;
        }

        /**
         * Check to see if an api key and token exist @todo remove??
         *
         * @param $id
         * @param $token
         * @param $prefix
         *
         * @return bool
         */
//        public static function check_api_key( $id, $token )
//        {
//            $prefix = DT_Site_Link_System::$token;
//
//            $keys = get_option( $prefix . '_api_keys', [] );
//
//            return isset( $keys[ $id ] ) && $keys[ $id ]['token'] == $token;
//        }

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

        public static function are_sites_keys_set() {

            $prefix = DT_Site_Link_System::$token;
            $site = get_option( $prefix . '_api_keys' );
            if ( ! $site || count( $site ) < 1 ) { // if no site is connected, then disable auto_approve
                return false;
            }
            return true;
        }

        /**
         * Verify the token and id of a REST request
         * @param $params
         *
         * @return bool|\WP_Error
         */
        public static function verify_param_transfer_token( $params ) {
            if ( isset( $params['transfer_token'] ) ) {
                // check id
                $id_decrypted = self::check_one_hour_encryption( $params['transfer_token'] );
                if ( is_wp_error( $id_decrypted ) || ! $id_decrypted ) {
                    return new WP_Error( "site_check_error_1", "Malformed request", [ 'status' => 400 ] );
                }

                // check token
                $token_result = self::check_token( $id_decrypted, $params['token'] );
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
}