<?php

add_action( 'after_setup_theme', 'register_webform_shortcodes' );
function register_webform_shortcodes() {
    add_shortcode( 'dt-webform', 'webform_shortcodes_handler' );
}

function webform_shortcodes_handler( $atts ): string {
    $params = shortcode_atts( array(
        'id'          => '', // Blank post id
        'button_only' => 'false', // If true, return url; otherwise return form elements
        'campaigns'   => '', // Additional campaign metadata
        'source' => '' // Additional source metadata
    ), $atts );

    // Fetch corresponding form token
    $token = get_metadata( 'post', $params['id'], 'token', true );

    // Determine required response, assuming token is valid
    if ( ! empty( $token ) ) {

        // Determine required response
        if ( $params['button_only'] === 'true' ) {

            // Construct public url
            if ( is_this_dt() ){
                $magic_link_key_value = get_post_meta( $params['id'], 'webform_ml_magic_key', true ) ?? $token;
                $public_url = site_url( '/webform/ml/' . $magic_link_key_value );
            } else {
                $public_url = trailingslashit( trailingslashit( plugin_dir_url( __DIR__ ) ) . 'public' ) . 'form.php?token=' . esc_attr( $token );
            }

            // If present, append additional url parameters
            $ign = is_this_dt() ? '?' : '&';
            $campaigns  = ! empty( $params['campaigns'] ) ? $ign . 'campaigns=' . esc_attr( $params['campaigns'] ) : '';
            $public_url .= $campaigns;

            $ource = !empty( $params['source'] ) ? ( empty( $campaigns ) ? '?' : '&' ) . 'source=' . esc_attr( $params['source'] ) : '';
            $public_url .= $ource;

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
            $public_url = trailingslashit( plugin_dir_url( __DIR__ ) ) . 'public/';
            $form_html    = DT_Webform_Utilities::get_form_html( $token, get_metadata_param( 'campaigns', $params['campaigns'] ), get_metadata_param( 'source', $params['source'] ), $dt_webform_core_fields, $dt_webform_fields, $public_url );
            $scripts_html = DT_Webform_Utilities::get_form_html_scripts_and_styles( $token, $dt_webform_meta, $dt_webform_fields, $public_url );

            // Generate html to be returned
            return "
            <div id=\"wrapper\">
                {$scripts_html}
                <form id=\"contact-form\" action=\"\">
                    {$form_html}
                </form>

                <div id=\"report\"></div>
                <div id=\"offlineWarningContainer\"></div>

            </div>
            ";
        }
    }

    return '';
}

function get_metadata_param( $key, $atts ): string {

    /**
     * Default to incoming request param; otherwise,
     * revert to shortcode attribute; if present!
     */

    $request = ! empty( $_GET[$key] ) ? sanitize_text_field( wp_unslash( $_GET[$key] ) ) : '';

    return ! empty( $request ) ? $request : $atts ?? '';
}
