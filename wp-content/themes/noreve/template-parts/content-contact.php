<?php
/**
 * Template Name: Contact Page
 * Template part for displaying Contact Page
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
   
   <?php $getcontent = getSections(459); ?>

  <section id="consultation" class="" data-scroll-section>
   <div class="spacer-80"></div>

      <div class="container">

            <?php
               if ( function_exists( 'woocommerce_breadcrumb' ) ) {
                   woocommerce_breadcrumb();
               }
            ?>         

         <div class="section-header">

            <h1 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">  
               <?php print $getcontent['title_top'];?>
            </h1>
            <p class="section-subtitle"><?php print $getcontent['title_middle'];?></p>
         </div>

         <div class="spacer-40"></div>

         <div class="section-body">

            <div class="row align-items-start">

               <div class="col-md-5">

                  <div class="contact-form">

                     <?php 
                        if($lang == "en"){ print do_shortcode('[contact-form-7 id="f922eec" title="Contact Us - EN"]');}
                        if($lang == "ar"){ print do_shortcode('[contact-form-7 id="654df8f" title="Contact Us - AR"]');}
                        if($lang == "fr"){ print do_shortcode('[contact-form-7 id="8b5c215" title="Contact Us - FR"]');}
                     ?>





                     <!-- <form action="">
                        <input type="text" name="fullname" class="form-control gernetic-form-lined"placeholder="Full Name">
                        <input type="email" name="email" class="form-control gernetic-form-lined" placeholder="E-mail">
                        <input type="tel" name="phone" class="form-control gernetic-form-lined" placeholder="Phone Number">
                        <input type="text" name="subject" class="form-control gernetic-form-lined" placeholder="Subject">
                        <textarea name="message" class="form-control gernetic-form-lined" rows="3" placeholder="Message"></textarea>

                        <div class="spacer-40"></div>

                        <button class="btn btn-lg btn-gernetic-black-square w-100">Send</button>
                     </form> -->


                   </div>

                   <div class="spacer-40 mobile-view"></div>
               </div>

               <div class="col-md-1"></div>

               <div class="col-md-6">
                  
                     <div class="bordered-radius">
                        <center>
                           <img src="<?php print $getcontent['attachment']['image'];?>" class="img-fluid w-100" alt="image">
                        </center>
                     </div>
                  
               </div>

            </div>

            <div class="spacer-80"></div>

            <div class="row align-items-start">

                  <?php

                      $social_media_and_contacts_options = get_option( 'social_media_and_contacts_option_name' ); // Array of All Options

                     //print_r($social_media_and_contacts_options);
                      $email_0 = $social_media_and_contacts_options['email_0']; // Email
                      $phone_1 = $social_media_and_contacts_options['phone_1']; // Phone
                      $mobile_2 = $social_media_and_contacts_options['mobile_2']; // Mobile
                      $instagram_3 = $social_media_and_contacts_options['instagram_3']; // Instagram
                      $linked_in_4 = $social_media_and_contacts_options['linked_in_4']; // Linked In
                      $twitter_x_5 = $social_media_and_contacts_options['twitter_x_5']; // Twitter X
                      $tiktok_6 = $social_media_and_contacts_options['tiktok_6']; // Tiktok
                      $whatsapp = $social_media_and_contacts_options['whatsapp']; // whatsapp
                      $address = $social_media_and_contacts_options['address']; // Phone
                  ?>

               <div class="col-md-4">

                  <i class="fa-brands fa-square-whatsapp" style="color: green; font-size: 35px;"></i>
                  <p class="fnt-bold remMar"><?php pll_e('Via Whatsapp'); ?></p>
                  <p class="fnt-thin remMar force-ltr"><?php print $whatsapp;?></p>
                  
                  <div class="spacer-20"></div>
               </div>

               <div class="col-md-4">

                  <i class="fa-solid fa-envelope" style="color: rgba(174, 153, 98, 1); font-size: 35px;"></i>
                  <p class="fnt-bold remMar"><?php pll_e('Via E-mail'); ?></p>
                  <p class="fnt-thin remMar"><?php print $email_0;?></p>
                  
                  <div class="spacer-20"></div>
               </div>

               <div class="col-md-4">
                  <i class="fa-solid fa-location-dot" style="color: rgba(174, 153, 98, 1); font-size: 35px;"></i>
                  <p class="fnt-bold remMar"><?php pll_e('Address'); ?></p>
                  <p class="fnt-thin remMar"><?php pll_e('Address Text'); ?></p>
                  
                  <div class="spacer-20"></div>
               </div>

            </div>


           

            

         </div>

      </div>

     <div class="spacer-80"></div>
  </section>


 <?php get_footer(); ?>
