<?php
/**
 * Template Name: Product Page
 * Template part for displaying Product Page
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

    $getProducts = getProducts("b2c");

?>

   <!--products-->
   <section id="prodcts" class="bg-dirty-white" data-scroll-section>
      <div class="spacer-80"></div>

      <div class="container">

         <div class="section-header">

            <?php woocommerce_breadcrumb();?>

            <div class="row align-items-center">

               <div class="col-md-3">

                  <p class="remMar fnt-light fnt-18"><?php print  $getProducts['count'];?> <?php pll_e('Products'); ?></p>
                  <div class="spacer-40"></div>

               </div>

               <div class="col-md-6"></div>

               <div class="col-md-3">

                 <!-- <form action="" class="">

                  <div class="row align-items-center">

                    <div class="col-4 text-end">

                      <p class="remMar fnt-18 fnt-light-gray">Sort by</p>

                    </div>

                    <div class="col-8">

                      <select name="sort" id="sort-products" class="form-control sort-control">
                        <option value="" selected>Featured</option>
                        <option value="">Price: Low to High</option>
                        <option value="">Price: High to Low</option>
                        <option value="">Best Seller</option>
                      </select>

                    </div>
                    
                  </div>
                   
                 </form> -->


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


                     <div class="product-categories b2c-categories">

                        <ul class="list-group">

                          <!-- <li class="list-group-item parent-cat">
                              <a href="#" class="list-link d-flex align-items-center active" data-cat-id="0">
                                 <i class="fa-solid fa-circle fa-mar-10"></i> 
                                 <p>All Products</p>
                              </a>
                          </li>  -->                            

                           <?php $getTerms = getProductCatsByParent(103,105,107);?>

                            <?php 
                                 $termCount = 0;
                                 $itemCount = 0;
                                 foreach($getTerms['child'] as $rowGetTerm){ 

                                    $hidden = "";
                                    $active = "";
                                    if($termCount++ >= 6){
                                       $hidden = "hidden-category";
                                    }
                                    if($itemCount++ == 0){
                                       $active = "active";
                                    }
                              ?>

                                <li class="list-group-item <?php //print $hidden; ?> <?php print $itemCount++; ?>">
                                    <a href="#" class="list-link  d-flex align-items-center <?php //print $active;?>" data-cat-id="<?php print $rowGetTerm['term_id'];?>">
                                       <i class="fa-solid fa-circle fa-mar-10"></i> 
                                       <p><?php print $rowGetTerm['term_name'];?></p>
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


                  <div class="spacer-40"></div>
               </div>

               <div class="col-md-9 posRel">


                  <?php //print_r($getProducts);?>

                  <div id="product-lists" class="row">

                     <?php foreach($getProducts['products'] as $rowProduct){ ?>

                        <div class="col-6 col-md-4 item" data-catids="[<?php print $rowProduct['categories'];?>]">
 
                              <div class="card h-100 w-100 product-box">

                                 <a href="<?php print $rowProduct['link'];?>" class="h-100 posRel">

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
                                         
                                         <div class="col-md-12">
                                            <center>
                                               <img src="<?php print $rowProduct['image'];?>" class="img-fluid" alt="product">                      
                                            </center>
                                         </div>
                                        
                                      </div>

                                    </div>

                                    <div class="spacer-20"></div>    

                                      <div class="product-details">

                                         <div class="product-padding">

                                            <h4 class="product-title"><?php print $rowProduct['title'];?></h4>
                                            <div class="spacer-20"></div>
                                            
                                            <div class="row align-items-end">

                                               <div class="col-12">

                                                <?php if(empty($rowProduct['details']['sale_price']) || $rowProduct['details']['sale_price'] == ""){?>
                                                  <p class="product-price">
                                                      <?php print $rowProduct['details']['price'];?>
                                                      <?php print $rowProduct['details']['currency'];?> 
                                                      
                                                   </p>

                                                <?php }else{ ?>

                                                  <p class="product-price">
                                                      <?php print $rowProduct['details']['sale_price'];?>
                                                      <?php print $rowProduct['details']['currency'];?> 
                                                      
                                                      <font class="product-price-sale">
                                                         <?php print $rowProduct['details']['price'];?>
                                                         <?php print $rowProduct['details']['currency'];?> 
                                                         
                                                      </font>
                                                   </p>

                                                <?php } ?>
                                               </div>
                                               <!-- 
                                               <div class="col-6 text-end">
                                                  <p class="product-weigth">50 ml</p> 
                                               </div>
                                               -->
                                            </div>
                                            
                                         </div>
                                         
                                      </div>

                                 </a>

                              </div>
 
                        </div>

                     <?php } ?>


                  </div>

                  <div id="selected-terms" data-terms=""></div>

                  <div class="spacer-20"></div>               
                           
               </div>
               
            </div>            

         </div>


      </div>

      <div class="spacer-80"></div>
   </section>


 <?php get_footer(); ?>

 <script>
//     jQuery(document).ready(function($){
// 
//       jQuery(".product-categories .list-link").each(function(){
// 
//          jQuery(this).on("click touch", function(){
// 
//             var getTermId = jQuery(this).data("cat-id");
// 
//             setTimeout(function () {
//                var getTermIds = getActiveData();
//                console.log(getTermIds);
// 
//                   $.ajax({
//                    url: "<?php print admin_url('admin-ajax.php'); ?>",
//                    type: 'POST',
//                    data: {
//                      action: 'product_filter',
//                      categories: JSON.stringify(getTermIds)
//                    },
//                    success: function (response) {
//                      //console.log(response);
// 
//                      jQuery("#product-lists").html("").fadeOut(100);
//                      jQuery("#product-lists").html(response).fadeIn(300);
// 
//                      
// 
//                    }
//                  });
// 
//             }, 600);
//            
//             
//             // console.log(getTermId);
//            
//          });
// 
//       });
// 
//     });


 </script>

 <script>

//    function getActiveData() {
// 
//        let dataArray = [];
// 
//          jQuery(".product-categories .active").each(function(){
// 
//              dataArray.push(jQuery(this).data("cat-id"));
//          });
// 
//        return dataArray;
//    }


</script>
