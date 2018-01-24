<?php
/**
 * Collection of various utilities
 */

class DT_Webform_Remote
{

    public static function site_link_metabox()
    {
        /**
         * Process $_POST
         */
        if ( isset( $_POST['remote_api_link_form'] ) && isset( $_POST['dt_webform_remote_api_link_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_webform_remote_api_link_nonce'] ) ), 'dt_webform_remote_api_link' ) ) {
            $remote = get_option( 'dt_webform_remote_settings' );
            if ( ! $remote ) {
                DT_Webform_Admin::initialize_plugin_state_metabox();
                $remote = get_option( 'dt_webform_remote_settings' );
            }

            $remote['api_link']['client_id'] = ( ! isset( $_POST['client_id'] ) || empty( $_POST['client_id'] ) ) ? '' : sanitize_text_field( wp_unslash( $_POST['client_id'] ) );
            $remote['api_link']['client_token'] = ( ! isset( $_POST['client_token'] ) || empty( $_POST['client_token'] ) ) ? '' : sanitize_key( wp_unslash( $_POST['client_token'] ) );
            $remote['api_link']['client_url'] = ( ! isset( $_POST['client_url'] ) || empty( $_POST['client_url'] ) ) ? '' : sanitize_text_field( wp_unslash( $_POST['client_url'] ) );

            update_option( 'dt_webform_remote_settings', $remote, false );
        }


        $remote = get_option( 'dt_webform_remote_settings' );
        if ( ! $remote ) {
            DT_Webform_Admin::initialize_plugin_state_metabox();
            $remote = get_option( 'dt_webform_remote_settings' );
        }
        ?>
        <!-- Box -->
        <form method="post" action="">
            <?php wp_nonce_field( 'dt_webform_remote_api_link', 'dt_webform_remote_api_link_nonce', true, true ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <td colspan="2">
                        API Link to Webform Home
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="max-width:20px;">
                        <label for="client_id">Client ID</label>
                    </td>
                    <td>
                        <input type="text" name="client_id" id="client_id"
                               value="<?php echo esc_attr( $remote['api_link']['client_id'] ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="client_token">Client Token</label>
                    </td>
                    <td>
                        <input type="text" name="client_token" id="client_token"
                               value="<?php echo esc_attr( $remote['api_link']['client_token'] ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="client_url">Client URL</label>
                    </td>
                    <td>
                        <input type="text" name="client_url" id="client_url"
                               value="<?php echo esc_attr( $remote['api_link']['client_url'] ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <button type="submit" class="button" name="remote_api_link_form" value="1">Update</button>
                    </td>
                    <td>

                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }
}