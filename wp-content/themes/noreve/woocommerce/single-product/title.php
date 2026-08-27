<?php
/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://woocommerce.com/document/template-structure/
 * @package    WooCommerce\Templates
 * @version    1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $product;
$postId = get_the_ID();
$getSubtitle = get_field('subtitle', $postId);
$getBestSeller = get_field('best_seller', $postId);

$countReviews = $product->get_review_count();
$average = $product->get_average_rating();
?>



	<div class="row align-items-start">
		<div class="col-md-8">
			<?php  the_title( '<h1 class="product_title entry-title">', '</h1>' );?>
			<?php if($getSubtitle){ ?>
				<p class="produdct_subtitle fnt-20 fnt-thin"><?php print $getSubtitle; ?></p>
			<?php }?>	
		</div>
		<div class="col-md-4 text-end">
			<p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?>">
				<?php echo $product->get_price_html(); ?>
			</p>
			<small class="fnt-14 fnt-thin"><?php pll_e('Vat'); ?></small>		
		</div>
	</div>

	<div class="spacer-20"></div>

	<?php if($getBestSeller == 1){ ?>
		<div class="bestseller">
			<p>Best Sellers</p>
		</div>
	<?php }?>

	<div class="shipping">
		<p><img src="<?php print get_template_directory_uri(); ?>/img/icons/delivery.svg" class="" alt="icons"> Get it on March 6th!</p>
	</div>	

	<div class="rating">
		<p><?php print $average?> <i class="fa-solid fa-star fnt-yellow"></i>  <?php print $countReviews;?> reviews</p>
	</div>








