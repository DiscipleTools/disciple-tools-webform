<?php
/**
 * Collection of various utilities
 */

class DT_Webform_Remote
{
    /**
     * Cleans potentially extra site records from previous configurations of the plugin.
     *
     * @param $keys
     *
     * @return mixed
     */
    public static function clean_site_records( $keys, $prefix ) {
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

    public static function site_link_metabox()
    {
        $prefix = 'dt_webform_site';
        $keys = DT_Webform_Api_Keys::update_keys( $prefix );
        $home_link = self::clean_site_records( $keys, $prefix );
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <td colspan="2">
                        <?php esc_html_e( 'Link to Remote Site to Home', 'dt_webform' ) ?>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="max-width:20px;">
                        <label for="id"><?php esc_html_e( 'ID', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="id" id="id" class="regular-text"
                                <?php echo ( isset( $home_link['id'] ) ) ? 'value="' . esc_attr( $home_link['id'] ) . '" readonly' : '' ?> /> (case sensitive)
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="token"><?php esc_html_e( 'Token', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="token" id="token" class="regular-text"
                        <?php echo ( isset( $home_link['token'] ) ) ? 'value="' . esc_attr( $home_link['token'] ) . '" readonly' : '' ?> />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="url"><?php esc_html_e( 'Home URL', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="url" id="url" class="regular-text" placeholder="http://www.website.com"
                        <?php echo ( isset( $home_link['url'] ) ) ? 'value="' . esc_attr( $home_link['url'] ) . '" readonly' : '' ?> />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php if ( isset( $home_link['id'] ) ) : ?>
                            <button type="submit" class="button" name="action" value="delete"><?php esc_html_e( 'Unlink Site', 'dt_webform' ) ?></button>
                        <?php else : ?>
                            <button type="submit" class="button" name="action" value="update"><?php esc_html_e( 'Update', 'dt_webform' ) ?></button>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ( isset( $home_link['id'] ) && ! empty( $home_link ) ) : ?>
                <tr>
                    <td colspan="2">
                        <span style="float:right">
                                <?php esc_html_e( 'Status:', 'dt_webform' ) ?>
                            <strong>
                                    <span id="<?php echo esc_attr( $home_link['id'] ); ?>-status">
                                        <?php esc_html_e( 'Checking Status', 'dt_webform' ) ?>
                                    </span>
                                </strong>
                            </span>
                        <script>
                            jQuery(document).ready(function() {
                                check_link_status( '<?php echo esc_attr( $home_link['id'] ); ?>', '<?php echo esc_attr( $home_link['token'] ); ?>', '<?php echo esc_attr( $home_link['url'] ); ?>' );
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

    /**
     * Trigger transfer of new leads
     *
     * @param array $selected_records
     *
     * @return bool|\WP_Error
     */
    public static function trigger_transfer_of_new_leads( $selected_records = [] ) {

        $transfer_records = [];

        // get option
        $home = get_option( 'dt_webform_site_api_keys' );
        if ( ! isset( $home ) || empty( $home ) ) {
            // set auto post to false
            $options = get_option( 'dt_webform_options' );
            $options['auto_approve'] = false;
            update_option( 'dt_webform_options', $options, false );

            // respond with error
            return new WP_Error( 'site_settings_not_set', 'Site keys are empty.' );
        }
        foreach ( $home as $key => $value ) {
            $id = $value['id'];
            $token = $value['token'];
            $url = $value['url'];
            break;
        }

        // get entire record from selected records
        foreach ( $selected_records as $record ) {
            array_push( $transfer_records, dt_get_simple_post_meta( $record ) );
        }

        // Create hash key and url
        $md5_hash_id = DT_Webform_API_Keys::one_hour_encryption( $id );

        // Send remote request
        $args = [
            'method' => 'GET',
            'body' => [
                'id' => $md5_hash_id,
                'token' => $token,
                'selected_records' => $transfer_records,
            ]
        ];
        $result = wp_remote_get( $url . '/wp-json/dt-public/v1/webform/transfer_collection', $args );

        if ( is_wp_error( $result ) ) {
            return new WP_Error( 'failed_remote_get', $result->get_error_message() );
        }

        if ( isset( $result['body'] ) && ! empty( $result['body'] ) && count( $result['body'] ) > 0 ) {
            $records = json_decode( $result['body'] );

            foreach ( $records as $record ) {
                wp_delete_post( $record, true );
            }

            dt_write_log( 'Start deleting process' );
            dt_write_log( $records );
        }

        return true;
    }

    public static function get_custom_css( $token ) {

    }
}
