<?php
/**
 * Collection of various utilities
 */

class DT_Webform_Remote
{
    public static function site_link_metabox()
    {
        $prefix = 'dt_webform_site';
        $keys = DT_Webform_Api_Keys::update_keys( $prefix );

        foreach ( $keys as $key ) {
            $home_link = $key;
        } // end foreach

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
                               value="<?php echo ( isset( $home_link['id'] ) ) ? esc_attr( $home_link['id'] ) : '' ?>" required/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="token"><?php esc_html_e( 'Token', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="token" id="token" class="regular-text"
                               value="<?php echo ( isset( $home_link['token'] ) ) ? esc_attr( $home_link['token'] ) : '' ?>" required/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="url"><?php esc_html_e( 'Home URL', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="url" id="url" class="regular-text" placeholder="http://www.website.com"
                               value="<?php echo ( isset( $home_link['url'] ) ) ? esc_attr( $home_link['url'] ) : '' ?>" required/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="submit" class="button" name="action" value="update"><?php esc_html_e( 'Update', 'dt_webform' ) ?></button>
                        <span class="float-right">
                            <button type="submit" class="button-like-link" name="action" value="delete"><?php esc_html_e( 'Unlink Site', 'dt_webform' ) ?></button>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span style="float:right">
                                <?php esc_html_e( 'Status:', 'dt_webform' ) ?>
                            <strong>
                                    <span id="<?php echo esc_attr( $key['id'] ); ?>-status">
                                        <?php esc_html_e( 'Checking Status', 'dt_webform' ) ?>
                                    </span>
                                </strong>
                            </span>
                        <script>
                            jQuery(document).ready(function() {
                                check_link_status( '<?php echo esc_attr( $key['id'] ); ?>', '<?php echo esc_attr( $key['token'] ); ?>', '<?php echo esc_attr( $key['url'] ); ?>' );
                            })
                        </script>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }

    public static function manual_transfer_of_new_lead( $selected_records ) {
        // send trigger to create contacts from new lead array records
    }

}
