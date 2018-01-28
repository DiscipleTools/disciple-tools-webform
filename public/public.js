//Get URL Parameters
let getUrlParameter = function getUrlParameter(sParam) {
    let sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

function submit_form() {
    let url = get_url()
    let $inputs = jQuery(':input');
    let data = {};
    $inputs.each(function() {
        data[this.name] = jQuery(this).val();
    });

    return jQuery.ajax({
        type: "POST",
        data: JSON.stringify(data),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: url + '/wp-json/dt-public/v1/webform/form_submit',
    })
        .done(function (data) {
            jQuery('#report').append('Success')

        })
        .fail(function (err) {
            jQuery('#report').html('Failed')
        });
}

function get_url() {
    return window.location.protocol + '//' + window.location.hostname
}