<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Gernetic
 */

    $lang = pll_current_language();
    get_header();

    $postId = get_the_ID();

    $iconsLang = "en";
    if($lang == "ar"){$iconsLang = "ar";}
    if($lang == "fr"){$iconsLang = "en";}

?>

  <section class="" data-scroll-section>
  	<div class="spacer-80"></div>

  		<div class="container">

             <?php
                if ( function_exists( 'woocommerce_breadcrumb' ) ) {
                    woocommerce_breadcrumb();
                }
             ?>

            <div class="section-header">
               <?php if (is_cart() || is_checkout()){ ?>

                   <h1 class="section-title fnt-gold">  
                      <?php the_title();?>
                   </h1>

               <?php }else{ ?>

                   <h1 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">  
                      <?php the_title();?>
                   </h1>

               <?php } ?>


            </div>         

  		    <div class="spacer-40"></div>

             <div class="section-body">

                <?php the_content();?>

            </div>

  		</div>

     <div class="spacer-80"></div>
  </section>


 <?php get_footer(); ?>
