jQuery(document).ready(function() {

});

function check_link_status(id, token, url ) {
    return jQuery.ajax({
        type: "POST",
        data: JSON.stringify({"id": id, "token": token }),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: url + '/wp-json/dt-public/v1/webform/site_link_check',
    })
        .done(function (data) {
            jQuery('#' + id + '-status').html('Linked')
        })
        .fail(function (err) {
            jQuery('#' + id + '-status').html('Not Linked')
        })
        ;
}