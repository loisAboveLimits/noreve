<?php
/**
 * Template Name: Blog Page
 * Template part for displaying Blog Page
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
               <?php the_title();?>
            </h1>
       
         </div>

         <div class="spacer-40"></div>

         <div class="section-body">

         <?php $getOthers = getOtherBlogs(0); ?>

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


 <?php get_footer(); ?>
