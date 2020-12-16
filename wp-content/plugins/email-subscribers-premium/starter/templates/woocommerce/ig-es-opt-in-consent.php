<?php
/**
 * Override this template by copying it to yourtheme/woocommerce/ig-es-opt-in-consent.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$consent_text = get_site_option( 'ig_es_opt_in_consent_text', '' );
$allowed_tags = ig_es_allowed_html_tags_in_esc();
?>

<p class="ig-es-opt-in-consent-wrapper form-row">
	<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
		<input id="ig-es-opt-in-consent" name="ig-es-opt-in-consent" type="checkbox" value="yes">
		<span for="ig-es-opt-in-consent-text">
			<?php
				echo wp_kses( $consent_text , $allowed_tags ); 
			?>
		</span>
	</label>
</p>
