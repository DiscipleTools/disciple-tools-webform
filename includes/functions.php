<?php

/* CORE FUNCTIONS */
if ( ! function_exists( 'is_dt' ) ) {
    function is_dt(): bool
    {
        $wp_theme = wp_get_theme();

        // child theme check
        if ( get_template_directory() !== get_stylesheet_directory() ) {
            if ( 'disciple-tools-theme' == $wp_theme->get( 'Template' ) ) {
                return true;
            }
        }

        // main theme check
        $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
        if ($is_theme_dt) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists( 'dt_is_child_theme_of_disciple_tools' ) ) {
    /**
     * Returns true if this is a child theme of Disciple Tools, and false if it is not.
     *
     * @return bool
     */
    function dt_is_child_theme_of_disciple_tools() : bool {
        if ( get_template_directory() !== get_stylesheet_directory() ) {
            $current_theme = wp_get_theme();
            if ( 'disciple-tools-theme' == $current_theme->get( 'Template' ) ) {
                return true;
            }
        }
        return false;
    }
}

/**
 * A simple function to assist with development and non-disruptive debugging.
 * -----------
 * -----------
 * REQUIREMENT:
 * WP Debug logging must be set to true in the wp-config.php file.
 * Add these definitions above the "That's all, stop editing! Happy blogging." line in wp-config.php
 * -----------
 * define( 'WP_DEBUG', true ); // Enable WP_DEBUG mode
 * define( 'WP_DEBUG_LOG', true ); // Enable Debug logging to the /wp-content/debug.log file
 * define( 'WP_DEBUG_DISPLAY', false ); // Disable display of errors and warnings
 * @ini_set( 'display_errors', 0 );
 * -----------
 * -----------
 * EXAMPLE USAGE:
 * (string)
 * write_log('THIS IS THE START OF MY CUSTOM DEBUG');
 * -----------
 * (array)
 * $an_array_of_things = ['an', 'array', 'of', 'things'];
 * write_log($an_array_of_things);
 * -----------
 * (object)
 * $an_object = new An_Object
 * write_log($an_object);
 */
if ( !function_exists( 'dt_write_log' ) ) {
    /**
     * A function to assist development only.
     * This function allows you to post a string, array, or object to the WP_DEBUG log.
     *
     * @param $log
     */
    function dt_write_log( $log ) {
        if ( true === WP_DEBUG ) {
            global $dt_write_log_microtime;
            $now = microtime( true );
            if ( $dt_write_log_microtime > 0 ) {
                $elapsed_log = sprintf( "[elapsed:%5dms]", ( $now - $dt_write_log_microtime ) * 1000 );
            } else {
                $elapsed_log = "[elapsed:-------]";
            }
            $dt_write_log_microtime = $now;
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( $elapsed_log . " " . print_r( $log, true ) );
            } else {
                error_log( "$elapsed_log $log" );
            }
        }
    }
}


if ( !function_exists( 'dt_is_rest' ) ) {
    /**
     * Checks if the current request is a WP REST API request.
     *
     * Case #1: After WP_REST_Request initialisation
     * Case #2: Support "plain" permalink settings
     * Case #3: URL Path begins with wp-json/ (your REST prefix)
     *          Also supports WP installations in subfolders
     *
     * @returns boolean
     * @author matzeeable
     */
    function dt_is_rest( $namespace = null ) {
        $prefix = rest_get_url_prefix();
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST
            || isset( $_GET['rest_route'] )
            && strpos( trim( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '\\/' ), $prefix, 0 ) === 0 ) {
            return true;
        }
        $rest_url    = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array() ) );
        $is_rest = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
        if ( $namespace ){
            return $is_rest && strpos( $current_url['path'], $namespace ) != false;
        } else {
            return $is_rest;
        }
    }
}

/**
 * Admin alert for when Disciple Tools Theme is not available
 */
if ( ! function_exists( 'dt_webform_no_disciple_tools_theme_found' ) ) {
    function dt_webform_no_disciple_tools_theme_found() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( "'Disciple Tools - Webform' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or deactivate 'Disciple Tools - Webform' plugin.", "dt_webform" ); ?></p>
        </div>
        <?php
    }
}

if ( ! function_exists( 'dt_sanitize_array' ) ) {
    function dt_sanitize_array( &$array ) {
        foreach ($array as &$value) {
            if ( !is_array( $value ) ) {
                $value = sanitize_text_field( wp_unslash( $value ) );
            } else {
                dt_sanitize_array( $value );
            }
        }
        return $array;
    }
}

/**
 * This returns a simple array versus the multi dimensional array
 *
 * @return array
 */
if ( ! function_exists( 'dt_get_simple_post_meta' ) ) {
    function dt_get_simple_post_meta( $post_id ) {

        $map = wp_cache_get( __METHOD__, $post_id );
        if ( $map ) {
            return $map;
        }

        $map = [];
        if ( ! empty( $post_id ) ) {
            $map         = array_map( function( $a ) {
                return maybe_unserialize( $a[0] );
            }, get_post_meta( $post_id ) ); // map the post meta
            $map['ID'] = $post_id; // add the id to the array
        }

        wp_cache_set( __METHOD__, $map, $post_id );

        return $map;
    }
}

if ( ! function_exists( 'dt_get_location_grid_mirror' ) ) {
    /**
     * Best way to call for the mapping polygon
     * @return array|string
     */
    function dt_get_location_grid_mirror( $url_only = false ) {

        $mirror = wp_cache_get( __METHOD__, $url_only );
        if ( $mirror ) {
            return $url_only ? $mirror["url"] : $mirror;
        }

        $mirror = get_option( 'dt_location_grid_mirror' );
        if ( empty( $mirror ) ) {
            $array = [
                'key'   => 'google',
                'label' => 'Google',
                'url'   => 'https://storage.googleapis.com/location-grid-mirror/',
            ];
            update_option( 'dt_location_grid_mirror', $array, true );
            $mirror = $array;
        }

        wp_cache_set( __METHOD__, $mirror, $url_only );

        if ( $url_only ) {
            return $mirror['url'];
        }

        return $mirror;
    }
}

if ( ! function_exists( 'dt_get_mapbox_endpoint' ) ) {
    function dt_get_mapbox_endpoint( $type = 'places' ) : string {
        switch ( $type ) {
            case 'permanent':
                return 'https://api.mapbox.com/geocoding/v5/mapbox.places-permanent/';
                break;
            case 'places':
            default:
                return 'https://api.mapbox.com/geocoding/v5/mapbox.places/';
                break;
        }
    }
}

if ( ! function_exists( 'dt_get_webform_site_link' ) ) {
    function dt_get_webform_site_link() {
        return get_option( 'dt_webform_site_link' );
    }
}

if ( ! function_exists( 'dt_has_permissions' ) ) {
    function dt_has_permissions( array $permissions ) : bool {
        if ( count( $permissions ) > 0 ) {
            foreach ( $permissions as $permission ){
                if ( current_user_can( $permission ) ){
                    return true;
                }
            }
        }
        return false;
    }
}

if ( ! function_exists( 'dt_get_url_path' ) ) {
    function dt_get_url_path() {
        if ( isset( $_SERVER["HTTP_HOST"] ) ) {
            $url  = ( !isset( $_SERVER["HTTPS"] ) || @( $_SERVER["HTTPS"] != 'on' ) ) ? 'http://'. sanitize_text_field( wp_unslash( $_SERVER["HTTP_HOST"] ) ) : 'https://'. sanitize_text_field( wp_unslash( $_SERVER["HTTP_HOST"] ) );
            if ( isset( $_SERVER["REQUEST_URI"] ) ) {
                $url .= sanitize_text_field( wp_unslash( $_SERVER["REQUEST_URI"] ) );
            }
            return trim( str_replace( get_site_url(), "", $url ), '/' );
        }
        return '';
    }
}
