<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$postId = get_the_ID();

$lang = pll_current_language();

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart w-100" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>



		<div class="row">
			
			<div class="col-md-6">

				<div class="custom-single-add-to-cart">

					<div class="input-group flex-nowrap">

					  <span class="input-group-text" id="basic-addon1">

							<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> btn btn-lg w-100 btn-single-gernetic-addtocart">
								<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
							</button>
							<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>				  	
					  </span>
					  
						<?php
							do_action( 'woocommerce_before_add_to_cart_quantity' );

							woocommerce_quantity_input(
								array(
									'min_value'   => $product->get_min_purchase_quantity(),
									'max_value'   => $product->get_max_purchase_quantity(),
									'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
									'classes' => "form-control input-single-gernetic-black"
								)
							);

							do_action( 'woocommerce_after_add_to_cart_quantity' );
						?>

					</div>					

				</div>


				<div class="spacer-20 mobile-view"></div>
			</div>

			<div class="col-md-6">
                <a href="#" class="action-icon tinvwl_add_to_wishlist_button tinvwl-position-after ftinvwl-animated wishlist-toggle w-100 btn btn-lg btn-single-gernetic-wishlist" data-tinv-wl-product="<?php print $postId; ?>" data-action="add_to_wishlist" data-tinv-wl-productvariation="0" data-tinv-wl-productvariations="[]" data-tinv-wl-producttype="simple" data-tinv-wl-action="addto">
                    <?php pll_e('Add to Wishlist'); ?>
                </a>
			</div>
			<div class="spacer-20 mobile-view"></div>
		</div>

	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
	
	<div class="spacer-40"></div>

	<div class="tamara-tabby">
		<?php do_shortcode('[tamara_show_popup price="'.WC()->cart->get_total().'" , currency="SAR" , language="'.$lang.'"]');?>
	</div>

	<!-- <div class="split-payment">

		<div class="row align-items-center">
			<div class="col-md-8">
				<p class="remMar">Pay in 4 interest-free installments totaling 45.75</p>
			</div>

			<div class="col-md-4">
				<ul class="nav justify-content-end">
					<li>
						

					</li>
					<li><img src="<?php print get_template_directory_uri(); ?>/img/icons/tabby.svg" alt="icons"></li>
					<li><img src="<?php print get_template_directory_uri(); ?>/img/icons/tamara.svg" alt="icons"></li>
				</ul>				

			</div>
		</div>
	</div> -->
