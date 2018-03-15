<?php
/**
 * Site Link System and API Keys
 *
 * @class DT_Site_Link_System
 * @class DT_Site_Link_System
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'DT_Site_Link_System' ) ) {
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
         * @param $id
         * @param $token
         *
         * @return string
         */
        public static function encrypt_transfer_token( $id, $token ) {
            return md5( $id . $token . current_time( 'Y-m-dH', 1 ) );
        }

        /**
         * Tests transfer token against options values. Decrypts md5 hash created with one_hour_encryption
         *
         * @param $transfer_token
         *
         * @return bool|string   False if no match; String with key if success;
         */
        public static function decrypt_transfer_token( $transfer_token ) {

            $keys = get_option( self::$token . '_api_keys' );

            if ( empty( $keys ) ) {
                return false;
            }

            foreach ( $keys as $key => $array ) {
                if ( isset( $array['token'] ) && isset( $array['id'] ) && ( md5( $array['id'] . $array['token'] . current_time( 'Y-m-dH', 1 ) ) == $transfer_token
                || md5( $array['id'] . $array['token'] . current_time( 'Y-m-dH', 1 ) - 3600 ) == $transfer_token ) ) {
                    return $key;
                }

            }
            return false;
        }

        /**
         * Metabox for creating multiple site links
         */
        public static function metabox_multiple_link() {
            $prefix = self::$token;
            $keys = self::process_form_post();
            ?>
            <h1><?php esc_html_e( 'API Keys for' ); ?> <?php echo get_bloginfo('name'); ?></h1>


            <!-- Connect to Other Website -->
            <form action="" method="post">
                <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                <h2><?php esc_html_e( 'Connect to Another Site' ) ?></h2>
                <table class="widefat striped">
                    <tr>
                        <td width="100px" colspan="2">
                            <?php esc_attr_e('Get the ID, Token, and URL from the remote site and insert here.') ?>
                        </td>
                    </tr>
                    <tr>
                        <td width="100px"><label for="id"><?php esc_html_e( 'ID' ) ?></label></td>
                        <td><input type="text" id="id" name="id" required /> <span class="text-small"><em><?php esc_html_e( '(Case Sensitive)' ) ?></em></span></td>
                    </tr>
                    <tr>
                        <td><label for="token"><?php esc_html_e( 'Token' ) ?></label></td>
                        <td><input type="text" id="token" name="token" required /></td>
                    </tr>
                    <tr>
                        <td><label for="url"><?php esc_html_e( 'URL' ) ?></label></td>
                        <td><input type="text" id="url" name="url" placeholder="<?php esc_html_e( 'www.website.com' ) ?>" required /> <span class="text-small"><em><?php esc_html_e( '(no "https://")' ) ?></em></span></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="submit" class="button" name="action" value="update"><?php esc_html_e( 'Connect To Site' ) ?></button>
                        </td>
                    </tr>
                </table>
            </form>
            <br>



            <!-- New Site Key Generator-->
            <form action="" method="post">
                <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                <h2><?php esc_html_e( 'Generate New Site Key' ) ?></h2>
                <table class="widefat striped">
                    <tr>
                        <td width="90px"><label for="id"><?php esc_html_e( 'Name' ) ?></label></td>
                        <td><input type="text" id="id" name="id" required> <?php esc_html_e( '(Case Sensitive)' ) ?></td>
                    </tr>
                    <tr>
                        <td><label for="url"><?php esc_html_e( 'Target URL' ) ?></label></td>
                        <td>
                            <input type="text" id="url" name="url" placeholder="www.website.com" required>  <button type="submit" class="button" name="action" value="create"><?php esc_html_e( 'Generate Token' ) ?></button>

                        </td>
                    </tr>
                </table>
            </form>
            <br>


            <!-- Existing Site Connections -->
            <h2><?php esc_html_e( 'Existing Site Connections' ) ?></h2>
            <?php
            if ( ! empty( $keys ) || ! is_wp_error( $keys ) ) :
                foreach ( $keys as $key ): ?>
                    <form action="" method="post"><!-- begin form -->
                        <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                        <input type="hidden" name="id" value="<?php echo esc_html( $key['id'] ); ?>" />
                        <table class="widefat">
                            <thead>
                            <tr>
                                <td><strong><?php echo esc_html( $key['id'] ); ?></strong></td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <strong><?php esc_html_e( 'Target site:' ) ?></strong>
                                    <table class="widefat">
                                        <tbody>
                                        <tr>
                                            <td><?php echo esc_html( $key['url'] ); ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong><?php esc_html_e( 'Place this information into the target site' ) ?></strong>
                                    <table class="widefat">
                                        <tr>
                                            <td width="100px"><?php esc_html_e( 'ID' ) ?></td>
                                            <td><?php echo esc_html( $key['id'] ); ?></td>
                                        </tr>
                                        <tr>
                                            <td><?php esc_html_e( 'Token' ) ?></td>
                                            <td><?php echo esc_html( $key['token'] ); ?></td>
                                        </tr>
                                        <tr>
                                            <td><?php esc_html_e( 'URL' ) ?></td>
                                            <td><?php echo esc_html( self::get_current_site_base_url() ); ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
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
                                </td>
                            </tr>
                            <tr id="<?php echo esc_attr( $key['id'] ); ?>-message" style="display:none;">
                                <td>
                                    <strong><?php esc_attr_e('Consider Checking:') ?></strong>
                                    <ol>
                                        <li>
                                            <?php echo sprintf( esc_attr__( 'Check if the target site is setup with identical configuration information.' ), esc_attr( current_time( 'Y-m-dH', 1 ) ) ); ?>
                                        </li>
                                        <li>
                                            <?php echo esc_attr__( 'Check if HTTPS/SSL is enabled on both sites. Due to the transfer of data between these sites, SSL encryption is required for both sites to protect the data exchange.' ); ?>
                                        </li>
                                        <li>
                                            <?php echo esc_attr__( 'Check if the server timestamps are identical. Mismatched server times will cause decryption key failures. Your server timestamp' ); ?>: <span class="info-color"><strong><?php echo esc_attr( current_time( 'Y-m-dH', 1 ) ) ?></strong></span>
                                        </li>
                                    </ol>
                                </td>
                            </tr>
                            <script>
                                jQuery(document).ready(function() {
                                    check_link_status( '<?php echo esc_attr( self::encrypt_transfer_token( $key['id'], $key['token'] ) ); ?>', '<?php echo esc_attr( $key['url'] ); ?>', '<?php echo esc_attr( $key['id'] ); ?>' );
                                })
                            </script>
                            </tbody>
                        </table>
                        <br>

                    </form><!-- end form -->
                <?php endforeach;  ?>
            <?php else : ?>
                <p><?php echo esc_attr__( 'No stored keys. To add a key use the token generator to create a key.' ) ?></p>
            <?php endif; ?>

        <!-- Footer Information -->
            <hr />
            <p class="text-small"><?php esc_attr_e( 'Timestamp for this server') ?>: <span class="info-color"><?php echo esc_attr( current_time( 'Y-m-dH', 1 ) ) ?></span>  <br><em><?php esc_attr_e( 'Compare this number to linked sites. They should be identical.') ?></em></p>
            <p><?php esc_attr_e( 'URL for this server') ?>: <span class="info-color"><?php echo esc_html( self::get_current_site_base_url() ); ?></span></p>
            <?php
        }

        /**
         * Metabox for creating a single site link.
         */
        public static function metabox_single_link()
        {
            $prefix = self::$token;
            $keys = self::process_form_post();
            $key = self::clean_site_records( $keys );
            ?>
            <form method="post" action="">
                <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <td colspan="2">
                            <strong><?php esc_html_e( 'Link to Home Site' ) ?></strong><br>

                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td width="100px">
                            <label for="id"><?php esc_html_e( 'ID' ) ?></label>
                        </td>
                        <td>
                            <input type="text" name="id" id="id"
                            <?php echo ( isset( $key['id'] ) ) ? 'value="' . esc_attr( $key['id'] ) . '" readonly' : '' ?> /> (case sensitive)
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="token"><?php esc_html_e( 'Token' ) ?></label>
                        </td>
                        <td>
                            <input type="text" name="token" id="token"
                            <?php echo ( isset( $key['token'] ) ) ? 'value="' . esc_attr( $key['token'] ) . '" readonly' : '' ?> />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="url"><?php esc_html_e( 'URL' ) ?></label>
                        </td>
                        <td>
                            <input type="text" name="url" id="url" placeholder="www.website.com"
                            <?php echo ( isset( $key['url'] ) ) ? 'value="'.esc_attr( $key['url'] ) . '" readonly' : '' ?> />
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
                    <tr>
                        <td colspan="2">
                            <?php esc_html_e( 'Current site' ) ?>: <span class="info-color"><?php echo esc_attr( self::get_current_site_base_url() ) ?></span><br>
                            <span class="text-small">Timestamp: <?php echo esc_attr( current_time( 'Y-m-dH', 1 ) ) ?></span>


                    <?php if ( isset( $key['id'] ) && ! empty( $key ) ) : ?>


                            <span style="float:right">
                                    <?php esc_html_e( 'Status: ' ) ?>
                                <strong>
                                        <span id="<?php echo esc_attr( $key['id'] ); ?>-status">
                                            <?php esc_html_e( 'Checking Status' ) ?>
                                        </span>
                                </strong>
                            </span>
                        </td>
                    </tr>
                    <tr id="<?php echo esc_attr( $key['id'] ); ?>-message" style="display:none;">
                        <td>
                            <strong><?php esc_attr_e('Consider Checking:') ?></strong>
                            <ol>
                                <li>
                                    <?php echo sprintf( esc_attr__( 'Check if the target site is setup with identical configuration information.' ), esc_attr( current_time( 'Y-m-dH', 1 ) ) ); ?>
                                </li>
                                <li>
                                    <?php echo esc_attr__( 'Check if HTTPS/SSL is enabled on both sites. Due to the transfer of data between these sites, SSL encryption is required for both sites to protect the data exchange.' ); ?>
                                </li>
                                <li>
                                    <?php echo esc_attr__( 'Check if the server timestamps are identical. Mismatched server times will cause decryption key failures. Your server timestamp' ); ?>: <span style="color:green; font-weight: bold;"><?php echo esc_attr( current_time( 'Y-m-dH', 1 ) ) ?></span>
                                </li>
                            </ol>
                            <hr />
                            <p class="text-small"><?php esc_attr_e( 'Timestamp for this server') ?>: <span class="info-color"><?php echo esc_attr( current_time( 'Y-m-dH', 1 ) ) ?></span>  <br><em><?php esc_attr_e( 'Compare this number to linked sites. They should be identical.') ?></em></p>
                            <p><?php esc_attr_e( 'URL for this server') ?>: <span class="info-color"><?php echo esc_html( home_url() ); ?></span></p>

                        <script>
                            jQuery(document).ready(function() {
                                check_link_status( '<?php echo esc_attr( self::encrypt_transfer_token( $key['id'], $key['token'] ) ); ?>', '<?php echo esc_attr( $key['url'] ); ?>', '<?php echo esc_attr( $key['id'] ); ?>' );
                            })
                        </script>
                    <?php endif; ?>
                        </td>
                    </tr>
                    </tbody>
                </table>

            </form>
            <br>
            <?php
        }

        /**
         * Create, Update, and Delete api keys
         *
         * @return mixed|\WP_Error
         */
        public static function process_form_post() {
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
                        $url = sanitize_text_field( wp_unslash( $_POST['url'] ) );
                        $url = str_replace('http://', '', $url );
                        $url = trim( str_replace('https://', '', $url ) );

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
                        $url    = sanitize_text_field( wp_unslash( $_POST['url'] ) );
                        $url    = str_replace('http://', '', $url );
                        $url    = trim( str_replace('https://', '', $url ) );

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
         * Cleans potentially extra site records from previous configurations of the plugin.
         * Used by the single metabox configuration
         *
         * @param $keys
         *
         * @return mixed
         */
        private static function clean_site_records( $keys ) {
            $prefix = self::$token;

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
                url: 'https://' + url + '/wp-json/dt-public/v1/webform/site_link_check',
            })
                .done(function (data) {
                    if( data ) {
                        jQuery('#' + id + '-status').html( linked ).attr('class', 'success-green')
                    } else {
                        jQuery('#' + id + '-status').html( not_linked ).attr('class', 'fail-red');
                        jQuery('#' + id + '-message').show();
                    }
                })
                .fail(function (err) {
                    jQuery( document ).ajaxError(function( event, request, settings ) {
                         if( request.status === 0 ) {
                            jQuery('#' + id + '-status').html( not_found ).attr('class', 'fail-red')
                         } else {
                            jQuery('#' + id + '-status').html( JSON.stringify( request.statusText ) ).attr('class', 'fail-red')
                         }
                    });
                });
            }
            </script>";
            echo "<style>
                    .success-green { color: limegreen;}
                    .fail-red { color: red;}
                    .info-color { color: steelblue; }
                </style>";
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

        /**
         * REST ROUTES
         */
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
                $status = self::verify_transfer_token( $params['transfer_token'] );
                if ( $status ) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return new WP_Error( "site_check_error", "Malformed request", [ 'status' => 400 ] );
            }
        }

        /**
         * Verify the token and id of a REST request
         *
         * @param $site_key
         *
         * @return bool|\WP_Error
         */
        private static function verify_referrer_ip( $site_key ) {
            $keys = get_option( self::$token . '_api_keys' );

            if ( ! isset( $keys[ $site_key ] ) ) {
                return new WP_Error(__METHOD__, 'No site key found.' );
            }

            if ( empty( $keys[ $site_key ]['ip'] ) ) {
                return true; // no ip address check required
            }

            if ( ! empty( $keys[ $site_key ]['ip'] ) ) {
                $required_id = trim( $keys[ $site_key ]['ip'] );
                $referrer_ip = trim( self::get_real_ip_address() );
                if ( $required_id == $referrer_ip ) {
                    return true; // ip address check passed
                }
            }
            return false;
        }

        /**
         * Verify the token and id of a REST request
         *
         * @param $transfer_token
         *
         * @return bool
         */
        public static function verify_transfer_token( $transfer_token ) : bool {
            if ( ! empty( $transfer_token ) ) {
                // check id
                $id_decrypted = self::decrypt_transfer_token( $transfer_token );
                if ( $id_decrypted ) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        
        /**
         * Checks if site keys exist
         * 
         * @return bool
         */
        public static function verify_sites_keys_are_set() : bool {
            $prefix = self::$token;
            $site = get_option( $prefix . '_api_keys' );
            if ( ! $site || count( $site ) < 1 ) { // if no site is connected, then disable auto_approve
                return false;
            }
            return true;
        }

        /**
         * Gets the real ip address for the referring client
         * @return string
         */
        public static function get_real_ip_address()
        {
            $ip = '';
            if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ))   //check ip from share internet
            {
                // @codingStandardsIgnoreLine
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ))   //to check ip is pass from proxy
            {
                // @codingStandardsIgnoreLine
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) )
            {
                // @codingStandardsIgnoreLine
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return $ip;
        }

        public static function get_local_public_ip_address() {
            $ip = file_get_contents("http://ipecho.net/plain");
            if( !empty( $ip ) ) {
                return $ip;
            }

            $ip = file_get_contents("http://icanhazip.com");
            if( !empty( $ip ) ) {
                return $ip;
            }
            return __( 'Unable to get local IP address' );
        }

        protected static function get_current_site_base_url() {
            $url = str_replace( 'http://', '', home_url() );
            $url = str_replace( 'https://', '', home_url() );
            return $url;
        }

        /**
         * Add this deactivation step into any deactivation hook for the plugin / theme
         * @example  DT_Site_Link_System::deactivate()
         */
        public static function deactivate() {
            $prefix = self::$token;
            delete_option( $prefix . '_api_keys' );
        }
    }
    DT_Site_Link_System::instance();

} // end class check