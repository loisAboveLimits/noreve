<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' ); ?>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

<div class="" id="customer_login">

	<div class="login-form">

<?php endif; ?>

		<!-- <h2><?php esc_html_e( 'Login', 'woocommerce' ); ?></h2> -->

		<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-control gernatic-form" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" placeholder="<?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>" /><?php // @codingStandardsIgnoreLine ?>

			<div class="spacer-10"></div>

			<input class="woocommerce-Input woocommerce-Input--text input-text form-control gernatic-form" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" placeholder="<?php esc_html_e( 'Password', 'woocommerce' ); ?>" />


			<?php do_action( 'woocommerce_login_form' ); ?>

				<!-- <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
				</label> -->

				<div class="spacer-10"></div>

				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> btn btn-sm btn-gernetic-black-square w-100" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
			
			<div class="spacer-10"></div>

			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

	</div>

	<div class="register-form">

		<!-- <h2><?php esc_html_e( 'Register', 'woocommerce' ); ?></h2> -->

		<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

			<?php do_action( 'woocommerce_register_form_start' ); ?>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

				
				<!-- <label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label> -->

				<div class="spacer-10"></div>

				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-control gernatic-form" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" placeholder="<?php esc_html_e( 'Username', 'woocommerce' ); ?>" /><?php // @codingStandardsIgnoreLine ?>
				

			<?php endif; ?>

				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text form-control gernatic-form" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" placeholder="<?php esc_html_e( 'Email address', 'woocommerce' ); ?>" /><?php // @codingStandardsIgnoreLine ?>

			<!-- <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
				
			</p> -->

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				
					<!-- <label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label> -->

					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text form-control gernatic-form" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" placeholder="<?php esc_html_e( 'Password', 'woocommerce' ); ?>" />
				

			<?php else : ?>

				<small><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></small>
				<div class="spacer-10"></div>
				
			<?php endif; ?>

			<?php do_action( 'woocommerce_register_form' ); ?>

				<div class="spacer-10"></div>

				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit btn btn-sm btn-gernetic-black-square w-100" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
			

			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>

	</div>

</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
