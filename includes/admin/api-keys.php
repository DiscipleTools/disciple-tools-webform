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
     * @var object instance. The class instance
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Api_Keys Instance
     * Ensures only one instance of DT_Webform_Api_Keys is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Api_Keys instance
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
     * @access public
     * @since  0.1.0
     */
    public function __construct()
    {
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
    private function admin_notice( $notice, $type )
    {
        echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>';
        echo esc_html( $notice );
        echo '</p></div>';
    }

    /**
     * The API keys page html
     *
     * @access public
     * @since  0.1.0
     */
    public function api_keys_page()
    {

        if ( !current_user_can( "manage_dt" ) ) {
            wp_die( 'You do not have permission to this page' );
        }

        $keys = get_option( "dt_api_keys", [] );

        if ( isset( $_POST['api-key-view-field'] ) && wp_verify_nonce( sanitize_key( $_POST['api-key-view-field'] ), 'api-keys-view' ) ) {

            if ( isset( $_POST["application"] ) && !empty( $_POST["application"] ) ) {
                $client_id = wordwrap( strtolower( sanitize_text_field( wp_unslash( $_POST["application"] ) ) ), 1, '-', 0 );
                $token = bin2hex( random_bytes( 32 ) );
                if ( !isset( $keys[ $client_id ] ) ) {
                    $keys[ $client_id ] = [
                    "client_id" => $client_id,
                    "client_token" => $token
                    ];
                    update_option( "dt_api_keys", $keys );
                } else {
                    $this->admin_notice( "Application already exists", "error" );
                }
            } elseif ( isset( $_POST["delete"] ) ) {
                if ( $keys[ sanitize_text_field( wp_unslash( $_POST["delete"] ) ) ] ) {
                    unset( $keys[ sanitize_text_field( wp_unslash( $_POST["delete"] ) ) ] );
                    update_option( "dt_api_keys", $keys );
                }
            }
        }
        $this->template( $keys );
    }

    /**
     * Check to see if an api key and token exist
     *
     * @param $client_id
     * @param $client_token
     *
     * @return bool
     */
    public function check_api_key( $client_id, $client_token )
    {
        $keys = get_option( "dt_api_keys", [] );

        return isset( $keys[ $client_id ] ) && $keys[ $client_id ]["client_token"] == $client_token;
    }

    /**
     * @param $keys
     */
    public function template( $keys ) {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <h1>API Keys</h1>
                <p>Developers can use API keys to grant limited access to the Disciple Tools
                    API from external websites and applications. To get an API key, fill in what what you want to call it below.
                    We will generate Client Token and Client Id base on the name.
                </p>
                <?php /* This warning may be worded too strongly, we might want to review
    it after we've done a security review of the API. */ ?>
                <p><strong>Do not give access to anyone or anything</strong> you do not
                    trust with all the data stored in this website.</p>


                <form action="" method="post">
                    <?php wp_nonce_field( 'api-keys-view', 'api-key-view-field' ); ?>
                    <h2>Token Generator</h2>
                    <table class="widefat striped" style="margin-bottom:50px">
                        <tr>
                            <th>
                                <label for="application">Name</label>
                            </th>
                            <td>
                                <input type="text" id="application" name="application">
                                <button type="submit" class="button">Generate Token</button>
                            </td>
                        </tr>
                    </table>
                    <h2>Existing Keys</h2>
                    <table class="widefat striped">
                        <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Client Token</th>
                            <th></th>
                        </tr>
                        </thead>
                        <?php foreach ( $keys as $id => $key): ?>
                            <tbody>
                            <tr>
                                <td>
                                    <?php echo esc_html( $key["client_id"] ); ?>
                                </td>
                                <td>
                                    <?php echo esc_html( $key["client_token"] ); ?>
                                </td>
                                <td>
                                    <button type="submit" class="button button-delete" name="delete" value="<?php echo esc_attr( $id ); ?>">Delete <?php echo esc_html( $id ); ?></button>
                                </td>
                            </tr>
                            </tbody>
                        <?php endforeach; ?>
                    </table>
                </form>
            </div>
        </div>

        <?php
    }

}
