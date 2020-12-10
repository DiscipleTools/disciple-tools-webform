<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly


/**
 * Class DT_Webform_Utilities
 */
class DT_Webform_Utilities {

    public static function get_form_meta( $token ) {

        if ( empty( $token ) ) {
            return false;
        }
        $meta = wp_cache_get( 'get_form_meta', $token );
        if ( $meta) {
            return maybe_unserialize( $meta );
        }

        global $wpdb;

        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1", $token ) );
        $meta = dt_get_simple_post_meta( $post_id );

        if ( isset( $meta['_edit_last'] ) ) {
            unset( $meta['_edit_last'] );
        }
        if ( isset( $meta['_edit_lock'] ) ) {
            unset( $meta['_edit_lock'] );
        }

        wp_cache_set( 'get_form_meta', $meta, $token );

        return $meta;
    }

    public static function get_custom_css( $token ) {

        if ( empty( $token ) ) {
            return false;
        }

        $meta = wp_cache_get( 'get_custom_css', $token );
        if ( $meta ) {
            return $meta;
        }

        global $wpdb;
        $css = $wpdb->get_var( $wpdb->prepare( "
            SELECT meta_value
            FROM $wpdb->postmeta
            WHERE post_id = ( SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1 )
            AND meta_key = 'custom_css'
            LIMIT 1", $token ) );

        wp_cache_set( 'get_custom_css', $css, $token );

        return $css;
    }

    public static function order_custom_field_array( $custom_fields ) {
        // reorder
        $order = [];
        foreach ( $custom_fields as $value ) {
            $value = maybe_unserialize( $value );
            if ( ! isset( $value['order'] ) || $value['order'] < 1 ) {
                $value['order'] = 1;
            }
            if ( ! isset( $order[$value['order']] ) ) {
                $order[$value['order']] = [];
            }
            $order[$value['order']][$value['key']] = $value;
        }
        ksort( $order );

        $ordered_fields = [];
        foreach ( $order as $value ) {
            foreach ( $value as $k => $v ) {
                $ordered_fields[$k] = $v;
            }
        }

        return $ordered_fields;
    }

    public static function get_contact_defaults( $force = false ) {

        if ( is_this_dt() ) {
            Disciple_Tools::instance();
            return DT_Posts::get_post_field_settings( 'contacts' );
        }

        // caching
        $contact_defaults = get_transient( 'dt_webform_contact_defaults' );
        if ( $contact_defaults && ! $force ) {
            return $contact_defaults;
        }

        $site_id = dt_get_webform_site_link();
        if ( ! $site_id ) {
            return new WP_Error( __METHOD__, 'Not site link set.' );
        }

        $site = Site_Link_System::get_site_connection_vars( $site_id );
        if ( ! $site || is_wp_error( $site ) ) {
            return new WP_Error( __METHOD__, 'Missing site to site data' );
        }


        $args = [
            'method' => 'POST',
            'body' => [
                'transfer_token' => $site['transfer_token'],
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $site['transfer_token'],
            ],
        ];

        $result = wp_remote_post( 'https://' . trailingslashit( $site['url'] ) . 'wp-json/dt-public/v2/contacts/settings_fields', $args );
        if ( is_wp_error( $result ) ) {
            return new WP_Error( __METHOD__, $result->get_error_message() );
        }

        $contact_defaults = json_decode( $result['body'], true );
        if ( isset( $contact_defaults['sources'] ) ) {
            set_transient( 'dt_webform_contact_defaults', $contact_defaults, 60 *60 *24 );
            return $contact_defaults;
        } else {
            return new WP_Error( __METHOD__, 'Remote response from DT server malformed.' );
        }

    }

    public static function get_theme( string $theme = 'wide-heavy', string $token = null ) {

        $meta = self::get_form_meta( $token );
        if ( empty( $meta ) ) {
            $meta = [];
        }

        // Unique styles
        switch ( $theme ) {
            case 'simple':
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 400px;
                    }
                    #contact-form {}
                    .input-text {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-communication_channel {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-date {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .2em;
                        font-size: .8em;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        padding: .2em;
                        font-size: .8em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;
                    }
                    .input-multi_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-key_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-dropdown {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-tel {
                       padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-note {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }

                    .hr {}
                    .hr-divider {}
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    .input-label {
                        font-size: 1em;

                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }
                    #title {
                        font-size: 1.2em;
                        font-weight:bold;
                        padding: .5em 0;
                    }
                    #description {
                        padding-bottom: .8em;
                        font-size: .9em;

                    }
                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-header {
                       font-size: 1.2em;
                        font-weight:bold;
                        padding: .5em 0;
                    }
                    .section-description {
                        padding-bottom: .8em;
                        font-size: .9em;

                    }
                    .section-map {
                        margin: 10px 0 ;
                        padding: 10px 0;
                    }
                    fieldset {
                        border:none;
                    }
                    ';
                break;
            case 'heavy':
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 400px;
                    }
                    #contact-form {}
                    .input-text {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-communication_channel {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-date {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .5em;
                        font-size: 1.2em;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        font-size:1.1em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;

                    }
                    .input-multi_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-key_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-dropdown {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-tel {
                        padding: .5em;
                        font-size: 1.2em;
                        line-height: 1.5em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-note {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    .hr {}
                    .hr-divider {}

                    label.error {
                        color: red;
                        font-size: .8em;
                    }

                    .input-label {
                        font-size: 1.2em;

                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }

                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-header {
                        font-size: 2em;
                        font-weight: bolder;
                        padding-top: .5em;
                    }
                    .section-map {
                        margin: 10px 0 ;
                    }

                    #title {
                        font-size: 2em;
                        font-weight: bolder;
                        padding-top: .5em;
                    }
                    #description {
                        padding-bottom: 1em;
                    }
                    fieldset {
                        border:none;
                    }
                    ';
                break;
            case 'none':
                $css = '';
                break;
            case 'minimum':
                $css = '
                    // FORM WRAPPER
                    #wrapper {}
                    #contact-form {}

                    // CORE SECTION AND INPUTS
                    #title {}
                    #description {}
                    #section-name {}
                    #section-phone {}
                    #section-email {}
                    #name {}
                    #phone {}
                    #email {}

                    // SECTION CLASSES
                    .section {}
                    .section-dropdown {}
                    .section-key_select {}
                    .section-multi_select {}
                    .section-multi_radio {}
                    .section-checkbox {}
                    .section-tel {}
                    .section-email {}
                    .section-text {}
                    .section-header {}
                    .section-description {}
                    .section-note {}
                    .section-map {}
                    .section-custom_label {}
                    fieldset {
                        border:none;
                    }

                    // INPUT CLASSES
                    .input-text {}
                    .input-communication_channel {}
                    .input-textarea {}
                    .input-checkbox {}
                    .input-multi_radio {}
                    .input-key_select {}
                    .input-multi_select {}
                    .span-radio {}
                    .input-tel {}
                    .input-email {}
                    .input-dropdown {}
                    .input-note {}
                    button.submit-button {}

                    // LABELS
                    .input-label {}
                    .label-dropdown {}
                    .label-multi_radio {}
                    .label-checkbox {}
                    .label-tel {}
                    .label-email {}
                    .label-text {}
                    .label-note {}
                    .label-map {}
                    .label-map-instructions {}

                    // DIVIDER CLASSES
                    .hr {}
                    .hr-divider {}

                    // ERROR CLASSES
                    label.error {}
                    .offlineWarning {}
                    // EXTRA SECTIONS AND INPUTS
                    ';
                break;
            case 'wide-heavy':
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 1000px;
                    }
                    #contact-form {}

                    #title {
                        font-size: 2em;
                        font-weight: bolder;
                        padding: .5em 0;
                    }
                   #description {
                        padding: .5em 0;
                    }
                    #section-name {}
                    #section-phone {}
                    #section-email {}
                    #name {}
                    #phone {}
                    #email {}

                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-multi_select {}
                    .section-key_select {}
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-tel {}
                    .section-email {}
                    .section-text {}
                    .section-date {}
                    .section-header {
                        font-size: 2em;
                        font-weight: bolder;
                        padding-top: .5em;
                    }
                    .section-description {}
                    .section-note {}
                    .section-map {
                        padding-bottom: 10px;
                    }
                    .section-custom_label {
                        font-size: 1.2em;
                    }

                    .input-text {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-communication_channel {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-date {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .5em;
                        font-size: 1.5em;
                        font-family: Arial;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        font-size:1.1em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;
                    }
                    .input-tel {
                       padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-multi_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-key_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-dropdown {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-note {
                        padding: .5em;
                        font-size: 1.2em;
                        font-family: Arial;
                        width: 100%;
                        border: .5px solid #ccc;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }
                    .input-label {
                        font-size: 1.2em;
                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }
                    .label-checkbox {}
                    .label-tel {}
                    .label-email {}
                    .label-text {}
                    .label-note {}
                    .label-map {}
                    .label-map-instructions {
                        color: grey;
                    }
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    .hr {}
                    .hr-divider {}

                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    fieldset {
                        border:none;
                    }
                    ';
                break;
            default:
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 1000px;
                    }
                    #contact-form {}

                    #title {
                        font-size: 2em;
                        font-weight: bolder;
                        padding: .5em 0;
                    }
                   #description {
                        padding: .5em 0;
                    }
                    #section-name {}
                    #section-phone {}
                    #section-email {}
                    #name {}
                    #phone {}
                    #email {}

                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-multi_select {}
                    .section-key_select {}
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-tel {}
                    .section-email {}
                    .section-text {}
                    .section-date {}
                    .section-header {
                        font-size: 2em;
                        font-weight: bolder;

                        padding-top: .5em;
                    }
                    .section-description {}
                    .section-note {}
                    .section-map {
                        padding-bottom: 10px;
                    }
                    .section-custom_label {
                        font-size: 1.2em;

                    }

                    .input-text {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-communication_channel {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-date {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .5em;
                        font-size: 1.2em;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        font-size:1.1em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;
                    }
                    .input-tel {
                       padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-multi_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-key_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-dropdown {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-note {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }
                    .input-label {
                        font-size: 1.2em;
                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }
                    .label-checkbox {}
                    .label-tel {}
                    .label-email {}
                    .label-text {}
                    .label-note {}
                    .label-map {}
                    .label-map-instructions {
                        color: grey;
                    }
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    .hr {}
                    .hr-divider {}

                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    fieldset {
                        border:none;
                    }
                    ';

                $ids = '';
                foreach ( $meta as $key => $value ) {
                    if ( substr( $key, 0, 5 ) === 'field' ) {
                        if ( empty( $value['labels'] ) ) {
                            $value['labels'] = 'Divider';
                        }
                        if ( ! is_array( $value['labels'] ) ) {
                            $ids .= '// ' . esc_html( $value['labels'] ) . PHP_EOL;
                            $ids .= '#section-' . esc_attr( $key ) . ' {}' . PHP_EOL;
                            $ids .= '#' . esc_attr( $key ) . ' {}' . PHP_EOL . PHP_EOL;
                        }
                    }
                }

                return $css . $ids;

        }

        /**
         * Location Styles
         */
        $location_styles = '';
        foreach ( $meta as $key => $value ) {
            if ( substr( $key, 0, 5 ) === 'field' && $value['type'] === 'location' ) {
                $location_styles = '
                #geocoder {
                    padding-bottom: 10px;
                }
                #map {
                    width:66%;
                    height:400px;
                    float:left;
                }
                #list {
                    width:33%;
                    float:right;
                }
                #selected_values {
                    width:66%;
                    float:left;
                }
                .selection-container {
                    padding: 15px 10px;
                    border: 1px solid lightgray;
                    margin: 5px;
                    font-weight: bold;
                    float:left;
                }
                .selection-remove {
                    padding-left:10px;
                    color: red;
                    cursor: pointer;
                }
                .results-button-column {
                    width:10px;
                    padding-right: 10px;
                    vertical-align: top;
                }
                .results-add-button {

                }
                .results-title-column {}
                .results-title {
                    font-size:1.2em;
                    font-weight:bold;
                }
                .results-row {
                    padding-bottom:5px;
                }
                .results-table {

                }
                .results-table td {
                    padding-bottom: 15px;
                }

                /* mapbox autocomplete elements*/
                #mapbox-search {
                    margin:0;
                }
                #mapbox-search-wrapper {
                    margin: 0 0 1rem;
                }
                .mapbox-autocomplete {
                    /*the container must be positioned relative:*/
                    position: relative;
                }
                .mapbox-autocomplete-items {
                    position: absolute;
                    border: 1px solid #e6e6e6;
                    border-bottom: none;
                    border-top: none;
                    z-index: 99;
                    /*position the autocomplete items to be the same width as the container:*/
                    top: 100%;
                    left: 0;
                    right: 0;
                }
                .mapbox-autocomplete-items div {
                    padding: 10px;
                    cursor: pointer;
                    background-color: #fff;
                    border-bottom: 1px solid #e6e6e6;
                }
                .mapbox-autocomplete-items div:hover {
                    /*when hovering an item:*/
                    background-color: #00aeff;
                    color: #ffffff;
                }
                .mapbox-autocomplete-active {
                    /*when navigating through the items using the arrow keys:*/
                    background-color: #00aeff !important;
                    color: #ffffff;
                }
                #mapbox-spinner-button {
                    border-radius:0;
                    display:none;
                }
                /* end mapbox elements*/
            ';
            }
        }

        /**
         * Custom CSS
         */
        $custom_css = '';
        if ( isset( $meta['custom_css'] ) && ! empty( $meta['custom_css'] ) ) {
            $custom_css = $meta['custom_css'];
        }

        $css = $location_styles . $css . $custom_css;
        $css = trim( str_replace( PHP_EOL, '', str_replace( '  ', '', $css ) ) );

        return $css;
    }

}

