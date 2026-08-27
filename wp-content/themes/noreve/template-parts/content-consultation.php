<?php
/**
 * Template Name: Consultation Page
 * Template part for displaying Consultation Page
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
   
   <?php $getcontent = getSections(450); ?>

  <section id="consultation" class="form-height" data-scroll-section>
  	<div class="spacer-80"></div>

  		<div class="container">

  			<div class="section-header">

            <?php
               if ( function_exists( 'woocommerce_breadcrumb' ) ) {
                   woocommerce_breadcrumb();
               }
            ?>


            <h1 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">  
               <?php print $getcontent['title_top'];?>
            </h1>
            <p class="section-subtitle"><?php print $getcontent['title_middle'];?></p>
         </div>

         <div class="spacer-40"></div>

         <div class="section-body">

            <div class="consultation-form">

               <?php if($lang == "en"){ 
                  print do_shortcode('[contact-form-7 id="5d3f4f7" title="Consultation Form EN"]'); 
               }?>

               <?php if($lang == "ar"){ 
                  print do_shortcode('[contact-form-7 id="67e9052" title="Consultation Form AR"]'); 
               }?>               

               <?php if($lang == "fr"){ 
                  print do_shortcode('[contact-form-7 id="227342e" title="Consultation Form FR"]'); 
               }?> 
              <!--  <form action="">

                  <div class="group1">

                  [text* fullname class:form-control class:gernatic-form placeholder "Full Name"]

                  [email* email class:form-control class:gernatic-form placeholder "Email"]

                  [tel phone class:form-control class:gernatic-form placeholder "Phone Number"]

                  [number age class:form-control class:gernatic-form placeholder "Age"]
                  </div>

                  <div class="spacer-40"></div>

                  <div class="group2">
                  <h4 class="fnt-25">Skin Information</h4>

                  <div class="spacer-20"></div>

                  <div class="gernatic-input-group d-flex align-items-center">

                  [text* skinType id:skinType class:form-control class:gernatic-form placeholder "Skin Type"]

                  <span class="btn-selection d-flex justify-content-end">
                  <button class="btn btn-sm btn-consultation">Dry</button>
                  <button class="btn btn-sm btn-consultation">Fatty</button>
                  <button class="btn btn-sm btn-consultation">Mixed</button>
                  <button class="btn btn-sm btn-consultation">Sensitive</button>
                  <button class="btn btn-sm btn-consultation">Not sure</button>
                  </span>
                  </div>

                  <div class="spacer-10"></div>

                  <div class="gernatic-input-group d-flex align-items-center">

                  [text* skinCondition id:skinCondition class:form-control class:gernatic-form placeholder "Skin Condition"]

                  <span class="btn-selection d-flex justify-content-end">
                  <button class="btn btn-sm btn-consultation">Fatigue</button>
                  <button class="btn btn-sm btn-consultation">Wrinkles</button>
                  <button class="btn btn-sm btn-consultation">Dryness</button>
                  <button class="btn btn-sm btn-consultation">Pigmentation</button>
                  <button class="btn btn-sm btn-consultation">Acne</button>
                  </span>
                  </div>

                  <div class="spacer-10"></div>

                  <div class="gernatic-input-group d-flex align-items-center">

                  [number* duration id:duration class:form-control class:gernatic-form placeholder "Duration"]

                  <span class="btn-selection d-flex justify-content-end">

                  [select duration_unit class:form-control class:gernatic-form "Days" "Weeks" "Months" "Years"]

                  </span>
                  </div>
                  </div>

                  <div class="spacer-40"></div>

                  <div class="group3">

                  <h4 class="fnt-25">Additional Details</h4>

                  <div class="spacer-20"></div>

                  [textarea description class:form-control class:gernatic-form x10 placeholder "Description"]

                  <div id="upload" class="gernatic-input-group d-flex align-items-center">
                  <input type="text" class="form-control gernatic-form" placeholder="Upload a photo of your skin (optional)" disabled>
                  <span class="btn-selection d-flex justify-content-end">
                  <i class="fa-sharp fa-solid fa-upload"></i>
                  </span>

                  [file skin_photo id:formFile class:hideThis limit:10mb filetypes:jpg|jpeg|png|webp]

                  </div>  

                  <div class="spacer-10"></div>

                  <div class="gernatic-input-group d-flex align-items-center">
                  <input id="otherProducts" type="text" class="form-control gernatic-form" placeholder="Are you currently using any products?">

                  [text* otherProducts id:otherProducts class:form-control class:gernatic-form placeholder "Are you currently using any products?"]

                  <span class="btn-selection d-flex justify-content-end">
                  <button class="btn btn-sm btn-consultation">Yes</button>
                  <button class="btn btn-sm btn-consultation">No</button>
                  </span>
                  </div>                                       

                  <div class="spacer-10"></div>

                  <div class="gernatic-input-group d-flex align-items-center">

                  [date consultation_date id:date class:form-control class:gernatic-form onfocus:"this.type='date'" onblur:"if(!this.value)this.type='text'" placeholder="Consultation Date"]

                  <span class="btn-selection d-flex justify-content-end">
                  <i class="fa-sharp fa-solid fa-calendar"></i>
                  </span>

                  </div>                      

                  <div class="spacer-40"></div>

                  [submit class:btn class:btn-lg class:btn-gernetic-black-square "Submit a Consultation Request"]

                  </div>

               </form> --> 

            </div>

            

         </div>

  		</div>

     <div class="spacer-80"></div>
  </section>


 <?php get_footer(); ?>
