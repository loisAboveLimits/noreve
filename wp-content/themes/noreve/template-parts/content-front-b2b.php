<?php
/**
 * Template Name: B2B Home Page
 * Template part for displaying B2b Home Page
 *
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Gernetic
 */


   $lang = pll_current_language();

   $postId = get_the_ID();

   $iconsLang = "en";
   if($lang == "ar"){$iconsLang = "ar";}
   if($lang == "fr"){$iconsLang = "en";}

   get_header();


    
?>
   
   <?php $getBanner = getBanner($postId)?>

   <!--banner-->
   <section id="banner" class="vh-100 posRel" data-scroll-section>

      <div class="container-fluid remPad vh-100 posRel">

         <div class="banner-content">

            <div id="banner-slider" class="owl-carousel">

               <?php foreach($getBanner['gallery'] as $rowItem){ ?>

                  <?php if($rowItem['type'] == "video"){ ?>

                     <div class="item">
                        <div class="ratio ratio-16x9">
                          <video autoplay="" loop="" muted="" playsinline="" style="user-select: none;">
                            <source src="<?php print $rowItem['link']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                          </video>              
                        </div>
                     </div>

                  <?php }else{ ?>

                     <div class="item">
                        <img src="<?php print $rowItem['link']; ?>" class="img-fluid w-100" alt="banner" >
                     </div>


                  <?php } ?>

               <?php } ?>
            </div>            

         </div>


         <div class="banner-caption position-absolute start-50 translate-middle">

            <div class="row">
               <div class="col-md-2"></div>
               <div class="col-md-8">
                  <center>
                     <a href="<?php print get_pll_links(161);?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-black">
                        <?php pll_e('Discover Products'); ?>
                        <img src="<?php print get_template_directory_uri(); ?>/img/icons/discover-arrow-<?php print $iconsLang;?>.svg" class="btn-gernetic-arrow" alt="icon">
                     </a>
                  </center>
               </div>
               <div class="col-md-2"></div>
            </div>
            
         </div>   

         <div class="banner-dot position-absolute banner-dot-b2b">

            <div class="row ">
               <div class="col-md-2"></div>
               <div class="col-md-8">

                  <div class="bird-dots">

                     <?php 
                        $birdDots = countBirdDots($getBanner['gallery']);
                        print $birdDots;
                     ?>
                
                  </div>

               </div>
               <div class="col-md-2"></div>
            </div>
            
         </div> 

         <div id="partner-slider" class="owl-carousel">

            <?php for($i = 0; $i < 9; $i++){?>
               <div class="item">
                  <center>
                     <img src="<?php print get_template_directory_uri(); ?>/img/partners/partner1.png" class="img-fluid" alt="partners">
                  </center>
               </div>
            <?php }?>
            
         </div>  

      </div>

   </section>

   <?php $getHomeAbout = getSections(797); ?>

   <!--about-->
   <section id="about" class="bg-full" style="background-image: url('<?php print $getHomeAbout['background'];?>');" data-scroll-section>
      <div class="spacer-100"></div>

      <div class="container">

         <div class="section-header">

            <div class="row">
               
               <div class="col-md-6">
                  
                  <h2 class="section-title fnt-white preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">  <?php print $getHomeAbout['title_top'];?>
                  </h2>
                  <div class="spacer-40"></div>

                  <div class="about-content preAnimate" data-scroll data-scroll-repeat data-scroll-delay="60" data-scroll-class="animateThis">
                     <?php print $getHomeAbout['content'];?>
                     <div class="spacer-40"></div>
                  </div>

                  <div class="about-content preAnimate" data-scroll data-scroll-repeat data-scroll-delay="90" data-scroll-class="animateThis">
                     <a href="<?php print $getHomeAbout['button_link'];?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-readmore">
                        <?php print $getHomeAbout['button_text'];?>
                        <img src="<?php print get_template_directory_uri(); ?>/img/icons/readmore-arrow-<?php print $iconsLang;?>.png" class="btn-gernetic-readmore-arrow" alt="icon">
                     </a> 
                  </div>              

               </div>

               <div class="col-md-6"></div>
            
            </div>           

         </div>

         <div class="spacer-80"></div>

         <div class="section-body">

            <div class="preAnimate" data-scroll data-scroll-repeat data-scroll-delay="120" data-scroll-class="animateThis">

               <?php print $getHomeAbout['attachment']['editor'];?>

          
            </div>

         </div>
      </div>

      <div class="spacer-80"></div>
   
   </section>

   <?php $getHomeWhy = getSections(804); ?>

   <!--why gernetic-->
   <section id="partners" class="bg-full" style="background-image: url('<?php print $getHomeWhy['background'];?>');" data-scroll-section>
      <div class="spacer-100"></div>

      <div class="container">

         <div class="section-header">

            <div class="row align-items-end">

               <div class="col-md-9">

                  <h2 class="section-title fnt-black preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">  <?php print $getHomeWhy['title_top'];?>
                  </h2>

                  <div class="spacer-40"></div>
               </div>

               <div class="col-md-3"></div>

            </div>
            
         </div>

         <div class="spacer-20"></div>  

         <div class="section-body">

            <?php print $getHomeWhy['content'];?>

            <div class="spacer-40"></div>

            <h2 class="section-title fnt-black preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
               <?php print $getHomeWhy['title_middle'];?>
            </h2>
            <div class="spacer-40"></div>

               <?php print $getHomeWhy['attachment']['editor'];?>

            <div class="spacer-40"></div>

               <a href="<?php print $getHomeWhy['button_link'];?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
                  <?php print $getHomeWhy['button_text'];?>
                  <img src="<?php print get_template_directory_uri(); ?>/img/icons/discover-arrow-<?php print $iconsLang;?>.svg" class="btn-gernetic-arrow" alt="icon">
               </a>


         </div>       

      </div>

      <div class="spacer-100"></div>
   </section>

   <?php $getHomeExpert = getSections(809); ?>

   <!--experts-->
   <section id="experts" data-scroll-section>
      <div class="spacer-100"></div>

      <div class="container">
         
         <div class="section-header">

               <div class="row align-items-end">

                  <div class="col-md-9">

                     <h2 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
                        <?php print $getHomeExpert['title_top'];?>
                     </h2>

                     <?php print $getHomeExpert['content'];?>

                     <div class="spacer-40"></div>
                  </div>

                  <div class="col-md-3"></div>

               </div>
               
            </div>

            <div class="spacer-40"></div>

            <div class="section-body">

               <div class="expert-column posRel">

                  <div class="row remPad">

                     <div class="col-6 remPad">

                        <div class="posRel">
                           <img src="<?php print $getHomeExpert['attachment']['gallery'][0];?>" class="img-fluid w-100" alt="expert">
                           <p class="caption fnt-white position-absolute top-50 start-50 translate-middle fnt-regular preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
                              <?php print $getHomeExpert['title_middle'];?>
                           </p>
                        </div>
                        
                     </div>


                     <div class="col-6 remPad">

                        <div class="posRel">
                           <img src="<?php print $getHomeExpert['attachment']['gallery'][1];?>" class="img-fluid w-100" alt="expert">
                           <p class="caption fnt-white position-absolute top-50 start-50 translate-middle fnt-regular preAnimate" data-scroll data-scroll-repeat data-scroll-delay="60" data-scroll-class="animateThis">
                              <?php print $getHomeExpert['title_bottom'];?>
                           </p>
                        </div>

                     </div>
                     
                  </div>
               </div>

            </div>


         </div>


      <div class="spacer-100"></div>
   </section>

   <!--products-->
   <section id="products" class="bg-dirty-white" data-scroll-section>
      <div class="spacer-100"></div>

      <div class="container">

         <div class="section-header">

            <div class="row align-items-end">

               <div class="col-md-9">

                  <h2 class="section-title fnt-gold"><?php pll_e('Products'); ?></h2>

                  <div class="spacer-40"></div>
               </div>

               <div class="col-md-3 text-end">

                  <a class="fnt-20 fnt-black text-decoration-underline fnt-Semibold" href="<?php print get_pll_links(161); ?>"><?php pll_e('All'); ?></a>

                  <div class="spacer-40"></div>
               </div>

            </div>
            
         </div>

         <div class="spacer-20"></div>
         
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

                  <?php $getB2bProducts = getProductsHome("b2b"); ?>

                  <div id="product-list" class="owl-carousel">

                     <?php foreach($getB2bProducts['products'] as $rowitem){?>

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

                         <?php } ?>

                  </div>

                  <div class="spacer-20"></div>

                  <!-- <div class="product-dot position-absolute start-50 translate-middle">

                     <div class="row ">
                        <div class="col-md-2"></div>
                        <div class="col-md-8">

                           <div class="bird-dots d-flex align-items-center justify-content-center">
                              <a class="btn-dot active" data-slide="0"></a>
                              <a class="btn-dot" data-slide="1"></a>
                              <a class="btn-dot" data-slide="2"></a>
                              <a class="btn-dot" data-slide="3"></a>
                              <a class="btn-dot" data-slide="4"></a>
                              <a class="btn-dot" data-slide="5"></a>
                              <a class="btn-dot" data-slide="6"></a>
                              <a class="btn-dot" data-slide="7"></a>
                              <a class="btn-dot" data-slide="8"></a>   
                           </div>

                        </div>
                        <div class="col-md-2"></div>
                     </div>

                  </div>  -->               
                           
               </div>
               
            </div>            

         </div>


      </div>

      <div class="spacer-100"></div>
   </section>

   <?php $getReviews = getSections(414); ?>

   <!--review-->
   <section id="review" class="bg-dirty-white" data-scroll-section>
      <div class="spacer-100"></div>

      <div class="container posRel">

         <div class="section-header">

            <div class="row">
               
               <div class="col-md-6">
                  
                  <h2 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">   <?php print $getReviews['title_top'];?>
                  </h2>

                  <p class="section-subtitle fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="60" data-scroll-class="animateThis"><?php print $getReviews['title_middle'];?></p>             

               </div>

               <div class="col-md-6"></div>
            
            </div>           

         </div>    
         
         <div class="spacer-40"></div> 

         <div class="section-body">

            <?php $getTestimonies = getGlobalTestimony("b2c");?>

            <div id="review-list" class="owl-carousel">

               <?php foreach($getTestimonies as $rowtestimony){ ?>

                   <div class="item posRel">
                     <div class="review-bg">
                        <img src="<?php print get_template_directory_uri(); ?>/img/icons/review-frame-<?php print $iconsLang; ?>.png" class="img-fluid" alt="bg">
                     </div>

                     <div class="review-content">
                        <p><?php print $rowtestimony['content']; ?></p>
                     </div>

                     <div class="review-user d-flex justify-content-start">
                        <img src="<?php print $rowtestimony['featImg']; ?>" class="img-fluid" alt="user">
                        <p><?php print $rowtestimony['title']; ?></p>
                     </div>

                  </div>                  

               <?php } ?>

            </div>

            <div class="spacer-20"></div>

            <!-- <div class="review-dot position-absolute start-50 translate-middle">

               <div class="row ">
                  <div class="col-md-2"></div>
                  <div class="col-md-8">

                     <div class="bird-dots d-flex align-items-center justify-content-center">
                        <a class="btn-dot active" data-slide="0"></a>
                        <a class="btn-dot" data-slide="1"></a>
                        <a class="btn-dot" data-slide="2"></a>
                        <a class="btn-dot" data-slide="3"></a>
                        <a class="btn-dot" data-slide="4"></a>
                     </div>

                  </div>
                  <div class="col-md-2"></div>
               </div>

            </div> -->            
            

         </div>    


         
      </div>

      <div class="spacer-40 desktop-view"></div>
      <div class="spacer-100"></div>
    </section>  

 <?php get_footer(); ?>