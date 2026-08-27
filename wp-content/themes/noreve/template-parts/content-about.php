<?php
/**
 * Template Name: About Page
 * Template part for displaying About Page
 *
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

  <?php $getAbout = getSections(441); ?>

  <!--about-->
  <section id="about" class="" data-scroll-section>
  	<div class="spacer-80"></div>

  		<div class="container">

            <?php
               if ( function_exists( 'woocommerce_breadcrumb' ) ) {
                   woocommerce_breadcrumb();
               }
            ?>

         <div class="section-header">
            <h1 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
               <?php print $getAbout['title_top'];?>
            </h1>
         </div>

         <div class="spacer-40"></div>

         <div class="section-body">
            <div class="about-content">

               <div class="preAnimate" data-scroll data-scroll-repeat data-scroll-delay="60" data-scroll-class="animateThis">
               
               <?php print $getAbout['content'];?>

               </div>

               <div class="spacer-40"></div>

               <div class="preAnimate" data-scroll data-scroll-repeat data-scroll-delay="120" data-scroll-class="animateThis">

                  <?php print $getAbout['attachment']['editor'];?>

               </div>
                     
         </div>

  		</div>

     <div class="spacer-80"></div>
  </section>

  <?php $getAboutHistory = getSections(444); ?>

  <!--history-->
  <section id="history" class="" data-scroll-section>
   <div class="spacer-80"></div>

   <div class="container">

      <div class="section-header">
         <h2 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
            <?php print $getAboutHistory['title_top'];?>
         </h2>
      </div> 

      <div class="spacer-80"></div>

      <div class="section-body">

         <?php print $getAboutHistory['content'];?>
         
      </div>
      
   </div>

   <div class="spacer-80"></div>
  </section>
 <?php get_footer(); ?>
