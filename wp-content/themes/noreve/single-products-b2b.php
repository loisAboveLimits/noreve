<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Gernetic
 */


    $lang = pll_current_language();
    get_header();

    $postId = get_the_ID();

    $iconsLang = "en";
    if($lang == "ar"){$iconsLang = "ar";}
    if($lang == "fr"){$iconsLang = "en";}

    $getFeatImg = get_featured_image_url(get_the_ID());
    $getGallery = get_field("gallery", get_the_ID());

    // $countReviews = $product->get_review_count();
	 // $average = $product->get_average_rating();

    $countReviews = 4;
	 $average = 5;

?>

<section id="products" class="" data-id="<?php the_ID();?>" data-scroll-section>
	<div class="spacer-80"></div>

	<div class="container">

        <?php
           if ( function_exists( 'woocommerce_breadcrumb' ) ) {
               woocommerce_breadcrumb();
           }
        ?>         


      <div class="row align-items-start">

      	<div class="col-md-5">

      		<div class="product-images ratio ratio-1x1">

      			<img src="<?php print $getFeatImg;?>" alt="product" class="img-fluid">


      		</div>
      		
      		<div class="spacer-40 mobile-view"></div>
      	</div>

      	<div class="col-md-1"></div>

      	<div class="col-md-6">
      		<h1 class="product_title entry-title"><?php the_title();?></h1>

      		<div class="spacer-40"></div>

            <div class="row">
               <div class="col-md-8">

                  <a href="#" 
                     class="addToQuote singleAddToQuote" 
                     data-product-id="<?php the_ID();?>"
                     data-bs-toggle="tooltip" 
                     data-bs-placement="top" 
                     data-bs-title="<?php pll_e('Added to Quotation'); ?>"                        
                  >

                     <?php pll_e('Add To Quote'); ?>

                  </a>

               </div>
               <div class="col-md-4">

               </div>
            </div>

      		 

      		 <div class="spacer-20"></div>

      		<?php the_content(); ?>

      		<div class="spacer-40 mobile-view"></div>
      	</div>

      </div>
		

      <div class="spacer-100"></div>

      <div class="related-products">

         <div class="row align-items-end">

            <div class="col-md-8">
               <h2 class="mid-section-title">Suggested Products</h2>
            </div>

            <div class="col-md-4 text-end">
               <a class="fnt-20 fnt-black text-decoration-underline fnt-Semibold" href="#">All</a>
            </div>
            

         </div>
         
         <div class="spacer-20"></div>

         <div id="product-list" class="owl-carousel">

            <?php for($i = 0; $i < 5; $i++){?>

               <div class="item" data-catid="<?php print $i;?>">

                  <div class="product-box">

                     <a href="">
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
                              <div class="col-md-3"></div>
                              <div class="col-md-6">
                                 <center>
                                    <img src="<?php print get_template_directory_uri(); ?>/img/products/product1.png" class="img-fluid" alt="product">                      
                                 </center>
                              </div>
                              <div class="col-md-3"></div>
                           </div>
                        </div>

                        <div class="spacer-20"></div>

                        <div class="product-details">

                           <div class="product-padding">

                              <h4 class="product-title">Aftershave Balm</h4>
                              <div class="spacer-20"></div>
                              
                              <div class="row align-items-end">

                                 <div class="col-6">
                                    <p class="product-price">SR 161</p>
                                 </div>

                                 <div class="col-6 text-end">
                                    <p class="product-weigth">50 ml</p>
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
               <h2 class="mid-section-title">Product Reviews</h2>
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

           </div>  -->          
      
      </div>
		

	</div>

	<div class="spacer-100"></div>

   <div id="partner-slider" class="owl-carousel other-page">

      <?php for($i = 0; $i < 9; $i++){?>
         <div class="item">
            <center>
               <img src="<?php print get_template_directory_uri(); ?>/img/partners/partner1.png" class="img-fluid" alt="partners">
            </center>
         </div>
      <?php }?>
      
   </div>  

</section>
	
	<!-- #main
	<main id="primary" class="site-main">



		<?php
		/*
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/content', get_post_type() );

			the_post_navigation(
				array(
					'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous:', 'gerenetic' ) . '</span> <span class="nav-title">%title</span>',
					'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next:', 'gerenetic' ) . '</span> <span class="nav-title">%title</span>',
				)
			);

			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endwhile; // End of the loop.
		*/
		?>

	</main> -->

<?php
//get_sidebar();
get_footer();
