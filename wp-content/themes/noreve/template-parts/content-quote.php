<?php
/**
 * Template Name: Quotation Page
 * Template part for displaying Quotation Page
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

   // if (session_id()) {
   //     session_unset();
   //     session_destroy();
   // }

   //unset($_SESSION['product_ids']);

   /*if (isset($_SESSION['product_ids'])){
       print_r($_SESSION['product_ids']);
   }*/

   if(isset($_GET['submit']) && $_GET['submit'] == 1){
    session_unset();
    session_destroy();
   }

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
                   <p><?php pll_e('Fillout'); ?></p>

               <?php } ?>


            </div>         

  		    <div class="spacer-40"></div>

             <div class="section-body">

               <?php  if(isset($_GET['submit']) && $_GET['submit'] == 1){ ?>


                  <div class="alert alert-success" role="alert">
                     Your request has been successfully received. Our team will contact you within 24 hours.
                  </div>

                  <div class="spacer-20"></div>

                  <div class="row">
                     <div class="col-md-4">
                        <a href="<?php print get_pll_links(149); ?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-black">
                           Back to Home
                           <img src="<?php print get_template_directory_uri(); ?>/img/icons/discover-arrow-<?php print $lang; ?>.svg" class="btn-gernetic-arrow" alt="icon">
                        </a>
                     </div>
                     <div class="col-md-8"></div>
                  </div>                  


               <?php }else{ ?>

                  <?php if(!isset($_SESSION['product_ids']) || empty($_SESSION['product_ids'])){ ?>

                     <div class="alert alert-warning" role="alert">
                       No item on the cart
                     </div>                  

                  <?php }else{ ?>

                     <?php $getQuotesitems = getQuotes($_SESSION['product_ids']); ?>

                     <?php //print_r($getQuotesitems);?>

                     <h4><?php pll_e('Required Products'); ?></h4>
                     <div class="spacer-20"></div>

                     <div class="b2b-table">
                        <div class="table-responsive">
                           <table class="table">
                              <thead>
                                 <tr>
                                    <th></th>
                                    <th><?php pll_e('Product Name'); ?></th>
                                    <th><?php pll_e('Package Size'); ?></th>
                                    <th><?php pll_e('Product Number'); ?></th>
                                    <th><?php pll_e('Quantity'); ?></th>
                                    <th></th>
                                 </tr>
                              </thead>

                              <tbody>

                                 <?php 
                                    $i = 1;
                                    foreach($getQuotesitems as $rowItem){ 

                                       //print_r($rowItem);
                                 ?>
                                 <tr class="item" data-product="<?php print $rowItem['id']; ?>">
                                    <td class="align-middle b2b-num"><?php print $i++; ?></td>
                                    <td class="align-middle b2b-det-first item-name"><?php print $rowItem['title']; ?></td>
                                    <td class="align-middle item-size"><?php print $rowItem['size']; ?></td>
                                    <td class="align-middle item-number">PROD-<?php print $rowItem['id']; ?></td>
                                    <td class="align-middle item-qty">
                                       <input type="number" class="form-control qty" value="10">
                                    </td>
                                    <td class="align-middle b2b-det-last">
                                       <center>
                                          <i class="fa-solid fa-trash-can delItem" data-id="<?php print $rowItem['id']; ?>"></i>
                                       </center>
                                    </td>
                                 </tr>

                                 <?php } ?>
                                                          
                              </tbody>

                           </table>
                        </div>

                     </div>  

                     <div class="spacer-40"></div>

                     <div class="consultation-form">
                        <?php

                              if($lang == "en"){
                                 print do_shortcode('[contact-form-7 id="3e9adaa" title="Quotation - EN"]');
                              }

                              if($lang == "ar"){
                                 print do_shortcode('[contact-form-7 id="25688fb" title="Quotation - AR"]');
                              }

                              if($lang == "fr"){
                                 print do_shortcode('[contact-form-7 id="24d7193" title="Quotation - FR"]');
                              }

                           ?>
                     </div>           

                  <?php }?>                  

               <?php } ?>

                <?php //the_content();?>

            </div>

  		</div>

     <div class="spacer-80"></div>
  </section>


 <?php get_footer(); ?>

<?php if(isset($_SESSION['product_ids']) && !empty($_SESSION['product_ids'])){ ?>

   <script>

      jQuery(document).ready(function($){

         document.addEventListener('wpcf7beforesubmit', function(event) {

            let items = [];

            jQuery(".b2b-table .item").each(function(){
    
               var itemId = jQuery(this).attr("data-product");
               var itemName = jQuery(this).find(".item-name").text();
               var itemQty = jQuery(this).find(".qty").val();
    
               var itemsValue = "\nProduct Name: "+itemName+"\nProduct ID: PROD-"+itemId+"\nQuantity: "+itemQty+"\n";
    
                items.push(itemsValue);
    
            });
    
            console.log(items);
            let text = items.map(item => '' + item).join('\n');
            jQuery('textarea#orders').append(text);
            jQuery(".wpcf7-response-output").hide();

         }, false);

         jQuery(document).on('wpcf7mailsent', function() {
             window.location.href = "<?php print get_pll_links(574);?>?submit=1";
         }); 

      });
   </script>


<?php }?>
