<?php
/**
 * My Account navigation
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
$avatar = get_avatar_url( $current_user->ID );

do_action( 'woocommerce_before_account_navigation' );
?>

<?php //print_r($current_user); ?>

<nav class="woocommerce-MyAccount-navigation" aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">
	<ul>

		<li>
			<div class="profile-details">

				<div class="row align-items-center">

					<div class="col-2">

						<div class="profile-image">
							<center>
								<img src="<?php print $avatar; ?>" alt="icons" class="img-fluid">
							</center>	
						</div>
					
					</div>
					<div class="col-10">
						<h4 class="text-uppercase remMar fnt-20"><?php print $current_user->data->display_name; ?></h4>
						<p class="remMar fnt-14"><?php print $current_user->data->user_email; ?></p>
					</div>
					
				</div>
				
			</div>
		</li>


		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
