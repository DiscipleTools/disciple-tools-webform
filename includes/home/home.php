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
                    <td>
                        <label for="<?php echo esc_attr( $prefix ).'_id' ?>">Name</label>
                    </td>
                    <td>
                        <input type="text" id="<?php echo esc_attr( $prefix ).'_id' ?>" name="<?php echo esc_attr( $prefix ) . '_id' ?>">
                        <button type="submit" class="button">Generate Token</button>
                    </td>
                </tr>
            </table>
            <h2><?php esc_html_e( 'Existing Keys', 'dt_webform' ) ?></h2>

            <?php
            if ( ! empty( $keys ) ) :
                foreach ( $keys as $id => $key ): ?>
                    <table class="widefat">
                        <thead>
                        <tr>
                            <th colspan="2"><?php esc_html_e( 'Setup information for ', 'dt_webform' ) ?>"<?php echo esc_html( $key["id"] ); ?>"</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><?php esc_html_e( 'ID', 'dt_webform' ) ?></td>
                            <td><?php echo esc_html( $key["id"] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Token', 'dt_webform' ) ?></td>
                            <td><?php echo esc_html( $key["token"] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'URL', 'dt_webform' ) ?></td>
                            <td><?php echo esc_html( $key["url"] ); ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <button type="button" class="button-like-link" onclick="jQuery('#delete-<?php echo esc_html( $key["id"] ); ?>').show();">Delete
                                </button>
                                <p style="display:none;" id="delete-<?php echo esc_html( $key["id"] ); ?>">
                                    Are you sure you want to delete this record? This is a permanent action.<br>
                                    <button type="submit" class="button" name="delete"
                                            value="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Permanently Delete', 'dt_webform' ) ?>
                                    </button>
                                </p>
                            </td>
                        </tr>
                        </tbody>
                        <br>
                    </table>
                <?php endforeach;  ?>

            <?php else : ?>
                <p>No stored keys. To add a key use the token generator to create a key.</p>

            <?php endif; ?>

        </form>
        <?php

    }
}