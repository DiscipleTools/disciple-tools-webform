<?php


add_action( 'after_setup_theme', 'register_webform_shortcodes' );
function register_webform_shortcodes() {
    add_shortcode( 'dt-webform', 'webform_shortcodes_handler' );
}

function webform_shortcodes_handler( $atts ): string {
    $params = shortcode_atts( array(
        'id'    => '', // Blank post id
        'fetch' => 'false' // If false, return url; otherwise return form elements
    ), $atts );

    // Fetch corresponding form token
    $token = get_metadata( 'post', $params['id'], 'token', true );

    // Determine required response, assuming token is valid
    if ( ! empty( $token ) ) {

        // Construct public url
        $public_url = trailingslashit( trailingslashit( plugin_dir_url( __DIR__ ) ) . 'public' ) . 'form.php?token=' . esc_attr( $token );

        // Determine required response
        if ( $params['fetch'] === 'false' ) {
            return $public_url;

        } else {
            $response = wp_remote_get( $public_url, [
                'headers' => [
                    'Content-Type' => 'text/html'
                ]
            ] );

            // If valid, cheery-pick html elements of interest
            if ( is_array( $response ) && ! is_wp_error( $response ) ) {

                // Convert to dom and extract elements of interest
                $dom = new DOMDocument();
                @$dom->loadHTML( $response['body'] );

                // Process any referenced scripts and capture embedded ones!
                $embedded_scripts = webform_shortcodes_elements( $dom, 'script' );

                // Capture identified styles to be embedded
                $embedded_styles = webform_shortcodes_elements( $dom, 'style' );

                /**
                 * Concatenate existing form, along with any identified embedded scripts and styles.
                 * Ensure to include required divs!
                 */

                $html = $dom->saveHTML( $dom->getElementById( 'wrapper' ) );

                /*$html = '<div id="wrapper">';
                $html .= $dom->saveHTML( $dom->getElementsByTagName( 'form' )->item( 0 ) );
                $html .= '<div id="report"></div>';
                $html .= '<div id="offlineWarningContainer"></div>';
                $html .= '</div>';*/

                $html .= ! empty( $embedded_scripts ) ? implode( ' ', $embedded_scripts ) : '';
                $html .= ! empty( $embedded_styles ) ? implode( ' ', $embedded_styles ) : '';

                return $html;
            }
        }
    }

    return '';
}

function webform_shortcodes_elements( DOMDocument $dom, $name ): array {
    $elements = [];
    foreach ( $dom->getElementsByTagName( $name ) as $element ) {
        $elements[] = $dom->saveHTML( $element );
    }

    return $elements;
}


function webform_shortcodes_scripts( DOMDocument $dom ): array {
    $embedded_scripts = [];
    foreach ( $dom->getElementsByTagName( 'script' ) as $script ) {

        // Differentiate between referenced scripts and embedded
        if ( ! empty( $script->attributes->getNamedItem( 'src' ) ) ) {

            // Dynamically generate wp enqueue script for referenced files
            $src = $script->attributes->getNamedItem( 'src' );
            wp_enqueue_script( time(), $src->value );

        } else {

            // Capture embedded scripts to be returned with form
            $embedded_scripts[] = $dom->saveHTML( $script );
        }
    }

    return $embedded_scripts;
}

function webform_shortcodes_styles( DOMDocument $dom ): array {
    $embedded_styles = [];
    foreach ( $dom->getElementsByTagName( 'style' ) as $style ) {
        $embedded_styles[] = $dom->saveHTML( $style );
    }

    return $embedded_styles;
}
