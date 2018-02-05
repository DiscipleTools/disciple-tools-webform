<?php
/**
 * Collection of various utilities
 */

class DT_Webform_Home
{
    public static function site_api_link_metabox() {

        $prefix = 'dt_webform_site';
        $keys = DT_Webform_Api_Keys::update_keys( $prefix );

        ?>
        <h3><?php esc_html_e( 'API Keys', 'dt_webform' ) ?></h3>
        <p></p>
        <form action="" method="post">
            <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
            <h2><?php esc_html_e( 'Token Generator', 'dt_webform' ) ?></h2>
            <table class="widefat striped">
                <tr>
                    <td><label for="id"><?php esc_html_e( 'Name', 'dt_webform' ) ?></label></td>
                    <td><input type="text" id="id" name="id" required> (Case Sensitive)</td>
                </tr>
                <tr>
                    <td><label for="url"><?php esc_html_e( 'Remote URL', 'dt_webform' ) ?></label></td>
                    <td><input type="text" id="url" name="url" placeholder="http://www.website.com" required>
                        <button type="submit" class="button" name="action" value="create"><?php esc_html_e( 'Generate Token', 'dt_webform' ) ?></button>
                    </td>
                </tr>
            </table>
        </form>
        <h2><?php esc_html_e( 'Existing Keys', 'dt_webform' ) ?></h2>
        <?php
        if ( ! empty( $keys ) || ! is_wp_error( $keys ) ) :
            foreach ( $keys as $key ): ?>
                <form action="" method="post"><!-- begin form -->
                <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
                <input type="hidden" name="id" value="<?php echo esc_html( $key['id'] ); ?>" />

                <table class="widefat">
                    <thead>
                    <tr>
                        <td colspan="2"><?php esc_html_e( 'Setup information for ', 'dt_webform' ) ?>"<?php echo esc_html( $key['id'] ); ?>"</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php esc_html_e( 'ID', 'dt_webform' ) ?></td>
                        <td><?php echo esc_html( $key['id'] ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Token', 'dt_webform' ) ?></td>
                        <td><?php echo esc_html( $key['token'] ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Home URL', 'dt_webform' ) ?></td>
                        <td><?php echo esc_html( home_url() ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Remote URL', 'dt_webform' ) ?></td>
                        <td><?php echo esc_html( $key['url'] ); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="button" class="button-like-link" onclick="jQuery('#delete-<?php echo esc_html( $key['id'] ); ?>').show();">
                                <?php esc_html_e( 'Delete', 'dt_webform' ) ?>
                            </button>
                            <p style="display:none;" id="delete-<?php echo esc_html( $key['id'] ); ?>">
                                <?php esc_html_e( 'Are you sure you want to delete this record? This is a permanent action.', 'dt_webform' ) ?><br>
                                <button type="submit" class="button" name="action" value="delete">
                                    <?php esc_html_e( 'Permanently Delete', 'dt_webform' ) ?>
                                </button>
                            </p>

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
                    <br>
                </table>

                </form><!-- end form -->
            <?php endforeach;  ?>
        <?php else : ?>
            <p>No stored keys. To add a key use the token generator to create a key.</p>
        <?php endif; ?>
        <?php

    }

    /**
     * @param $new_lead_id
     *
     * @return bool|\WP_Error
     */
    public static function create_contact_record( $new_lead_id ) {

        $check_permission = false;

        $new_lead_meta = dt_get_simple_post_meta( $new_lead_id );

        // Get the id of the form source of the lead
        if ( ! isset( $new_lead_meta['token'] ) || empty( $new_lead_meta['token'] ) ) {
            dt_write_log( 'Missing token' );
            return new WP_Error( 'missing_contact_info', 'Missing token' );
        }

        // Build record
        if ( ! isset( $new_lead_meta['name'] ) || empty( $new_lead_meta['name'] ) ) {
            dt_write_log( 'Missing name' );
            return new WP_Error( 'missing_contact_info', 'Missing name' );
        }

        if ( ! isset( $new_lead_meta['form_title'] ) || empty( $new_lead_meta['form_title'] ) ) {
            $form_title = 'unknown (token: ' . $new_lead_meta['token'];
        } else {
            $form_title = $new_lead_meta['form_title'];
        }

        $fields = [
            'title' => $new_lead_meta['name'],
            'phone' => ( isset( $new_lead_meta['phone'] ) ) ? $new_lead_meta['phone'] : '',
            'email' => ( isset( $new_lead_meta['email'] ) ) ? $new_lead_meta['email'] : '',
            'initial_comment' => __( 'Original Source: Webform ' ) . '(' . $form_title . ')',
        ];

        // Post to contact
        $result = Disciple_Tools_Contacts::create_contact( $fields, $check_permission );
        if ( is_wp_error( $result ) ) {
            dt_write_log( 'failed_to_insert_contact' );
            return new WP_Error( 'failed_to_insert_contact', $result->get_error_message() );
        }

        // Delete new lead after success
        $delete_result = wp_delete_post( $new_lead_id, true );
        if ( is_wp_error( $delete_result ) ) {
            dt_write_log( 'failed_to_delete_contact' );
            return new WP_Error( 'failed_to_delete_contact', $result->get_error_message() );
        }

        return $result;
    }
}