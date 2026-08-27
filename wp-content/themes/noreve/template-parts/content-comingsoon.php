<?php
/**
 * Template Name: Coming Soon
 * Template part for displaying Coming Soon
 *
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Gernetic
 */


    $lang = pll_current_language();
    get_header();

    $iconsLang = "en";
    if($lang == "ar"){$iconsLang = "ar";}
    if($lang == "fr"){$iconsLang = "en";}
?>

  <section class="vh-100 bg-full" style="background-image: url('<?php print get_template_directory_uri(); ?>/img/home/banner-slide2.png');" data-scroll-section>
  	<div class="spacer-100"></div>

  		<div class="container vh-100">

         <div class="section-header">

            <div class="coming-soon position-absolute start-50 translate-middle">

               <div class="row">
                  <div class="col-md-2"></div>
                  <div class="col-md-8">
                     <center>
                        <h2 class="banner-title fnt-white fnt-thin preAnimate" data-scroll data-scroll-repeat data-scroll-delay="90" data-scroll-class="animateThis">
                           <?php pll_e('Coming Soon - Title'); ?>
                        </h2>
                        <div class="spacer-20"></div>

                        <p class="banner-subtitle fnt-white fnt-thin"><?php pll_e('Coming Soon - Subtitle'); ?></p>
                        <div class="spacer-40"></div>

                        <a href="<?php print get_pll_links(77);?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-black">
                           <?php pll_e('Coming Soon - Button'); ?>
                        <img src="<?php print get_template_directory_uri(); ?>/img/icons/discover-arrow-<?php print $iconsLang;?>.svg" class="btn-gernetic-arrow" alt="icon">
                     </a>
                     </center>
                  </div>
                  <div class="col-md-2"></div>
               </div>
            </div>

         </div>  

  		</div>

     <div class="spacer-100"></div>
  </section>


 <?php get_footer(); ?>
