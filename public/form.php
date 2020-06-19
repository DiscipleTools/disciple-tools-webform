<?php
if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
    die( 'missing server info' );
}
// @codingStandardsIgnoreLine
require( $_SERVER[ 'DOCUMENT_ROOT' ] . '/wp-load.php' ); // loads the wp framework when called

if ( ! isset( $_GET['token'] ) || empty( $_GET['token'] ) ) {
    die( 'missing token' );
}
require_once( '../includes/utilities.php' );
require_once( '../includes/post-type-active-forms.php' );
require_once( '../includes/functions.php' );

$dt_webform_token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
$dt_webform_meta = DT_Webform_Utilities::get_form_meta( $dt_webform_token );
$dt_webform_core_fields = DT_Webform_Active_Form_Post_Type::get_core_fields_by_token( $dt_webform_token );
$dt_webform_fields = DT_Webform_Active_Form_Post_Type::get_extra_fields( $dt_webform_token );

if ( isset( $dt_webform_meta['disable'] ) && 'disabled' === $dt_webform_meta['disable'] ) {
    die( 'form is disabled' );
}

?>
<html lang="en">
<head>
    <title><?php echo esc_html( $dt_webform_core_fields['header_title_field']['label'] ?? '' ) ?></title>
    <?php
    /**
     * Coding standards require enqueue of files, but for the purpose of a light iframe, we don't want
     * to load an entire site header. Therefore these files are to ignore standards.
     */
    // @codingStandardsIgnoreStart ?>

    <script type="text/javascript" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) ?>jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) ?>jquery-migrate.min.js"></script>
    <script type="text/javascript" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) ?>jquery.validate.min.js"></script>
    <script type="text/javascript" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) ?>public.js?ver=1.2"></script>

    <?php $swurl = esc_url( plugin_dir_url( __FILE__ ) ) . 'sw.js'?>
    <script>
        if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?php echo $swurl ?>').then(function(registration) {
            // Registration was successful
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
            // registration failed :(
            console.log('ServiceWorker registration failed: ', err);
            });
        });
        }
    </script>
    <script>
        window.TRANSLATION = {
            'required': '<?php echo $dt_webform_meta['js_string_required'] ?? esc_html__( 'Required', 'dt_webform' ) ?>',
            'characters_required': '<?php echo $dt_webform_meta['js_string_char_required'] ?? esc_html__( "At least {0} characters required!", 'dt_webform' ) ?>',
            'submit_in': '<?php echo $dt_webform_meta['js_string_submit_in'] ?? esc_html__( 'Submit in', 'dt_webform' ) ?>',
            'submit': '<?php echo $dt_webform_meta['js_string_submit'] ?? esc_html__( 'Submit', 'dt_webform' ) ?>',
            'success': '<?php echo $dt_webform_meta['js_string_success'] ?? esc_html__( 'Success', 'dt_webform' ) ?>',
            'failure': '<?php echo $dt_webform_meta['js_string_failure'] ?? esc_html__( 'Sorry, Something went wrong', 'dt_webform' ) ?>',
        }
        window.SETTINGS = {
            'spinner': ' <span class="spinner"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>spinner.svg" width="20px" /></span>',
        }
        <?php if ( isset( $dt_webform_meta['theme'] ) && $dt_webform_meta['theme'] === 'inherit' ) : ?>
            jQuery(document).ready(function() {
                //pulling all <style></style> css of parent document
                if (parent) {
                    var oHead = document.getElementsByTagName("head")[0];
                    var arrStyleSheets = parent.document.getElementsByTagName("style");
                    for (var i = 0; i < arrStyleSheets.length; i++)
                        oHead.appendChild(arrStyleSheets[i].cloneNode(true));
                }
                //pulling all external style css(<link href="css.css">) of parent document
                $("link[rel=stylesheet]",parent.document).each(function(){
                    var cssLink = document.createElement("link")
                    cssLink.href = "https://"+parent.document.domain+$(this).attr("href");
                    cssLink .rel = "stylesheet";
                    cssLink .type = "text/css";
                    document.body.appendChild(cssLink);
                });
            });
        <?php endif; ?>
    </script>

    <?php
    /* location files */
    if ( count( $dt_webform_fields ) > 0 ) {
        foreach ( $dt_webform_fields as $dt_webform_key => $dt_webform_value ) :
            if ( isset( $dt_webform_value[ 'type' ] ) && $dt_webform_value[ 'type' ] === 'location' ) :

                if ( is_dt() && ! class_exists( 'DT_Mapbox_API')  ) {
                    require_once( get_template_directory().  '/dt-mapping/geocode-api/mapbox-api.php' );
                }
                else if ( ! is_dt() ) {
                    require_once( '../dt-mapping/geocode-api/mapbox-api.php' );
                }
                ?>
                <script type="text/javascript" src="<?php echo DT_Mapbox_API::$mapbox_gl_js ?>"></script>
                <link rel="stylesheet" href="<?php echo DT_Mapbox_API::$mapbox_gl_css ?>" type="text/css" media="all">
            <?php
            break;
            endif;
        endforeach;
    }
    // @codingStandardsIgnoreEnd ?>

    <style>
        <?php echo esc_attr( DT_Webform_Utilities::get_theme( $dt_webform_meta['theme'] ?? 'wide-heavy', $dt_webform_token ) ) ?>
        .email { display:none; }
    </style>

</head>
<body>
<div id="wrapper">
<form id="contact-form" action="">

    <?php
    /**
     * Hidden Fields
     */
    ?>
    <input type="hidden" id="token" name="token" value="<?php echo esc_attr( $dt_webform_token ) ?>"/>
    <input type="hidden" id="ip_address" name="ip_address" value="<?php echo esc_attr( DT_Webform::get_real_ip_address() ?? '' ) ?>"/>

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
            <label for="name" class="input-label label-name"><?php echo esc_html( $dt_webform_core_fields['name_field']['label'] ) ?? '' ?></label><br>
            <input type="text" id="name" name="name" class="input-text input-name" value="" <?php echo ( $dt_webform_core_fields['name_field']['required'] === 'yes' ) ? 'required' : '' ?>/>
        </div>
    <?php endif; ?>

    <?php if ( isset( $dt_webform_core_fields['phone_field'] ) && $dt_webform_core_fields['phone_field']['hidden'] !== 'yes' ) : ?>
        <div id="section-phone" class="section">
            <label for="phone" class="input-label"><?php echo esc_html( $dt_webform_core_fields['phone_field']['label'] ) ?? '' ?></label><br>
            <input type="tel" id="phone" name="phone" class="input-text input-phone" value="" <?php echo ( $dt_webform_core_fields['phone_field']['required'] == 'yes' ) ? 'required' : '' ?>/>
        </div>
    <?php endif; ?>

    <?php if ( isset( $dt_webform_core_fields['email_field'] ) && $dt_webform_core_fields['email_field']['hidden'] !== 'yes' ) : ?>
        <div id="section-email" class="section">
            <label for="email"
                   class="input-label label-email"><?php echo esc_html( $dt_webform_core_fields['email_field']['label'] ) ?? '' ?></label><br>
            <input type="email" id="email2" name="email2" class="input-text email" value=""/>
            <input type="email" id="email" name="email" class="input-text input-email" value="" <?php echo ( $dt_webform_core_fields['email_field']['required'] === 'yes' ) ? 'required' : '' ?>/>
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
                switch ($dt_webform_value['type']) {
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
                                <br>
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
                                <br>
                                <select id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                        class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                        name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?> multiple>
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
                    case 'text':
                    case 'date':
                        ?>
                        <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                             class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?> section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                            <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                   class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['labels'] ?? '' ) ?></label>
                            <br>
                            <input type="<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                   id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                   name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                   class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                   value="" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?>/>
                        </div>
                        <?php
                        break;
                    case 'location':

                        if ( $dt_webform_value['values'] === 'click_map' ) {
                            form_click_map($dt_webform_value);
                        }
                        if ( $dt_webform_value['values'] === 'search_box' ) {
                            ?>
                            <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                 class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?> section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
                                <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                       class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['labels'] ?? '' ) ?></label>
                                <br>
                                <div id="mapbox-wrapper">
                                    <div id="mapbox-autocomplete" class="mapbox-autocomplete input-group" data-autosubmit="true">
                                        <input id="mapbox-search" type="text" name="mapbox_search" placeholder="Search Location" class="input-text ignore" style="width:95%" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?> /><span id="mapbox-spinner-button" style="display:none;width:5%;padding:8px;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) ?>spinner.svg" alt="spinner" style="width: 20px;" /></span>
                                        <div id="mapbox-autocomplete-list" class="mapbox-autocomplete-items"></div>
                                        <div style="display:none;">
                                            <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-lng" data-type="lng" class="location"></span>
                                            <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-lat" data-type="lat" class="location"></span>
                                            <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-level" data-type="level" class="location"></span>
                                            <span id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>-label" data-type="label" class="location"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                jQuery(document).ready(function(){
                                    function write_input_widget() {

                                        window.currentfocus = -1

                                        jQuery('#mapbox-search').on("keyup", function(e){
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
                                    function mapbox_autocomplete(address){
                                        console.log('mapbox_autocomplete: ' + address )
                                        if ( address.length < 1 ) {
                                            return;
                                        }

                                        let root = 'https://api.mapbox.com/geocoding/v5/mapbox.places/'
                                        let settings = '.json?types=country,region,postcode,district,place,locality,neighborhood,address&limit=6&access_token='
                                        let key = '<?php echo DT_Mapbox_API::get_key() ?>'

                                        let url = root + encodeURI( address ) + settings + key

                                        jQuery.get( url, function( data ) {
                                            console.log(data)
                                            if( data.features.length < 1 ) {
                                                // destroy lists
                                                console.log('no results')
                                                return
                                            }

                                            let list = jQuery('#mapbox-autocomplete-list')
                                            list.empty()

                                            jQuery.each( data.features, function( index, value ) {
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
                                        window.validate_timer_id = setTimeout(function(){
                                            // call geocoder
                                            mapbox_autocomplete( jQuery('#mapbox-search').val() )

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
                switch ($dt_webform_value['type']) {
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
                                <br>
                                <select id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                        class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                        name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>">
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
                                       class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['title'] ) ?></label><br>
                                <?php
                                foreach ( $list as $index => $item ) {
                                    $checked = '';
                                    if ( 0 == $index ) {
                                        $checked = 'checked';
                                    }
                                    echo '<span class="span-radio"><input type="radio" class="input-' . esc_attr( $dt_webform_value['type'] ) . '" name="' . esc_attr( $dt_webform_value['key'] ) . '" value="' . esc_html( $item ) . '" '. esc_attr( $checked ).'>' . esc_html( $item ) . '</span>';
                                }
                                ?>
                                <br style="clear: both;" /> </div>
                            <?php
                        }
                        break;
                    case 'checkbox':
                        // text box
                        ?>
                        <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                             class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                            <input type="checkbox"
                                   id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                   name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                                   class="input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"
                                   value="<?php echo esc_html( $dt_webform_value['labels'] ) ?>"
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
                            <br>
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
                            <?php echo nl2br( esc_html( $dt_webform_value['labels'] ) ) ?>
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
                            <br>
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
        <button type="button" class="submit-button ignore" id="submit-button" onclick="check_form()" disabled><?php esc_attr_e( 'Submit', 'dt_webform' ) ?></button> <span class="spinner" style="display:none;"></span>
    </div>

</form>

<div id="report"></div>
<div id="offlineWarningContainer"></div>
</div> <!-- wrapper-->
</body>
</html>

<?php
function form_click_map( $dt_webform_value ) {
    ?>
    <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
         class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">

        <?php if ( ! empty( $dt_webform_value['labels'] ) ) : ?>
            <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                   class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_html( $dt_webform_value['labels'] ) ?? '' ?></label>
        <?php endif; ?>

        <script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.0/mapbox-gl-geocoder.min.js'></script>
        <link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.0/mapbox-gl-geocoder.css' type='text/css' />

        <!-- Widget -->
        <div class="label-map-instructions">zoom and click map to select locations</div>
        <div>
            <div id='map'></div>
            <div id="list">
                <div><span class="input-label">Click Results</span><br><hr></div>
                <div id="list-location-grid"></div>
                <div id="list-address"></div>
            </div>
        </div>
        <div id="selected_values"></div>

        <!-- Mapbox script -->
        <script>
            window.spinner = '<img class="load-spinner" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'spinner.svg' ?>" width="20px" />'
            mapboxgl.accessToken = '<?php echo esc_html( get_option( 'dt_mapbox_api_key' ) ) ?>';
            var map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [-20, 30],
                zoom: 1
            });

            // Controls
            let searchGeocoder = new MapboxGeocoder({
                accessToken: mapboxgl.accessToken,
                types: 'country region district locality neighborhood postcode',
                marker: {color: 'orange'},
                mapboxgl: mapboxgl
            });
            let userGeocoder = new mapboxgl.GeolocateControl({
                positionOptions: {
                    enableHighAccuracy: true
                },
                marker: {
                    color: 'orange'
                },
                trackUserLocation: false
            })
            let navigationGeocoder = new mapboxgl.NavigationControl()
            map.addControl(searchGeocoder);
            map.addControl(userGeocoder);
            map.addControl(navigationGeocoder);

            // Search event
            searchGeocoder.on('result', function(e) { // respond to search
                searchGeocoder._removeMarker()
                console.log(e)
            })

            // Click event
            map.on('click', function (e) {
                jQuery('#list-location-grid').empty().append(window.spinner);
                console.log(e)

                let lng = e.lngLat.lng
                let lat = e.lngLat.lat
                window.active_lnglat = [lng,lat]

                // add marker
                if ( window.active_marker ) {
                    window.active_marker.remove()
                }
                window.active_marker = new mapboxgl.Marker()
                    .setLngLat(e.lngLat )
                    .addTo(map);
                console.log(active_marker)


                // add location grid list
                jQuery.get('<?php echo esc_url( trailingslashit( plugin_dir_url(__DIR__ ) ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
                    {
                        type: 'possible_matches',
                        longitude: lng,
                        latitude:  lat,
                        nonce: '<?php echo esc_html( wp_create_nonce( 'location_grid' ) ) ?>'
                    }, null, 'json' ).done(function(data) {

                    console.log(data)
                    if ( data !== undefined ) {
                        print_click_results( data )
                    }

                })
            });

            // Geolocate event
            userGeocoder.on('geolocate', function(e) { // respond to search
                jQuery('#list-location-grid').empty().append(window.spinner);
                console.log(e)
                let lat = e.coords.latitude
                let lng = e.coords.longitude
                window.active_lnglat = [lng,lat]

                // add polygon
                jQuery.get('<?php echo esc_url( trailingslashit( plugin_dir_url(__DIR__ ) ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
                    {
                        type: 'possible_matches',
                        longitude: lng,
                        latitude:  lat,
                        nonce: '<?php echo esc_html( wp_create_nonce( 'location_grid' ) ) ?>'
                    }, null, 'json' ).done(function(data) {
                    console.log(data)

                    if ( data !== undefined ) {
                        print_click_results(data)
                    }
                })
            })

            function print_click_results( data ) {
                if ( data !== undefined ) {
                    // print click results
                    window.MBresponse = data

                    let print = jQuery('#list-location-grid')
                    print.empty();
                    let table_body = ''
                    jQuery.each( data, function(i,v) {
                        let string = '<tr class="results-row"><td class="results-button-column">'
                        string += '<a class="results-add-button" href="javascript:void(0)" onclick="add_selection(' + v.grid_id +')">Add</a></td> '
                        string += '<td class="results-title-column"><span class="results-title"> '+v.name+'</span><br>'
                        if ( v.admin0_name !== v.name ) {
                            string += v.admin0_name
                        }
                        if ( v.admin1_name !== null ) {
                            string += ' > ' + v.admin1_name
                        }
                        if ( v.admin2_name !== null ) {
                            string += ' > ' + v.admin2_name
                        }
                        if ( v.admin3_name !== null ) {
                            string += ' > ' + v.admin3_name
                        }
                        if ( v.admin4_name !== null ) {
                            string += ' > ' + v.admin4_name
                        }
                        if ( v.admin5_name !== null ) {
                            string += ' > ' + v.admin5_name
                        }
                        string += '</td></tr>'
                        table_body += string
                    })
                    print.append('<table class="results-table">' + table_body + '</table>')
                }
            }

            /**
             * Protects against duplicate entries, by using the grid_id as the key.
             * @param grid_id
             */
            function add_selection( grid_id ) {
                console.log(window.MBresponse[grid_id])
                // test if already added
                let already = jQuery('#'+grid_id).html()
                if ( already ) {
                    return;
                }
                let div = jQuery('#selected_values')
                let response = window.MBresponse[grid_id]

                if ( window.selected_locations === undefined ) {
                    window.selected_locations = []
                }
                window.selected_locations[grid_id] = new mapboxgl.Marker()
                    .setLngLat( [ window.active_lnglat[0], window.active_lnglat[1] ] )
                    .addTo(map);
                let name = ''
                name += response.name
                if ( response.admin1_name !== undefined && response.level > '1' ) {
                    name += ', ' + response.admin1_name
                }
                if ( response.admin0_name && response.level > '0' ) {
                    name += ', ' + response.admin0_name
                }
                div.append('<div class="selection-container" id="'+grid_id+'">' +
                    '<span>'+name+'</span>' +
                    '<span class="selection-remove" onclick="remove_selection(\''+grid_id+'\')">X</span>' +
                    '<input type="hidden" name="location_lnglat_' + grid_id + '" value="' + window.active_lnglat[0] + ',' + window.active_lnglat[1] + ',' + grid_id + '" />' +
                    '</div>')
            }
            function remove_selection( grid_id ) {
                window.selected_locations[grid_id].remove()
                jQuery('#' + grid_id ).remove()
            }
        </script>
    </div>
    <br clear="all" />
    <?php
}
