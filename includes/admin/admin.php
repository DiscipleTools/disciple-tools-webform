<?php

/**
 * Class DT_Webform_Admin
 */
class DT_Webform_Admin
{
    /**
     * Re-usable form to edit state of the plugin.
     */
    public static function initialize_plugin_state_metabox()
    {
        // Set selections
        $options = [
        [
        'key'   => 'combined',
        'label' => __( 'Combined', 'dt_webform' ),
        ],
        [
        'key'   => 'home',
        'label' => __( 'Home', 'dt_webform' ),
        ],
        ];

        // Check if Disciple Tools Theme is present. If not, limit select to remote server.
        $current_theme = get_option( 'current_theme' );
        if ( ! 'Disciple Tools' == $current_theme ) {
            $options = [
            [
            'key'   => 'remote',
            'label' => __( 'Remote', 'dt_webform' ),
            ],
            ];
        }

        // Get current selection
        $state = get_option( 'dt_webform_state' );
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'dt_webform_select_state', 'dt_webform_select_state_nonce', true, true ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <th>Plugin State</th>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <label for="initialize_plugin_state">Configure the state of the plugin: </label><br>
                        <select name="initialize_plugin_state" id="initialize_plugin_state">
                            <option value="">Select</option>
                            <option value="" disabled>---</option>
                            <?php
                            foreach ( $options as $option ) {
                                echo '<option value="' . esc_attr( $option['key'] ) . '" ';
                                if ( $option['key'] == $state ) {
                                    echo 'selected';
                                }
                                echo '>' . esc_attr( $option['label'] ) . '</option>';
                            }
                            ?>
                        </select>
                        <span><button class="button-like-link" type="button" onclick="jQuery('#state-help').toggle();">explain settings</button></span>
                    </td>
                </tr>
                <tr id="state-help" style="display: none;">
                    <td>
                        Three different configurations:
                        <p>
                            <strong>Home</strong><br>
                            Home configuration sets up just the half of the plugin that integrates with Disciple Tools.
                            Choosing this option assumes that you have a remote server running separatedly with the
                            'remote' setting on the plugin configured.
                        </p>
                        <p>
                            <strong>Remote</strong><br>
                            The 'Remote' configuration sets up only the remote webform server. Choosing this option
                            assumes that you have a Disciple Tools server running elsewhere with the Webform plugin
                            installed and configured as 'home'. If Disciple Tools Theme is not installed, Remote will be
                            the only installation option.
                        </p>
                        <p>
                            <strong>Combined</strong><br>
                            The 'Combined' configuration sets up the Webform plugin to run the webform server from the
                            same system as the Disciple Tools System. Choosing this option assumes that you have a
                            remote server running separatedly with the 'remote' setting on the plugin configured.
                        </p>

                    </td>
                </tr>
                <tr>
                    <td>
                        <button class="button" type="submit">Update</button>
                    </td>
                </tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
        </form>
        <?php
    }

}

/**
 * This returns a simple array versus the multi dimensional array from the get_user_meta function
 *
 * @return array
 */
function dt_get_simple_post_meta( $post_id ) {
    return array_map( function ( $a ) { return $a[0];
    }, get_post_meta( $post_id ) );
}