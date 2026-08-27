<?php
/**
 * Template Name: Sample Page
 * Template part for displaying Sample/Test Page
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

  <section class="" data-scroll-section>
  	<div class="spacer-80"></div>

  		<div class="container">

          <?php
             if ( function_exists( 'woocommerce_breadcrumb' ) ) {
                 woocommerce_breadcrumb();
             }
          ?>        

  			<h1><?php the_title();?></h1>

  			<?php the_content();?>

        <?php 

          //$getTerms = getProductCatsByParent(115,117,119); 

          //print_r($getTerms);

          $gateways = WC()->payment_gateways()->get_available_payment_gateways();

          //print_r(array_keys($gateways));

        ?>

         <div class="spacer-40"></div>

         <div class="seach-form">
           <?php print do_shortcode('[searchwp_form id=1]');?>
         </div>

          <!-- <div id="content-accordion">
             <div class="accordion" id="gerenetic-accordion">
               <div class="accordion-item">
                 <h2 class="accordion-header">
                   <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accord-1">
                     Product Description
                   </button>
                 </h2>
                 <div id="accord-1" class="accordion-collapse collapse show">
                   <div class="accordion-body">

                     <p>Its ultra-light formula leaves no residue, giving you a refreshing feeling and a silky-smooth texture. Alcohol-free, it keeps skin moisturized and provides long-lasting comfort all day long.</p>

                   </div>
                 </div>
               </div>

               <div class="accordion-item">
                 <h2 class="accordion-header">
                   <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accord-2">
                     How to use
                   </button>
                 </h2>
                 <div id="accord-2" class="accordion-collapse collapse">
                   <div class="accordion-body">

                     <p>After shaving, massage with light movements.</p>

                   </div>
                 </div>
               </div>

               <div class="accordion-item">
                 <h2 class="accordion-header">
                   <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accord-3">
                     The problem it addresses
                   </button>
                 </h2>
                 <div id="accord-3" class="accordion-collapse collapse">
                   <div class="accordion-body">

                     <ul>
                      <li>Dryness</li>
                      <li>Illness of balance</li>
                      <li>Dullness of the skin</li>
                     </ul>

                   </div>
                 </div>
               </div>

               <div class="accordion-item">
                 <h2 class="accordion-header">
                   <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accord-4">
                     Components
                   </button>
                 </h2>
                 <div id="accord-4" class="accordion-collapse collapse">
                   <div class="accordion-body">

                     <ul>
                      <li>Hyaluronic acid</li>
                      <li>Vitamin C</li>
                     </ul>

                   </div>
                 </div>
               </div>

               <div class="accordion-item">
                 <h2 class="accordion-header">
                   <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accord-5">
                     Product details
                   </button>
                 </h2>
                 <div id="accord-5" class="accordion-collapse collapse">
                   <div class="accordion-body">

                      <div class="table-responsive">
                      <table class="table-secondary">
                         <tbody>
                             <tr>
                               <th>Product number</th>
                               <td>FNVGBAU050</td>
                             </tr>
                             <tr>
                               <th>Country of origin</th>
                               <td>France</td>
                             </tr>
                             <tr>
                               <th>Sex</th>
                               <td>Male</td>
                             </tr>
                             <tr>
                               <th>Size</th>
                               <td>50ml</td>
                             </tr>                                                                   
                         </tbody>
                      </table>

                      </div>
                   </div>
                 </div>
               </div>

               <div class="accordion-item">
                 <h2 class="accordion-header">
                   <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accord-6">
                     Returns and Exchanges
                   </button>
                 </h2>
                 <div id="accord-6" class="accordion-collapse collapse">
                   <div class="accordion-body">

                     <p>Learn more about our return and exchange policy <a href=""><b>here</b></a></p>

                   </div>
                 </div>
               </div>                  


             </div>                  
          </div> -->            

  		</div>

     <div class="spacer-80"></div>
  </section>


 <?php get_footer(); ?>


