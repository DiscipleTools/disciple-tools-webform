<?php

/**
 * Class DT_Webform_Settings
 */
class DT_Webform_Settings
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

    public static function auto_approve_metabox()
    {
        // Check for post
        if ( isset( $_POST['dt_webform_auto_approve_nonce'] ) && ! empty( $_POST['dt_webform_auto_approve_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dt_webform_auto_approve_nonce'] ) ), 'dt_webform_auto_approve' ) ) {

            $options = get_option( 'dt_webform_options' );
            if ( isset( $_POST['auto_approve'] ) ) {
                $options['auto_approve'] = true;
            } else {
                $options['auto_approve'] = false;
            }

            update_option( 'dt_webform_options', $options, false );
        }

        // Get status of auto approve
        $options = get_option( 'dt_webform_options' );
        if ( ! self::sites_keys_set() ) {
            self::set_auto_approve_to_false();
            $options['auto_approve'] = false;
        }

        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'dt_webform_auto_approve', 'dt_webform_auto_approve_nonce', false, true ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <th>Auto Approve</th>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <label for="auto_approve">Auto Approve: </label>
                        <input type="checkbox" id="auto_approve" name="auto_approve" value="1" <?php echo ( $options['auto_approve'] ) ? 'checked' : ''; ?> />
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

    public static function statistics_for_all_forms() {
        // query all forms
        $form_object = new WP_Query( [ 'post_type' => 'dt_webform_forms' ] );
        if ( is_wp_error( $form_object ) || $form_object->found_posts < 1 ) {
            return [
                'leads_received' => 0,
                'leads_transferred' => 0,
            ];
        }

        $leads_received = 0;
        $leads_transferred = 0;

        foreach ( $form_object->posts as $record ) {
            $received = get_post_meta( $record->ID, 'leads_received' );
            if ( $received ) {
                $leads_received += (int) $received;
            }
            $transferred = get_post_meta( $record->ID, 'leads_transferred' );
            if ( $transferred ) {
                $leads_transferred += (int) $transferred;
            }
        }

        return [
          'leads_received' => $leads_received,
          'leads_transferred' => $leads_transferred,
        ];
    }

    public static function set_auto_approve_to_false() {
        $options = get_option( 'dt_webform_options' );
        $options['auto_approve'] = false;
        update_option( 'dt_webform_options', $options, false );
    }

    public static function sites_keys_set() {
        if ( 'remote' == get_option( 'dt_webform_state' ) ) {
            DT_Site_Link_System::verify_sites_keys_are_set();
        }
        return true;
    }

    /**
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

}

/**
 * This returns a simple array versus the multi dimensional array from the get_user_meta function
 *
 * @return array
 */
function dt_get_simple_post_meta( $post_id ) {
    $map = array_map( function ( $a ) { return $a[0];
    }, get_post_meta( $post_id ) ); // map the post meta
    $map['ID'] = $post_id; // add the id to the array
    return $map;
}