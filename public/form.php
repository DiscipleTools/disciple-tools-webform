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
$dt_webform_campaigns = ! empty( $_GET['campaigns'] ) ? sanitize_text_field( wp_unslash( $_GET['campaigns'] ) ) : '';
$dt_webform_meta = DT_Webform_Utilities::get_form_meta( $dt_webform_token );
$dt_webform_core_fields = DT_Webform_Active_Form_Post_Type::get_core_fields_by_token( $dt_webform_token );
$dt_webform_fields = DT_Webform_Active_Form_Post_Type::get_extra_fields( $dt_webform_token );
$public_url = trailingslashit( plugin_dir_url( __FILE__ ) );

if ( isset( $dt_webform_meta['disable'] ) && 'disabled' === $dt_webform_meta['disable'] ) {
    die( 'form is disabled' );
}

?>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $dt_webform_core_fields['header_title_field']['label'] ?? '' ) ?></title>

    <?php DT_Webform_Utilities::echo_form_html_scripts_and_styles( $dt_webform_token, $dt_webform_meta, $dt_webform_fields, $public_url ); ?>

</head>
<body>
<div id="wrapper">
<form id="contact-form" action="">

    <?php DT_Webform_Utilities::echo_form_html( $dt_webform_token, $dt_webform_campaigns, $dt_webform_core_fields, $dt_webform_fields, $public_url ); ?>

</form>

<div id="report"></div>
<div id="offlineWarningContainer"></div>
</div> <!-- wrapper-->
</body>
</html>

<?php
function form_click_map( $dt_webform_value ) {
    $public_url = trailingslashit( plugin_dir_url( __FILE__ ) );
    dt_write_log(__METHOD__);
    dt_write_log($public_url);
    ?>
    <div id="section-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
         class="section section-<?php echo esc_attr( $dt_webform_value['type'] ) ?>">

        <?php if ( ! empty( $dt_webform_value['labels'] ) ) : ?>
            <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>"
                   class="input-label label-<?php echo esc_attr( $dt_webform_value['type'] ) ?> label-<?php echo esc_attr( $dt_webform_value['key'] ) ?>"><?php echo esc_html( $dt_webform_value['labels'] ) ?? '' ?></label>
        <?php endif;

        wp_enqueue_style( 'mapbox-css', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.0/mapbox-gl-geocoder.css', [], "4.4" );
        wp_enqueue_script( 'mapbox-script', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.0/mapbox-gl-geocoder.css', [], '4.4' );
        ?>

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
            window.spinner = '<img class="load-spinner" src="<?php echo $public_url . 'public/spinner.svg' ?>" width="20px" />'
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
                jQuery.get('<?php echo esc_url( trailingslashit( plugin_dir_url( __DIR__ ) ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
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
                jQuery.get( '<?php echo esc_url( trailingslashit( plugin_dir_url( __DIR__ ) ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
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
