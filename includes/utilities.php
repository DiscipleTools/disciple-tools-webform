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
        if ( $meta ) {
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
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $site['transfer_token'],
            ],
        ];

        $result = wp_remote_get( 'https://' . trailingslashit( $site['url'] ) . 'wp-json/dt-posts/v2/contacts/settings', $args );
        if ( is_wp_error( $result ) ) {
            return new WP_Error( __METHOD__, $result->get_error_message() );
        }

        $contact_defaults = json_decode( $result['body'], true );
        if ( isset( $contact_defaults["fields"]['sources'] ) ) {
            set_transient( 'dt_webform_contact_defaults', $contact_defaults["fields"], 60 *60 *24 );
            return $contact_defaults["fields"];
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
                        display: inline-block;
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
                        display: inline-block;
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
                        display: inline-block;
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
                        display: inline-block;
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

    public static function get_form_html_scripts_and_styles( $dt_webform_token, $dt_webform_meta, $dt_webform_fields, $public_url ): string {
        ob_start();

        self::echo_form_html_scripts_and_styles( $dt_webform_token, $dt_webform_meta, $dt_webform_fields, $public_url );

        $html = ob_get_contents();

        ob_end_clean();

        return $html;
    }

    public static function echo_form_html_scripts_and_styles( $dt_webform_token, $dt_webform_meta, $dt_webform_fields, $public_url ){

        /**
         * Coding standards require enqueue of files, but for the purpose of a light iframe, we don't want
         * to load an entire site header. Therefore these files are to ignore standards.
         */
        // @codingStandardsIgnoreStart

        ?>

        <script type="text/javascript" src="<?php echo esc_url( $public_url ) ?>jquery.min.js"></script>
        <script type="text/javascript"
                src="<?php echo esc_url( $public_url ) ?>jquery-migrate.min.js"></script>
        <script type="text/javascript"
                src="<?php echo esc_url( $public_url ) ?>jquery.validate.min.js"></script>
        <script type="text/javascript"
                src="<?php echo esc_url( $public_url ) ?>public.js?ver=<?php echo esc_html( filemtime( plugin_dir_path( __DIR__ ) . 'public/public.js' ) ) ?>"></script>
        <?php // @codingStandardsIgnoreEnd ?>
        <?php $swurl = $public_url . 'sw.js' ?>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('<?php echo esc_html( $swurl ) ?>').then(function (registration) {
                        // Registration was successful
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    }, function (err) {
                        // registration failed :(
                        console.log('ServiceWorker registration failed: ', err);
                    });
                });
            }
        </script>
        <script>
            window.TRANSLATION = {
                'required': "<?php echo esc_html( $dt_webform_meta['js_string_required'] ?? __( 'Required', 'dt_webform' ) ) ?>",
                'characters_required': "<?php echo esc_html( $dt_webform_meta['js_string_char_required'] ?? __( "At least {0} characters required!", 'dt_webform' ) ) ?>",
                'submit_in': "<?php echo esc_html( $dt_webform_meta['js_string_submit_in'] ?? __( 'Submit in', 'dt_webform' ) ) ?>",
                'submit': "<?php echo esc_html( $dt_webform_meta['js_string_submit'] ?? __( 'Submit', 'dt_webform' ) ) ?>",
                'success': "<?php echo esc_html( $dt_webform_meta['js_string_success'] ?? __( 'Success', 'dt_webform' ) ) ?>",
                'failure': "<?php echo esc_html( $dt_webform_meta['js_string_failure'] ?? __( 'Sorry, Something went wrong', 'dt_webform' ) ) ?>",
            }
            window.SETTINGS = {
                'spinner': ' <span class="spinner"><img src="<?php echo esc_html( $public_url ) ?>spinner.svg" width="20px" /></span>',
                'rest_url': "<?php echo esc_url_raw( rest_url() ) ?>",
            }
            <?php if ( isset( $dt_webform_meta['theme'] ) && $dt_webform_meta['theme'] === 'inherit' ) : ?>
            jQuery(document).ready(function () {
                //pulling all <style></style> css of parent document
                if (parent) {
                    var oHead = document.getElementsByTagName("head")[0];
                    var arrStyleSheets = parent.document.getElementsByTagName("style");
                    for (var i = 0; i < arrStyleSheets.length; i++)
                        oHead.appendChild(arrStyleSheets[i].cloneNode(true));
                }
                //pulling all external style css(<link href="css.css">) of parent document
                $("link[rel=stylesheet]", parent.document).each(function () {
                    var cssLink = document.createElement("link")
                    cssLink.href = "https://" + parent.document.domain + $(this).attr("href");
                    cssLink.rel = "stylesheet";
                    cssLink.type = "text/css";
                    document.body.appendChild(cssLink);
                });
            });
            <?php endif; ?>
        </script>

        <?php
        /* location files */
        if ( count( $dt_webform_fields ) > 0 ) {
            foreach ( $dt_webform_fields as $dt_webform_key => $dt_webform_value ) :
                if ( isset( $dt_webform_value['type'] ) && $dt_webform_value['type'] === 'location' ) :

                    if ( is_this_dt() && ! class_exists( 'DT_Mapbox_API' ) ) {
                        require_once( get_template_directory() . '/dt-mapping/geocode-api/mapbox-api.php' );
                    } else if ( ! is_this_dt() ) {
                        require_once( plugin_dir_path( __DIR__ ) . 'dt-mapping/geocode-api/mapbox-api.php' );
                    }
                    if ( class_exists( "DT_Mapbox_API" ) ){
                        // @codingStandardsIgnoreStart
                        ?>
                        <script type="text/javascript" src="<?php echo esc_html( DT_Mapbox_API::$mapbox_gl_js ) ?>"></script>
                        <link rel="stylesheet" href="<?php echo esc_html( DT_Mapbox_API::$mapbox_gl_css ) ?>" type="text/css"
                              media="all">
                        <?php
                        // @codingStandardsIgnoreEnd

                    }
                    break;
                endif;
            endforeach;
        } ?>


        <style>
            <?php echo esc_attr( self::get_theme( $dt_webform_meta['theme'] ?? 'wide-heavy', $dt_webform_token ) ) ?>
            #contact-form #section-email .email {
                display: none;
            }
        </style>
        <?php
    }

    public static function get_form_html( $dt_webform_token, $dt_webform_campaigns, $dt_webform_core_fields, $dt_webform_fields, $public_url ): string {
        ob_start();

        self::echo_form_html( $dt_webform_token, $dt_webform_campaigns, $dt_webform_core_fields, $dt_webform_fields, $public_url );

        $html = ob_get_contents();

        ob_end_clean();

        return $html;
    }

    public static function echo_form_html( $dt_webform_token, $dt_webform_campaigns, $dt_webform_core_fields, $dt_webform_fields, $public_url ) {

        /**
         * Hidden Fields
         */
        ?>
        <input type="hidden" id="token" name="token" value="<?php echo esc_attr( $dt_webform_token ) ?>"/>
        <input type="hidden" id="meta_campaigns" name="meta_campaigns" value="<?php echo esc_attr( $dt_webform_campaigns ) ?>"/>
        <input type="hidden" id="ip_address" name="ip_address"
               value="<?php echo esc_attr( DT_Webform::get_real_ip_address() ?? '' ) ?>"/>

        <?php
        /**
         * Core Fields
         */
        ?>
        <div id="title"
            <?php echo ( isset( $dt_webform_core_fields['header_title_field']['hidden'] ) && $dt_webform_core_fields['header_title_field']['hidden'] === 'yes' ) ? 'style="display:none;" ' : ''; ?>>
            <?php echo esc_html( $dt_webform_core_fields['header_title_field']['label'] ?? '' ) ?>
        </div>
        <div id="description"
            <?php echo ( isset( $dt_webform_core_fields['header_description_field']['hidden'] ) && $dt_webform_core_fields['header_description_field']['hidden'] === 'yes' ) ? 'style="display:none;" ' : ''; ?>>
            <?php echo nl2br( esc_html( $dt_webform_core_fields['header_description_field']['label'] ?? '' ) ) ?>
        </div>

        <?php if ( isset( $dt_webform_core_fields['name_field'] ) && $dt_webform_core_fields['name_field']['hidden'] !== 'yes' ) : ?>
            <div id="section-name" class="section">
                <label for="name"
                       class="input-label label-name"><?php echo esc_html( $dt_webform_core_fields['name_field']['label'] ) ?? '' ?></label>
                <input type="text" id="name" name="name" class="input-text input-name"
                       value="" <?php echo ( $dt_webform_core_fields['name_field']['required'] === 'yes' ) ? 'required' : '' ?>/>
            </div>
        <?php endif; ?>

        <?php if ( isset( $dt_webform_core_fields['phone_field'] ) && $dt_webform_core_fields['phone_field']['hidden'] !== 'yes' ) : ?>
            <div id="section-phone" class="section">
                <label for="phone"
                       class="input-label"><?php echo esc_html( $dt_webform_core_fields['phone_field']['label'] ) ?? '' ?></label>
                <input type="tel" id="phone" name="phone" class="input-text input-phone"
                       value="" <?php echo ( $dt_webform_core_fields['phone_field']['required'] == 'yes' ) ? 'required' : '' ?>/>
            </div>
        <?php endif; ?>

        <?php if ( isset( $dt_webform_core_fields['email_field'] ) && $dt_webform_core_fields['email_field']['hidden'] !== 'yes' ) : ?>
            <div id="section-email" class="section">
                <label for="email"
                       class="input-label label-email"><?php echo esc_html( $dt_webform_core_fields['email_field']['label'] ) ?? '' ?></label>
                <input type="email" id="email2" name="email2" class="input-text email" value=""/>
                <input type="email" id="email" name="email" class="input-text input-email"
                       value="" <?php echo ( $dt_webform_core_fields['email_field']['required'] === 'yes' ) ? 'required' : '' ?>/>
            </div>
        <?php else : ?>
            <div id="section-email" class="section email">
                <input type="email" id="email2" name="email2" class="input-text email" value=""/>
            </div>
        <?php endif; ?>


        <?php
        /**
         * Extra fields
         */
        if ( count( $dt_webform_fields ) > 0 ) {
            foreach ( $dt_webform_fields as $dt_webform_key => $dt_webform_value ) {
                if ( ! isset( $dt_webform_value['type'] ) ) {
                    error_log( 'Failed to find type field complete' );
                    continue;
                }

                // DT Fields
                if ( isset( $dt_webform_value['is_dt_field'] ) && ! empty( $dt_webform_value['is_dt_field'] ) ) {
                    switch ( $dt_webform_value['type'] ) {
                        // multi labels, multi values
                        case 'dropdown':
                        case 'key_select':
                            $list = DT_Webform_Active_Form_Post_Type::match_dt_field_labels_with_values( $dt_webform_value['labels'], $dt_webform_value['values'] );
                            if ( count( $list ) > 0 ) {
                                ?>
                                <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                     class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                    <label for="<?php echo esc_attr( $dt_webform_key ) ?>"
                                           class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                                        <?php echo esc_attr( $dt_webform_value['title'] ) ?>
                                    </label>
                                    <select id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                            class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                            name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                        <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?>
                                    >
                                        <?php
                                        if ( isset( $dt_webform_value['selected'] ) && $dt_webform_value['selected'] === 'no' ) {
                                            echo '<option></option>';
                                        }
                                        foreach ( $list as $item ) {
                                            echo '<option value="' . esc_attr( $item['value'] ) . '">' . esc_html( $item['label'] ) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <?php
                            }
                            break;
                        case 'multi_select':
                            $list = DT_Webform_Active_Form_Post_Type::match_dt_field_labels_with_values( $dt_webform_value['labels'], $dt_webform_value['values'] );
                            if ( count( $list ) > 0 ) {
                                ?>
                                <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                     class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                    <label for="<?php echo esc_attr( $dt_webform_key ) ?>"
                                           class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                                        <?php echo esc_attr( $dt_webform_value['title'] ) ?>
                                    </label>
                                    <fieldset>
                                        <?php
                                        foreach ( $list as $item ) {
                                            echo '<label><input type="checkbox" name="' . esc_attr( $dt_webform_key ) . '" value="' . esc_attr( $item['value'] ) . '">' . esc_html( $item['label'] ) . '</label><br>';
                                        }
                                        ?>
                                    </fieldset>
                                </div>
                                <?php
                            }
                            break;
                        case 'text':
                        case 'communication_channel':
                        case 'date':
                            ?>
                            <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                 class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?> section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                                <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['labels'] ?? '' ) ?></label>
                                <input type="<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                       id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                       value="" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?>/>
                            </div>
                            <?php
                            break;
                        case 'location':

                            if ( is_this_dt() && ! class_exists( 'DT_Mapbox_API' ) ) {
                                require_once( get_template_directory() . '/dt-mapping/geocode-api/mapbox-api.php' );
                            } else if ( ! is_this_dt() ) {
                                require_once( plugin_dir_path( __DIR__ ) . 'dt-mapping/geocode-api/mapbox-api.php' );
                            }

                            if ( $dt_webform_value['values'] === 'click_map' ) {
                                form_click_map( $dt_webform_value );
                            }
                            if ( $dt_webform_value['values'] === 'search_box' ) {
                                ?>
                                <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                     class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?> section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                                    <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                           class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['labels'] ?? '' ) ?></label>

                                    <div id="mapbox-wrapper">
                                        <div id="mapbox-autocomplete" class="mapbox-autocomplete input-group"
                                             data-autosubmit="true">
                                            <input id="mapbox-search" type="text" name="mapbox_search"
                                                   placeholder="Search Location" class="input-text ignore"
                                                   style="width:95%" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?> /><span
                                                id="mapbox-spinner-button"
                                                style="display:none;width:5%;padding:8px;"><img
                                                    src="<?php echo esc_url( $public_url ) ?>spinner.svg"
                                                    alt="spinner" style="width: 20px;"/></span>
                                            <div id="mapbox-autocomplete-list" class="mapbox-autocomplete-items"></div>
                                            <div style="display:none;">
                                                <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-lng"
                                                      data-type="lng" class="location"></span>
                                                <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-lat"
                                                      data-type="lat" class="location"></span>
                                                <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-level"
                                                      data-type="level" class="location"></span>
                                                <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-label"
                                                      data-type="label" class="location"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    jQuery(document).ready(function () {
                                        function write_input_widget() {

                                            window.currentfocus = -1

                                            jQuery('#mapbox-search').on("keyup", function (e) {
                                                var x = document.getElementById("mapbox-autocomplete-list");
                                                if (x) x = x.getElementsByTagName("div");
                                                if (e.which === 40) {
                                                    /*If the arrow DOWN key is pressed,
                                                    increase the currentFocus variable:*/
                                                    console.log('down')
                                                    window.currentfocus++;
                                                    /*and and make the current item more visible:*/
                                                    add_active(x);
                                                } else if (e.which === 38) { //up
                                                    /*If the arrow UP key is pressed,
                                                    decrease the currentFocus variable:*/
                                                    console.log('up')
                                                    window.currentfocus--;
                                                    /*and and make the current item more visible:*/
                                                    add_active(x);
                                                } else if (e.which === 13) {
                                                    /*If the ENTER key is pressed, prevent the form from being submitted,*/
                                                    e.preventDefault();
                                                    if (window.currentfocus > -1) {
                                                        /*and simulate a click on the "active" item:*/
                                                        close_all_lists(window.currentfocus);
                                                    }
                                                } else {
                                                    validate_timer()
                                                }
                                            })
                                        }

                                        write_input_widget()

                                        function add_active(x) {
                                            /*a function to classify an item as "active":*/
                                            if (!x) return false;
                                            /*start by removing the "active" class on all items:*/
                                            remove_active(x);
                                            if (window.currentfocus >= x.length) window.currentfocus = 0;
                                            if (window.currentfocus < 0) window.currentfocus = (x.length - 1);
                                            /*add class "autocomplete-active":*/
                                            x[window.currentfocus].classList.add("mapbox-autocomplete-active");
                                        }

                                        function remove_active(x) {
                                            /*a function to remove the "active" class from all autocomplete items:*/
                                            for (var i = 0; i < x.length; i++) {
                                                x[i].classList.remove("mapbox-autocomplete-active");
                                            }
                                        }

                                        function close_all_lists(selection_id) {

                                            jQuery('#mapbox-search').val(window.mapbox_result_features[selection_id].place_name)
                                            jQuery('#mapbox-autocomplete-list').empty()
                                            let spinner = jQuery('#mapbox-spinner-button').show()

                                            jQuery('#<?php echo esc_attr( $dt_webform_value['key'] ) ?>-lng').text(window.mapbox_result_features[selection_id].center[0])
                                            jQuery('#<?php echo esc_attr( $dt_webform_value['key'] ) ?>-lat').text(window.mapbox_result_features[selection_id].center[1])
                                            jQuery('#<?php echo esc_attr( $dt_webform_value['key'] ) ?>-level').text(window.mapbox_result_features[selection_id].place_type[0])
                                            jQuery('#<?php echo esc_attr( $dt_webform_value['key'] ) ?>-label').text(window.mapbox_result_features[selection_id].place_name)

                                            spinner.hide()

                                        }

                                        function mapbox_autocomplete(address) {
                                            console.log('mapbox_autocomplete: ' + address)
                                            if (address.length < 1) {
                                                return;
                                            }

                                            let root = 'https://api.mapbox.com/geocoding/v5/mapbox.places/'
                                            let settings = '.json?types=country,region,postcode,district,place,locality,neighborhood,address&limit=6&access_token='
                                            let key = '<?php echo esc_attr( DT_Mapbox_API::get_key() ) ?>'

                                            let url = root + encodeURI(address) + settings + key

                                            jQuery.get(url, function (data) {
                                                console.log(data)
                                                if (data.features.length < 1) {
                                                    // destroy lists
                                                    console.log('no results')
                                                    return
                                                }

                                                let list = jQuery('#mapbox-autocomplete-list')
                                                list.empty()

                                                jQuery.each(data.features, function (index, value) {
                                                    list.append(`<div data-value="${index}">${value.place_name}</div>`)
                                                })

                                                jQuery('#mapbox-autocomplete-list div').on("click", function (e) {
                                                    close_all_lists(e.target.attributes['data-value'].value);
                                                });

                                                // Set globals
                                                window.mapbox_result_features = data.features


                                            }); // end get request
                                        } // end validate
                                        window.validate_timer_id = '';

                                        function validate_timer() {

                                            clear_timer()

                                            // toggle buttons
                                            jQuery('#mapbox-spinner-button').show()

                                            // set timer
                                            window.validate_timer_id = setTimeout(function () {
                                                // call geocoder
                                                mapbox_autocomplete(jQuery('#mapbox-search').val())

                                                // toggle buttons back
                                                jQuery('#mapbox-spinner-button').hide()
                                            }, 1000);

                                        }

                                        function clear_timer() {
                                            clearTimeout(window.validate_timer_id);
                                        }
                                    })
                                </script>
                                <?php
                            }

                            break;

                        default:
                            break;
                    } // end switch
                } // end dt fields
                // Non-DT Fields
                else {
                    switch ( $dt_webform_value['type'] ) {
                        // multi labels, multi values
                        case 'dropdown':
                        case 'key_select':
                            $list = DT_Webform_Active_Form_Post_Type::make_labels_array( $dt_webform_value['labels'] );
                            if ( count( $list ) > 0 ) {
                                ?>
                                <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                     class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                    <label for="<?php echo esc_attr( $dt_webform_key ) ?>"
                                           class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                                        <?php echo esc_attr( $dt_webform_value['title'] ) ?>
                                    </label>
                                    <select id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                            class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                            name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?>>
                                        <?php
                                        if ( isset( $dt_webform_value['selected'] ) && $dt_webform_value['selected'] === 'no' ) {
                                            echo '<option></option>';
                                        }
                                        foreach ( $list as $item ) {
                                            echo '<option value="' . esc_html( $item ) . '">' . esc_html( $item ) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <?php
                            }
                            break;
                        case 'multi_radio':
                        case 'multi_select':
                            $list = DT_Webform_Active_Form_Post_Type::make_labels_array( $dt_webform_value['labels'] );
                            if ( count( $list ) > 0 ) {
                                ?>
                                <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                     class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                    <label for="<?php echo esc_attr( $dt_webform_key ) ?>"
                                           class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['title'] ) ?></label>
                                    <div>
                                    <?php
                                    foreach ( $list as $index => $item ) {
                                        $checked = '';
                                        if ( 0 == $index ) {
                                            $checked = 'checked';
                                        }
                                        echo '<label class="span-radio"><input type="radio" class="input-' . esc_attr( $dt_webform_value['type'] ) . '" name="' . esc_attr( $dt_webform_value['key'] ) . '" value="' . esc_html( $item ) . '" ' . esc_attr( $checked ) . '>' . esc_html( $item ) . '</label>';
                                    }
                                    ?>
                                    </div>
                                    <br style="clear: both;"/>
                                </div>
                                <?php
                            }
                            break;
                        case 'checkbox':
                            // text box
                            ?>
                            <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                 class="section single-checkbox section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                <input type="checkbox"
                                       id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                       value="<?php echo esc_html( $dt_webform_value['labels'] ) ?>"
                                       data-selected="<?php echo esc_html( $dt_webform_value['selected'] ?? '' ) ?>"
                                />
                                <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_html( $dt_webform_value['labels'] ) ?></label>
                            </div>
                            <?php
                            break;
                        case 'tel':
                        case 'email':
                        case 'text':
                            ?>
                            <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                 class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?> section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                                <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['labels'] ?? '' ) ?></label>

                                <input type="<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                       id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                       value="" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?>/>
                            </div>
                            <?php
                            break;

                        case 'custom_label':
                        case 'header':
                        case 'description':
                            ?>
                            <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                 class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                <?php echo wp_kses_post( nl2br( $dt_webform_value['labels'] ) ) ?>
                            </div>
                            <?php
                            break;

                        case 'divider':
                            ?>
                            <hr id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                class="hr hr-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php
                            break;

                        case 'spacer':
                            ?><br clear="all"><?php
                            break;

                        case 'note':
                            ?>
                            <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                 class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_html( $dt_webform_value['labels'] ) ?? '' ?></label>
                                <textarea id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                          name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                          class="input-textarea input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?>></textarea>
                            </div>
                            <?php

                            break;
                        default:
                            break;
                    } // end switch
                } // end non-dt fields
            }
        }
        ?>

        <div class="section" id="submit-button-container">
            <span style="color:red" class="form-submit-error"></span>
            <br>
            <button type="button" class="submit-button ignore" id="submit-button" onclick="check_form()"
                    disabled><?php esc_attr_e( 'Submit', 'dt_webform' ) ?></button>
            <span class="spinner" style="display:none;"></span>
        </div>

        <?php
    }
}
