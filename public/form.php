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
        #email2 { display:none; }
        <?php echo esc_attr( DT_Webform_Remote::get_theme( $dt_webform_meta['theme'] ?? '' ) ) ?>
        <?php echo esc_attr( DT_Webform_Remote::get_custom_css( $dt_webform_token ) ) ?>
    </style>

</head>
<body style="background-color:#ffffff">

<p id="title" class="title"><?php echo esc_attr( $dt_webform_meta['title'] ?? '' ) ?></p>

<form id="contact-form" action="">

    <input type="hidden" id="token" name="token" value="<?php echo esc_attr( $dt_webform_token ) ?>"/>
    <input type="hidden" id="hidden_input" name="hidden_input" value="<?php echo esc_attr( $dt_webform_meta['hidden_input'] ?? '' ) ?>"/>
    <input type="hidden" id="ip_address" name="ip_address" value="<?php echo esc_attr( DT_Webform_Settings::get_real_ip_address() ?? '' ) ?>"/>

    <p class="section">
        <label for="name" class="input-label"><?php echo isset( $dt_webform_meta['name'] ) ? esc_attr( $dt_webform_meta['name'] ) : 'Name' ?></label><br>
        <input type="text" id="name" name="name" class="input-text" value="" required/><br>
    </p>
    <p class="section">
        <label for="phone" class="input-label"><?php echo isset( $dt_webform_meta['phone'] ) ? esc_attr( $dt_webform_meta['phone'] ) : 'Phone' ?></label><br>
        <input type="tel" id="phone" name="phone" class="input-text" value="" required/><br>
    </p>
    <p class="section">
        <label for="email" class="input-label"><?php echo isset( $dt_webform_meta['email'] ) ? esc_attr( $dt_webform_meta['email'] ) : 'Email' ?></label><br>
        <input type="email" id="email2" name="email2" class="input-text" value=""/>
        <input type="email" id="email" name="email" class="input-text" value=""/><br>
    </p>

    <?php
    /**
     * Add custom fields to form
     */
    $dt_webform_fields = DT_Webform_Active_Form_Post_Type::get_extra_fields( $dt_webform_token );
    if ( count( $dt_webform_fields ) > 0 ) {
        foreach ( $dt_webform_fields as $dt_webform_key => $dt_webform_value ) {
            $dt_webform_value = maybe_unserialize( $dt_webform_value );
        ?>
            <p>
                <label for="<?php echo esc_attr( $dt_webform_value['key'] ) ?>" class="input-label"><?php echo esc_attr( $dt_webform_value['label'] ) ?></label><br>
                <input type="<?php echo esc_attr( $dt_webform_value['type'] ) ?>" id="<?php echo esc_attr( $dt_webform_value['key'] ) ?>" name="<?php echo esc_attr( $dt_webform_value['key'] ) ?>" class="input-text" value="" <?php echo esc_attr( $dt_webform_value['required'] == 'yes' ? 'required' : '' ) ?>/><br>
            </p>
        <?php
        }
    }
    ?>

    <p class="section">
        <label for="comments" class="input-label"><?php echo esc_attr( $dt_webform_meta['comments_title'] ?? esc_attr__( 'Comments', 'dt_webform' ) ) ?></label><br>
        <textarea name="comments" id="comments" class="input-text input-textarea"></textarea><br>
    </p>
    <p class="section">
        <button type="button" class="submit-button" id="submit-button" onclick="check_form()" disabled><?php esc_attr_e( 'Submit', 'dt_webform' ) ?></button>
    </p>

</form>

<div id="report"></div>

</body>
</html>