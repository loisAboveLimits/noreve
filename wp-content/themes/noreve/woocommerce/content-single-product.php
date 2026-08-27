<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$postId = get_the_ID();
$getMidSecTitle = get_field("mid_section_title", $postId);
$getMidImage = get_field("mid_section_image", $postId);
$getMidImageMobile = get_field("mid_section_image_mobile", $postId);

$countReviews = $product->get_review_count();
$average = $product->get_average_rating();

$lang = pll_current_language();
$iconsLang = "en";
if($lang == "ar"){$iconsLang = "ar";}
if($lang == "fr"){$iconsLang = "en";}

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

	<div class="row">
		<div class="col-md-5">

			<div class="section-header">

				<?php
				/**
				 * Hook: woocommerce_before_single_product_summary.
				 *
				 * @hooked woocommerce_show_product_sale_flash - 10
				 * @hooked woocommerce_show_product_images - 20
				 */
				do_action( 'woocommerce_before_single_product_summary' );
				?>	

			</div>	

			<div class="spacer-40"></div>
		</div>

		<div class="col-md-1"></div>

		<div class="col-md-6">

			<div class="section-body">
				
				<div class="summary entry-summary">
					<?php
					/**
					 * Hook: woocommerce_single_product_summary.
					 *
					 * @hooked woocommerce_template_single_title - 5
					 * @hooked woocommerce_template_single_rating - 10
					 * @hooked woocommerce_template_single_price - 10
					 * @hooked woocommerce_template_single_excerpt - 20
					 * @hooked woocommerce_template_single_add_to_cart - 30
					 * @hooked woocommerce_template_single_meta - 40
					 * @hooked woocommerce_template_single_sharing - 50
					 * @hooked WC_Structured_Data::generate_product_data() - 60
					 */
					do_action( 'woocommerce_single_product_summary' );
					?>
					
					<?php the_content(); ?>


					<?php
					/**
					 * Hook: woocommerce_after_single_product_summary.
					 *
					 * @hooked woocommerce_output_product_data_tabs - 10
					 * @hooked woocommerce_upsell_display - 15
					 * @hooked woocommerce_output_related_products - 20
					 */
					//do_action( 'woocommerce_after_single_product_summary' );
					?>
				</div>
			</div>

			<div class="spacer-40"></div>
		</div>


	</div>

	<div class="spacer-40"></div>

	<?php if($getMidSecTitle) { ?>

		<div class="mid-section">
			
			<h2 class="mid-section-title"><?php print get_field("mid_section_title", $postId); ?></h2>
			<div class="spacer-20"></div>

			<div class="mid-section-img">
				<img src="<?php print $getMidImage; ?>" alt="product" class="img-fluid w-100 desktop-view">
				<img src="<?php print $getMidImageMobile; ?>" alt="product" class="img-fluid w-100 mobile-view">
			</div>

		</div>

	<?php } ?>

	<div class="spacer-100"></div>

	<div class="related-products">

		<?php $getRelatedProds = getRelatedProducts($postId);?>

		<div class="row align-items-end">

			<div class="col-md-8">
				<h2 class="mid-section-title"><?php pll_e('Suggested Products'); ?></h2>
			</div>

			<div class="col-md-4 text-end">
				<a class="fnt-20 fnt-black text-decoration-underline fnt-Semibold" href="<?php print get_pll_links(54); ?>"><?php pll_e('All'); ?></a>
			</div>
			

		</div>
		
		<div class="spacer-20"></div>

      <div id="product-list" class="owl-carousel">

      	<?php foreach($getRelatedProds as $rowRelatedProds){ ?>

            <div class="item" data-catid="<?php print $rowRelatedProds['categories']; ?>">

               <div class="product-box">

                  <a href="<?php print $rowRelatedProds['link']; ?>">
                     <div class="product-rating product-padding">
                        <div class="row align-items-center">

                           <div class="col-6">
                              <p class="number-rating d-flex justify-content-start fnt-bold fnt-20 remMar">
                                 <font class="fa-mar-10">4.4</font> 
                                 <img src="<?php print get_template_directory_uri(); ?>/img/icons/star.svg" class="" alt="icons">
                              </p>
                           </div>

                           <div class="col-6">
                              <p class="whislist-rating d-flex justify-content-end remMar">
                                 <img src="<?php print get_template_directory_uri(); ?>/img/icons/love.svg" class="fa-mar-5" alt="icons">
                              </p>
                           </div>
                           
                        </div>                              
                     </div>

                     <div class="spacer-20"></div>

                     <div class="product-img product-padding">
                        <div class="row">
                           <!-- <div class="col-md-3"></div> -->
                           <div class="col-md-12">
                              <center>
                                 <img src="<?php print $rowRelatedProds['image']; ?>" class="img-fluid" alt="product">                      
                              </center>
                           </div>
                           <!-- <div class="col-md-3"></div> -->
                        </div>
                     </div>

                     <div class="spacer-20"></div>

                     <div class="product-details">

                        <div class="product-padding">

                           <h4 class="product-title"><?php print $rowRelatedProds['title']; ?></h4>
                           <div class="spacer-20"></div>
                           
                           <div class="row align-items-end">

                              <div class="col-6">

                                 <?php if(empty($rowRelatedProds['details']['sale_price']) || $rowRelatedProds['details']['sale_price'] == ""){?>
                                      <p class="product-price">
                                      		<?php print $rowRelatedProds['details']['price'];?>
                                          <?php print $rowRelatedProds['details']['currency'];?> 
                                          
                                       </p>

                                    <?php }else{ ?>

                                      <p class="product-price">
                                      		<?php print $rowRelatedProds['details']['sale_price'];?>
                                          <?php print $rowRelatedProds['details']['currency'];?> 
                                          
                                          <font class="product-price-sale">
                                          	<?php print $rowRelatedProds['details']['price'];?>
                                             <?php print $rowRelatedProds['details']['currency'];?> 
                                             
                                          </font>
                                       </p>

                                    <?php } ?>
                              </div>

                              <div class="col-6 text-end">
                                 <!-- <p class="product-weigth">50 ml</p> -->
                              </div>
                           </div>
                           
                        </div>
                        
                     </div>
                  </a>

               </div>

            </div>      		

      	<?php } ?>

      </div>
		

	</div>

	<div class="spacer-100"></div>

	<div id="review">

		<div class="row align-items-end">

			<div class="col-md-8">
				<h2 class="mid-section-title"><?php pll_e('Product Reviews'); ?></h2>
			</div>

			<div class="col-md-4 text-end">
				<div class="rating">
					<p><?php print $average?> <i class="fa-solid fa-star fnt-yellow"></i>  <?php print $countReviews;?> reviews</p>
				</div>
			</div>	

		</div>	

		<div class="spacer-20"></div>
		

         <?php $getTestimonies = getGlobalTestimony("b2c");?>

         <div id="review-list" class="owl-carousel">

            <?php foreach($getTestimonies as $rowtestimony){ ?>

                <div class="item posRel">
                  <div class="review-bg">
                     <img src="<?php print get_template_directory_uri(); ?>/img/icons/review-frame-<?php print $iconsLang; ?>.png" class="img-fluid" alt="bg">
                  </div>

                  <div class="review-content">
                     <p><?php print $rowtestimony['content']; ?></p>
                  </div>

                  <div class="review-user d-flex justify-content-start">
                     <img src="<?php print $rowtestimony['featImg']; ?>" class="img-fluid" alt="user">
                     <p><?php print $rowtestimony['title']; ?></p>
                  </div>

               </div>                  

            <?php } ?>

         </div>

       	<div class="spacer-20"></div>

        <!-- <div class="review-dot position-absolute start-50 translate-middle">

           <div class="row ">
              <div class="col-md-2"></div>
              <div class="col-md-8">

                 <div class="bird-dots d-flex align-items-center justify-content-center">
                    <a class="btn-dot active" data-slide="0"></a>
                    <a class="btn-dot" data-slide="1"></a>
                    <a class="btn-dot" data-slide="2"></a>
                    <a class="btn-dot" data-slide="3"></a>
                    <a class="btn-dot" data-slide="4"></a>
                 </div>

              </div>
              <div class="col-md-2"></div>
           </div>

        </div> -->           
	</div>

	<div class="spacer-100"></div>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
