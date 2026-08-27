<?php
/**
 * Template Name: B2B Product Page
 * Template part for displaying B2B Product Page
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

    $getB2bProducts = getProducts("b2b");

?>

   <!--products-->
   <section id="prodcts" class="bg-dirty-white" data-scroll-section>
      <div class="spacer-80"></div>

      <div class="container">

         <div class="section-header">

            <?php woocommerce_breadcrumb();?>

            <div class="row align-items-center">

               <div class="col-md-3">

                  <p class="remMar fnt-light fnt-18"><?php print $getB2bProducts['count'];?> Products</p>
                  <div class="spacer-40"></div>

               </div>

               <div class="col-md-6"></div>

               <div class="col-md-3">

                 <form action="" class="">

                  <div class="row align-items-center">

                    <div class="col-4 text-end">

                      <!-- <p class="remMar fnt-18 fnt-light-gray">Sort by</p> -->

                    </div>

                    <div class="col-8">

                      <!-- <select name="sort" id="sort-products" class="form-control sort-control">
                        <option value="" selected>Featured</option>
                        <option value="">Price: Low to High</option>
                        <option value="">Price: High to Low</option>
                        <option value="">Best Seller</option>
                      </select> -->

                    </div>
                    
                  </div>
                   
                 </form>


                  <div class="spacer-40"></div>
               </div>

            </div>
            
         </div>

         
         <div class="section-body">

            <div class="row align-items-start">

               <div class="col-md-3">

                  <div class="product-filter">

                     <div class="box-title">

                        <div class="row align-items-center">
                          <div class="col-9">
                            <p class="fnt-black fnt-18 fnt-Semibold text-uppercase remMar"><?php pll_e('Filter'); ?></p>
                          </div>
                          <div class="col-3 text-end">
                            <a href=""><i class="fa-solid fa-arrow-rotate-left"></i></a>
                          </div>
                        </div>
                        
                     </div>

                     <div class="spacer-20"></div>

                     <div class="product-categories b2b-categories">

                        <ul class="list-group">
                             <li class="list-group-item parent-cat">
                                 <a href="#" class="list-link d-flex align-items-center active" data-cat-id="">
                                    <i class="fa-solid fa-circle fa-mar-10"></i> 
                                    <p><?php pll_e('All Products'); ?></p>
                                 </a>
                             </li>                           
                        </ul>

                        <ul class="list-group">

                           <?php $getTerms1 = getProductCatsByParent(115,117,119);?>

                             <li class="list-group-item parent-cat">
                                 <a href="#" class="list-link d-flex align-items-center" data-cat-id="<?php print $getTerms1['parent_id'];?>">
                                    <i class="fa-solid fa-circle fa-mar-10"></i> 
                                    <p><?php print $getTerms1['parent_name'];?></p>
                                 </a>
                             </li>

                            <?php 
                                 $termCount = 0;
                                 $itemCount = 0;
                                 foreach($getTerms1['child'] as $rowGetTerm1){

                                    $hidden = "";
                                    $active = "";
                                    // if($termCount++ >= 6){
                                    //    $hidden = "hidden-category";
                                    // }
                                    // if($itemCount++ == 0){
                                    //    $active = "active";
                                    // }
                              ?>

                                <li class="list-group-item child-cat">
                                    <a href="#" class="list-link d-flex align-items-center" data-cat-id="<?php print $rowGetTerm1['term_id'];?>">
                                       <i class="fa-solid fa-circle fa-mar-10"></i> 
                                       <p><?php print $rowGetTerm1['term_name'];?></p>
                                    </a>
                                </li>

                           <?php } ?>
                        </ul>

                        <ul class="list-group">

                           <?php $getTerms2 = getProductCatsByParent(154,156,158);?>

                             <li class="list-group-item parent-cat">
                                 <a href="#" class="list-link d-flex align-items-center" data-cat-id="<?php print $getTerms2['parent_id'];?>">
                                    <i class="fa-solid fa-circle fa-mar-10"></i> 
                                    <p><?php print $getTerms2['parent_name'];?></p>
                                 </a>
                             </li>

                            <?php 
                                 $termCount = 0;
                                 $itemCount = 0;
                                 foreach($getTerms2['child'] as $rowGetTerm2){

                                    $hidden = "";
                                    $active = "";
                                    // if($termCount++ >= 6){
                                    //    $hidden = "hidden-category";
                                    // }
                                    // if($itemCount++ == 0){
                                    //    $active = "active";
                                    // }
                              ?>

                                <li class="list-group-item child-cat">
                                    <a href="#" class="list-link d-flex align-items-center" data-cat-id="<?php print $rowGetTerm2['term_id'];?>">
                                       <i class="fa-solid fa-circle fa-mar-10"></i> 
                                       <p><?php print $rowGetTerm2['term_name'];?></p>
                                    </a>
                                </li>

                           <?php } ?>
                        </ul>                        
                     </div>

                     <!-- <div class="spacer-20"></div>
                     <a href="#" class="btn-more fnt-regular text-start box-title fnt-18">
                        <i class="fa-solid fa-angle-down fa-mar-5"></i><b>More</b>
                     </a> -->
                     <div class="spacer-10"></div>
                  
                  </div>

               </div>

               <div class="col-md-9 posRel">

                  <div id="product-lists" class="row">

                     <?php foreach($getB2bProducts['products'] as $rowitem){?>

                        <div class="col-md-4">

                          <div class="item" data-catid="[<?php print $rowitem['categories']; ?>]">

                             <div class="product-box">

                                <a href="<?php print $rowitem['link']; ?>">
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
                                               <img src="<?php print $rowitem['image']; ?>" class="img-fluid" alt="product">                      
                                            </center>
                                         </div>
                                         <div class="col-md-3"></div>
                                      </div>
                                   </div>

                                   <div class="spacer-20"></div>

                                   <div class="product-details">

                                      <div class="product-padding">

                                         <h4 class="product-title"><?php print $rowitem['title']; ?></h4>
                                         
                                         <div class="row align-items-start">

                                            <div class="col-6">
                                               <!-- <h4 class="product-title"><?php print $rowitem['title']; ?></h4> -->
                                            </div>

                                            <div class="col-6 text-end">
                                               <!-- <p class="product-weigth"><?php print $rowitem['size']; ?></p> -->
                                            </div>

                                         </div>

                                      </div>
                                      
                                   </div>
                                </a>

                     
                                 <a href="#" 
                                    class="addToQuote" 
                                    data-product-id="<?php print $rowitem['id'];?>"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    data-bs-title="<?php pll_e('Added to Quotation'); ?>"
                                 >

                                    <?php pll_e('Add To Quote'); ?>

                                 </a>


                             </div>

                          </div> 

                          <div class="spacer-20"></div> 

                        </div>


                      <?php } ?>

                  </div>

                  <div class="spacer-20"></div>               
                           
               </div>
               
            </div>           

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

   <?php 
      //print_r($_SESSION['product_ids']);
      //unset($_SESSION['product_ids']);
   ?>


 <?php get_footer(); ?>
