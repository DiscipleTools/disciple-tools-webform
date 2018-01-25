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
                    <td><input type="text" id="id" name="id" required></td>
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

}