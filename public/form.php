<?php
if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
    die( 'missing server info' );
}
// @codingStandardsIgnoreLine
require( $_SERVER[ 'DOCUMENT_ROOT' ] . '/wp-load.php' ); // loads the wp framework when called

if ( ! isset( $_GET['token'] ) ) {
    die( 'missing token' );
}
$dt_webform_token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
$dt_webform_meta = DT_Webform_Utilities::get_form_meta( $dt_webform_token );
$dt_webform_core_fields = DT_Webform_Active_Form_Post_Type::get_core_fields_by_token( $dt_webform_token );
$dt_webform_fields = DT_Webform_Active_Form_Post_Type::get_extra_fields( $dt_webform_token );

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
    <script type="text/javascript" src="jquery.min.js"></script>
    <script type="text/javascript" src="jquery-migrate.min.js"></script>
    <script type="text/javascript" src="jquery.validate.min.js"></script>
    <script type="text/javascript" src="public.js?ver=1.1"></script>
    <script>
        window.TRANSLATION = {
            'required': '<?php echo $dt_webform_meta['js_string_required'] ?? esc_html__( 'Required', 'dt_webform' ) ?>',
            'characters_required': '<?php echo $dt_webform_meta['js_string_char_required'] ?? esc_html__( "At least {0} characters required!", 'dt_webform' ) ?>',
            'submit_in': '<?php echo $dt_webform_meta['js_string_submit_in'] ?? esc_html__( 'Submit in', 'dt_webform' ) ?>',
            'submit': '<?php echo $dt_webform_meta['js_string_submit'] ?? esc_html__( 'Submit', 'dt_webform' ) ?>',
            'success': '<?php echo $dt_webform_meta['js_string_success'] ?? esc_html__( 'Success', 'dt_webform' ) ?>',
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
            if ( isset( $dt_webform_value[ 'type' ] ) && $dt_webform_value[ 'type' ] === 'map' ) :
                ?>
                <script type="text/javascript" src="https://api.mapbox.com/mapbox-gl-js/v1.1.0/mapbox-gl.js"></script>
                <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v1.1.0/mapbox-gl.css" type="text/css"
                      media="all">
            <?php
            break;
            endif;
        endforeach;
    }
    // @codingStandardsIgnoreEnd ?>

    <style>
        <?php echo esc_attr( DT_Webform_Utilities::get_theme( $dt_webform_meta['theme'] ?? '', $dt_webform_token ) ) ?>
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
            <input type="tel" id="phone" name="phone" class="input-text input-phone" value="" <?php echo ( $dt_webform_core_fields['phone_field']['required'] === 'yes' ) ? 'required' : '' ?>/>
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

            switch ($dt_webform_value['type']) {
                // multi labels, multi values
                case 'dropdown':
                    $list = DT_Webform_Active_Form_Post_Type::match_labels_with_values( $dt_webform_value['labels'], $dt_webform_value['values'] );
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
                                    echo '<option value="' . esc_attr( $item['value'] ) . '">' . esc_html( $item['label'] ) . '</option>';
                                }
                                ?>
                                </select>
                        </div>
                        <?php
                    }
                    break;
                case 'multi_radio':
                    $list = DT_Webform_Active_Form_Post_Type::match_labels_with_values( $dt_webform_value['labels'], $dt_webform_value['values'] );
                    if ( count( $list ) > 0 ) {
                        ?>
                        <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                             class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">
                                <label for="<?php echo esc_attr( $dt_webform_key ) ?>"
                                       class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_attr( $dt_webform_value['title'] ) ?></label><br>
                                <?php
                                foreach ( $list as $item ) {
                                    if ( isset( $item['label'] ) && isset( $item['value'] ) ) {
                                        echo '<span class="span-radio"><input type="radio" class="input-' . esc_attr( $dt_webform_value['type'] ) . '" name="' . esc_attr( $dt_webform_value['key'] ) . '" value="' . esc_attr( $item['value'] ) . '">' . esc_html( $item['label'] ) . '</span>';
                                    }
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
                               value="<?php echo esc_attr( $dt_webform_value['values'] ) ?>"
                               <?php echo esc_attr( ( $dt_webform_value['required'] === 'yes' ) ? 'required' : '' ) ?>
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
                                  class="input-textarea input-<?php echo esc_attr( $dt_webform_value['type'] ) ?>"></textarea>
                    </div>
                    <?php

                    break;

                case 'map':

                    if ( $dt_webform_value['values'] === 'click_map' ) {
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
                                    jQuery.get('<?php echo esc_url( trailingslashit( get_template_directory_uri() ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
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
                                    jQuery.get('<?php echo esc_url( trailingslashit( get_template_directory_uri() ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
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

                    break;
                default:
                    break;
            }
        }
    }
    ?>

    <div class="section" id="submit-button-container"><br>
        <button type="button" class="submit-button" id="submit-button" onclick="check_form()" disabled><?php esc_attr_e( 'Submit', 'dt_webform' ) ?></button>
    </div>

</form>

<div id="report"></div>
</div> <!-- wrapper-->
</body>
</html>
