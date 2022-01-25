<?php

add_action( 'after_setup_theme', 'register_webform_shortcodes' );
function register_webform_shortcodes() {
    add_shortcode( 'dt-webform', 'webform_shortcodes_handler' );
}

function webform_shortcodes_handler( $atts ): string {
    $params = shortcode_atts( array(
        'id'          => '', // Blank post id
        'button_only' => 'true', // If true, return url; otherwise return form elements
        'campaigns'   => '' // Additional campaign metadata
    ), $atts );

    // Fetch corresponding form token
    $token = get_metadata( 'post', $params['id'], 'token', true );

    // Determine required response, assuming token is valid
    if ( ! empty( $token ) ) {

        // Determine required response
        if ( $params['button_only'] === 'true' ) {

            // Construct public url
            $public_url = trailingslashit( trailingslashit( plugin_dir_url( __DIR__ ) ) . 'public' ) . 'form.php?token=' . esc_attr( $token );

            // If present, append additional url parameters
            $campaigns  = ! empty( $params['campaigns'] ) ? '&campaigns=' . esc_attr( $params['campaigns'] ) : '';
            $public_url .= $campaigns;

            return $public_url;

        } else {

            // Call required helper classes
            require_once( 'utilities.php' );
            require_once( 'post-type-active-forms.php' );

            // Determine meta and associated form fields
            $dt_webform_meta        = DT_Webform_Utilities::get_form_meta( $token );
            $dt_webform_core_fields = DT_Webform_Active_Form_Post_Type::get_core_fields_by_token( $token );
            $dt_webform_fields      = DT_Webform_Active_Form_Post_Type::get_extra_fields( $token );

            // Generate html to be returned
            $form_html    = DT_Webform_Utilities::get_form_html( $token, get_metadata_campaigns( $params['campaigns'] ), $dt_webform_core_fields, $dt_webform_fields );
            $scripts_html = DT_Webform_Utilities::get_form_html_scripts_and_styles( $token, $dt_webform_meta, $dt_webform_fields );

            // Generate html to be returned
            return "
            <div id=\"wrapper\">
                <form id=\"contact-form\" action=\"\">
                    {$form_html}
                </form>

                <div id=\"report\"></div>
                <div id=\"offlineWarningContainer\"></div>

                {$scripts_html}
            </div>
            ";
        }
    }

    return '';
}

function get_metadata_campaigns( $atts_campaigns ): string {

    /**
     * Default to incoming request campaigns param; otherwise,
     * revert to shortcode campaigns attribute; if present!
     */

    $request_campaigns = ! empty( $_GET['campaigns'] ) ? sanitize_text_field( wp_unslash( $_GET['campaigns'] ) ) : '';

    return ! empty( $request_campaigns ) ? $request_campaigns : $atts_campaigns ?? '';
}
