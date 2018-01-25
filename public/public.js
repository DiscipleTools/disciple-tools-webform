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
    let url = 'http://remote'
    let data = {
        "token": jQuery('#token').val(),
        "first_name": jQuery('#first_name').val(),
        "last_name": jQuery('#last_name').val(),
        "email": jQuery('#email').val(),
        "phone": jQuery('#phone').val()
    }
    return jQuery.ajax({
        type: "POST",
        data: JSON.stringify(data),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: url + '/wp-json/dt-public/v1/webform/form_submit',
    })
        .done(function (data) {
            jQuery.each(data, function(n, i) {
                jQuery('#report').append(n + ': ' + i + '<br>')
            })

        })
        .fail(function (err) {
            jQuery('#report').html('Not Linked')
        })
        ;
}