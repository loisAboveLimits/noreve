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

    $getBannerD = get_field('image', $postId);
    $getBannerM = get_field('image_mobile', $postId);
    $getTheDate = get_the_date("d F Y")

?>

<section id="blogs" class="" data-scroll-section>
	<div class="spacer-80"></div>

	<div class="container">

        <?php
           if ( function_exists( 'woocommerce_breadcrumb' ) ) {
               woocommerce_breadcrumb();
           }
        ?>         

        <div class="spacer-20"></div>


		<div class="row">
			<div class="col-md-1"></div>

			<div class="col-md-10">
				<center>
					<h1><?php the_title(); ?></h1>
				</center>
				
			</div>
			<div class="col-md-1"></div>
		</div>

		<div class="spacer-20"></div>
		
		
		<div class="banner-post posRel">

			<?php if($getBannerD){ ?>

				<div class="desktop-view bordered-radius">

					<center>
						<img src="<?php print $getBannerD; ?>" alt="banner" class="img-fluid w-100">
					</center>

				</div>

			<?php } ?>

			<?php if($getBannerM){ ?>

				<div class="mobile-view bordered-radius">

					<center>
						<img src="<?php print $getBannerM; ?>" alt="banner" class="img-fluid w-100">
					</center>

				</div>

			<?php } ?>			

			<div class="date">

				<p class="fnt-14 fnt-white"><i class="fa-solid fa-calendar"></i> <?php print $getTheDate;?></p>
				
			</div>


		</div>

		<div class="spacer-40"></div>

		<div class="content">
			
			<?php the_content(); ?>

		</div>

		<div class="spacer-80"></div>

		<div class="other-blogs">

            <h4 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">  
               <?php pll_e('Other Articles'); ?>
            </h4>
			
            <div class="spacer-20"></div>

			<?php $getOthers = getOtherBlogs($postId); ?>

			<?php //print_r($getOthers); ?>

			<div class="blog-list">

				<div id="product-lists" class="row">

					<?php foreach($getOthers as $rowOther){ ?>

						<div class="col-6 col-md-4 item">
							<div class="card h-100 w-100 product-box">

								<a href="<?php print $rowOther['link'];?>" class="h-100 posRel">

                                    <div class="product-img">

                                      <div class="row">
                                         
                                         <div class="col-md-12">
                                            <center>
                                               <img src="<?php print $rowOther['featImg'];?>" alt="blog" class="img-fluid w-100">                    
                                            </center>
                                         </div>
                                        
                                      </div>

                                    </div>

                                    <div class="product-details">

                                    	<div class="product-padding">
                                    		<h4 class="product-title"><?php print $rowOther['title'];?></h4>
                                    	</div>

                                    </div>

								</a>



							</div>
						</div>

					<?php } ?>

				</div>				

			</div>



		</div>
		

	</div>


	<div class="spacer-80"></div>
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
