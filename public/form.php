<?php
if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
    die( 'missing server info' );
}
// @codingStandardsIgnoreLine
require( $_SERVER[ 'DOCUMENT_ROOT' ] . '/wp-load.php' );

if ( ! isset( $_GET['token'] ) ) {
    die( 'missing token' );
}
$dt_webform_token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
$dt_webform_meta = DT_Webform_Remote::get_form_meta( $dt_webform_token );

?>
<html>
<head>
    <?php
    /**
     * Coding standards require enqueue of files, but for the purpose of a light iframe, we don't want
     * to load an entire site header. Therefore these files are to ignore standards.
     */
    // @codingStandardsIgnoreStart ?>
    <script type="text/javascript" src="jquery.min.js"></script>
    <script type="text/javascript" src="jquery-migrate.min.js"></script>
    <script type="text/javascript" src="jquery.validate.min.js"></script>
    <script type="text/javascript" src="public.js"></script>
    <?php
    // @codingStandardsIgnoreEnd ?>

    <style>
        td {
            padding: .5em;
        }

        input.input-text {
            padding: .5em;
        }

        button.submit-button {
            padding: .8em;
            font-weight: bolder;
        }
        p.title {
            font-size: 1.5em;
            font-weight: bold;
        }
        label.error {
            color: red;
            font-size: .8em;
        }
        <?php echo esc_attr( DT_Webform_Remote::get_custom_css( $dt_webform_token ) ) ?>
    </style>

</head>
<body style="background-color:#ffffff">

<p id="title" class="title"><?php echo esc_attr( isset( $dt_webform_meta['title'] ) ? $dt_webform_meta['title'] : '' ) ?></p>

<form id="contact-form" action="">

    <input type="hidden" id="token" name="token" value="<?php echo esc_attr( $dt_webform_token ) ?>"/>
    <div class="errorTxt"></div>

    <p class="section">
        <label for="name"><?php esc_attr_e( 'Name', 'dt_webform') ?></label><br>
        <input type="text" id="name" name="name" class="input-text" value="" required/><br>
    </p>
    <p class="section">
        <label for="phone"><?php esc_attr_e( 'Phone', 'dt_webform') ?></label><br>
        <input type="tel" id="phone" name="phone" class="input-text" value="" required/><br>
    </p>
    <p class="section">
        <label for="email"><?php esc_attr_e( 'Email', 'dt_webform') ?></label><br>
        <input type="email" id="email" name="email" class="input-text" style="display:none;" value=""/>
        <input type="email" id="l" name="l" class="input-text" value=""/><br>
    </p>

    <?php
    $fields = DT_Webform_Active_Form_Post_Type::get_extra_fields( $dt_webform_token );
    if( count( $fields) > 0 ) {
        foreach( $fields as $field ) {
        ?>
            <p>
                <label for="<?php echo $field['key'] ?>"><?php echo $field['label'] ?></label><br>
                <input type="<?php echo $field['type'] ?>" id="<?php echo $field['key'] ?>" name="<?php echo $field['key'] ?>" class="input-text" value="" <?php echo $field['required'] ? 'required' : '' ?>/><br>
            </p>
        <?php
        }
    }
    ?>

    <p class="section">
        <button type="button" class="submit-button" id="submit-button" onclick="check_form()" disabled>Submit</button>
    </p>

</form>

<div id="report"></div>

<script>
    let validator = jQuery('#contact-form').validate({
        errorPlacement: function(error, element) {
            error.appendTo( element.parent("p") );
        },
        rules: {
            name: {
                required: true,
                minlength: 2,
            },
            phone: {
                required: true,
                minlength: 10
            },
            l: {
                required: false,
                email: true

            }
        },
        messages: {
            name: {
                required: "Name required",
                minlength: jQuery.validator.format("At least {0} characters required!")
            },
            phone: {
                required: "Phone required",
                minlength: jQuery.validator.format("At least {0} characters required!")
            }
        },
        submitHandler: function(form) {
            submit_form()
        }

    });
    validator.form()

    jQuery(document).ready(function () {

        // This is a form delay to discourage robots
        let counter = 7;
        let myInterval = setInterval(function () {
            let button = jQuery('#submit-button')

            button.html( 'Submit in ' + counter + ' seconds' )
            --counter;

            if ( counter === 0 ) {
                clearInterval(myInterval);
                button.html( 'Submit' ).prop('disabled', false)
            }

        }, 1000);


    })
</script>

</body>
</html>