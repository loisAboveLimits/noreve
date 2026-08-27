<?php
/**
 * Checkout coupon form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-coupon.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) { // @codingStandardsIgnoreLine.
	return;
}

$registration_at_checkout   = WC_Checkout::instance()->is_registration_enabled();
$login_reminder_at_checkout = 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );

if ( is_user_logged_in() ) {
	return;
}

?>

<div class="row">
	
	<div class="col-md-5">
	
			<?php

			if ( $login_reminder_at_checkout ) : ?>
				<div class="woocommerce-form-login-toggle">
					<?php
					wc_print_notice(
						apply_filters( 'woocommerce_checkout_login_message', esc_html__( 'Returning customer?', 'woocommerce' ) ) . // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
						' <a href="#" class="showlogin">' . esc_html__( 'Click here to login', 'woocommerce' ) . '</a>',
						'notice'
					);
					?>
				</div>
				<?php
			endif;

			if ( $registration_at_checkout || $login_reminder_at_checkout ) :

				// Always show the form after a login attempt.
				$show_form = isset( $_POST['login'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				woocommerce_login_form(
					array(
						'message'  => esc_html__( 'If you have shopped with us before, please enter your details below. If you are a new customer, please proceed to the Billing section.', 'woocommerce' ),
						'redirect' => wc_get_checkout_url(),
						'hidden'   => ! $show_form,
					)
				);
			endif;
			?>
	</div>
<div class="col-md-7">

	<div class="woocommerce-form-coupon-toggle">
		<?php
			/**
			 * Filter checkout coupon message.
			 *
			 * @param string $message coupon message.
			 * @return string Filtered message.
			 *
			 * @since 1.0.0
			 */
			wc_print_notice( apply_filters( 'woocommerce_checkout_coupon_message', esc_html__( 'Have a coupon?', 'woocommerce' ) . ' <a href="#" role="button" aria-label="' . esc_attr__( 'Enter your coupon code', 'woocommerce' ) . '" aria-controls="woocommerce-checkout-form-coupon" aria-expanded="false" class="showcoupon">' . esc_html__( 'Click here to enter your code', 'woocommerce' ) . '</a>' ), 'notice' );
		?>
	</div>

	<form class="checkout_coupon woocommerce-form-coupon" method="post" style="display:none" id="woocommerce-checkout-form-coupon">

		<p class="form-row form-row-first">
			<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
			<input type="text" name="coupon_code" class="input-text form-control gernatic-form" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" id="coupon_code" value="" />
		</p>

		<p class="form-row form-row-last">
			<button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> btn-gernetic-black-center" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
		</p>

		<div class="clear"></div>
	</form>


</div>

</div>
