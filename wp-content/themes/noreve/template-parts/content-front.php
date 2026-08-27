<?php
/**
 * Template Name: Home Page
 * Template part for displaying Home Page
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

      <div class="container-fluid remPad posRel">

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
                     <h1 class="banner-title fnt-white fnt-thin"><?php print $getBanner['caption_1']; ?></h1>
                     <div class="spacer-20"></div>

                     <p class="banner-subtitle fnt-white fnt-thin"><?php print $getBanner['caption_2']; ?></p>
                     <div class="spacer-20"></div>

                     <a href="<?php print $getBanner['button_link']; ?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-black">
                        <?php print $getBanner['button_text']; ?>
                        <img src="<?php print get_template_directory_uri(); ?>/img/icons/discover-arrow-<?php print $iconsLang;?>.svg" class="btn-gernetic-arrow" alt="icon">
                     </a>
                  </center>
               </div>
               <div class="col-md-2"></div>
            </div>
            
         </div>   

         <!-- <div class="banner-dot position-absolute">

            <?php $countSlides = count($getBanner['gallery']); ?>

            <div class="row ">
               <div class="col-md-2"></div>
               <div class="col-md-8">

                  <div class="bird-dots d-flex align-items-center justify-content-center">
                     <?php 
                        for($iSlide = 0; $iSlide < $countSlides; $iSlide++){
                           $activeSlide = "";
                           if($iSlide == 0){
                               $activeSlide = "active";
                           }
                     ?>

                        <a class="btn-dot <?php print $activeSlide; ?>" data-slide="<?php print $iSlide; ?>"></a>

                     <?php }?>
                                     
                  </div>

               </div>
               <div class="col-md-2"></div>
            </div>
            


         </div> -->      

      </div>

   </section>

 
   <?php $getHomeAbout = getSections(345); ?>

   <!--about-->
   <section id="about" class="bg-full bg-change" 
      style="background-image: url('<?php print $getHomeAbout['background'];?>');" 
      data-bg-desktop="<?php print $getHomeAbout['background'];?>"
      data-bg-mobile="<?php print $getHomeAbout['background_mobile'];?>"
      data-scroll-section>
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
                     <a href="<?php print $getHomeAbout['button_link'];?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-black">
                        <?php print $getHomeAbout['button_text'];?>
                        <img src="<?php print get_template_directory_uri(); ?>/img/icons/discover-arrow-<?php print $iconsLang;?>.svg" class="btn-gernetic-arrow" alt="icon">
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

    <?php $getHomeProduct = getSections(388); ?>

   <!--products-->
   <section id="prodcts" class="bg-dirty-white vh-100" data-scroll-section>
      <div class="spacer-100"></div>

      <div class="container">

         <div class="section-header">

            <div class="row align-items-end">

               <div class="col-md-9">

                  <h2 class="section-title fnt-gold"><?php print $getHomeProduct['title_top'];?></h2>

                  <div class="spacer-40"></div>
               </div>

               <div class="col-md-3 text-end">

                  <a class="fnt-20 fnt-black text-decoration-underline fnt-Semibold" href="<?php print $getHomeProduct['button_link'];?>">
                     <?php print $getHomeProduct['button_text'];?>
                  </a>

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

               <div id="product-scroll-to" class="col-md-9 posRel">

                  <?php $getProducts = getProductsHome("b2c");?>

                  <div id="product-list" class="owl-carousel">

                     <?php foreach($getProducts['products'] as $rowProduct){ ?>

                        <div class="item" data-catid="<?php print $rowProduct['categories'];?>">

                           <div class="product-box">

                              <a href="<?php print $rowProduct['link'];?>">
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

                                          <div class="col-6">

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

                                          <div class="col-6 text-end">
                                             <!-- <p class="product-weigth">50 ml</p> -->
                                          </div>
                                       </div>
                                       
                                    </div>
                                    
                                 </div>
                              </a>

                           </div>

                        </div>

                        <?php } ?>

                  </div>

                   <div class="spacer-40"></div>

                 
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

    <?php $getHomeExpert = getSections(391); ?>

   <!--expert
   <section id="expert" class="bg-dirty-white" data-scroll-section>
      <div class="spacer-100"></div> 

      <div class="container">

         <div class="section-header">

            <div class="row align-items-end">

               <div class="col-md-9">

                  <h2 class="section-title fnt-gold"><?php print $getHomeExpert['title_top']; ?></h2>
                  <p class="section-subtitle"><?php print $getHomeExpert['title_middle']; ?></p>

                  <div class="spacer-40"></div>
               </div>

               <div class="col-md-3"></div>

            </div>
            
         </div>

         <div class="spacer-20"></div>

         <div class="section-body">
            
            <div class="form-container">

               <form action="" id="home-form">

                  <div class="row align-items-start">
                     <div class="col-md-11">

                        <textarea id="smartexpert" class="form-control w-100" placeholder="Tell us what your skin needs…" rows="6"></textarea>

                     </div>
                        <div class="col-md-1 text-end">
                           <a href="">
                              <img src="<?php print get_template_directory_uri(); ?>/img/icons/send-icon-<?php print $iconsLang; ?>.svg" class="" alt="icons">
                           </a>
                     </div>
                  </div>
               </form>
               <div class="spacer-20"></div>

               <p class="fnt-20">or choose the one you are suffering from</p>

               <ul class="nav">
                 <li class="nav-item">
                   <a class="nav-link text-uppercase" href="#">Fatigue</a>
                 </li>
                 <li class="nav-item">
                   <a class="nav-link text-uppercase" href="#">Wrinkles</a>
                 </li>
                 <li class="nav-item">
                   <a class="nav-link text-uppercase" href="#">Dryness</a>
                 </li>
                 <li class="nav-item">
                   <a class="nav-link text-uppercase" >Pigmentation</a>
                 </li>
                 <li class="nav-item">
                   <a class="nav-link text-uppercase" >Acne</a>
                 </li>
               </ul>


            </div>
         </div>


      </div>

      <div class="spacer-100"></div>
   
   </section>
   -->

   <?php $getHomeUsed = getSections(399); ?>

   <!--used-->
   <section id="used" class="vh-100 posRel bg-full force-vh-100" style="background-image: url('<?php print $getHomeUsed['background'];?>');" data-scroll-section>

      <div class="container-fluid vh-100 force-vh-100">

         <div class="section-header">

            <div class="used-caption position-absolute start-50 translate-middle">

               <div class="row">
                  <div class="col-md-2"></div>
                  <div class="col-md-8">
                     <center>
                        <h2 class="banner-title fnt-white fnt-thin preAnimate" data-scroll data-scroll-repeat data-scroll-delay="90" data-scroll-class="animateThis">
                           <?php print $getHomeUsed['title_top'];?>
                        </h2>
                     </center>
                  </div>
                  <div class="col-md-2"></div>
               </div>
            </div>

         </div>         

         <div class="section-body">

            <div class="used-item desktop-view">

               <!-- <div class="row">
                  
                  <div class="col-md-2"></div>
                  <div class="col-md-2">
                     <center>
                        <p class="fnt-20 fnt-regular preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
                           <?php print $getHomeUsed['title_middle'];?>
                        </p>
                        <img src="<?php print $getHomeUsed['featImg'];?>" class="img-fluid preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis"  alt="product">
                     </center>
                     <div class="spacer-40"></div>

                  </div>
                  <div class="col-md-8"></div>
               </div> -->    
                       
            </div>

         </div>
           

      </div>

   </section>

   <?php $getHomeOver= getSections(400); ?>

   <!--over-->
   <section id="over" class="vh-100 posRel bg-dirty-white force-vh-100" data-scroll-section>
      <div class="spacer-100"></div> 

      <div class="container-fluid vh-100">

         <div class="container">

            <div class="section-header force-ltr">

               <div class="row">
                  <div class="col-md-8">
                     <div class="spacer-80"></div> 
                     <center>
                        <h2 class="fnt-40 fnt-regular preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
                           <?php print $getHomeOver['title_top'];?>
                        </h2>

                        <?php print $getHomeOver['content'];?>

                     </center>
                  </div>
                  <div class="col-md-4"></div>
               </div>

            </div>

         </div>
         
         <div class="section-body">

            <div class="hand-product position-absolute start-50 translate-middle force-ltr">

               <div class="row">
                  <div class="col-md-4"></div>
                  <div class="col-md-8 hand-img">

                     <div class="desktop-view">
                        <img src="<?php print $getHomeOver['featImg'];?>" class="img-fluid preAnimate" data-scroll data-scroll-repeat data-scroll-delay="60" data-scroll-class="animateThis" alt="product">                        
                     </div>

                     <div class="mobile-view">
                        <img src="<?php print $getHomeOver['featImg'];?>" class="img-fluid" data-scroll data-scroll-repeat alt="product">                        
                     </div>                     
                     
                  </div>
               </div>

            </div>

         </div>
      </div>  

      <div class="spacer-100"></div>
   
   </section>

   <?php $getHomeCons= getSections(404); ?>

   <!--consultation-->
   <section id="consultation" class="bg-full" style="background-image: url('<?php print $getHomeCons['background'];?>');" data-scroll-section> 
      <div class="spacer-100"></div>

      <div class="container">

         <div class="section-header">

            <div class="row align-items-end">

               <div class="col-md-8">

                  <h2 class="section-title preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">
                     <?php print $getHomeCons['title_top'];?>
                  </h2>

                  <?php print $getHomeCons['content'];?>

                  <div class="spacer-40"></div>

                  <a href="<?php print $getHomeCons['button_link'];?>" class="btn btn-lg btn-dark fnt-thin btn-gernetic-black preAnimate" data-scroll data-scroll-repeat data-scroll-delay="90" data-scroll-class="animateThis">
                     <?php print $getHomeCons['button_text'];?>
                     <img src="<?php print get_template_directory_uri(); ?>/img/icons/discover-arrow-<?php print $iconsLang;?>.svg" class="btn-gernetic-arrow" alt="icon">
                  </a>   

                  <div class="spacer-40"></div>
               </div>

               <div class="col-md-4"></div>

            </div>
            
         </div>

         <div class="container-80"></div>

         <div class="section-body preAnimate" data-scroll data-scroll-repeat data-scroll-delay="120" data-scroll-class="animateThis">

             <div class="row">
               <div class="col-md-4 posRel">
                  <div class="consultant">
                     <img src="<?php print $getHomeCons['featImg'];?>" class="img-fluid" alt="consultant">
                  </div>
                  <div class="consultant-name-absolute">
                     <p class="fnt-18 fnt-Semibold remMar"><?php print $getHomeCons['title_middle'];?></p>
                     <p class="fnt-18 fnt-regular remMar"><?php print $getHomeCons['title_bottom'];?></p>
                  </div>
               </div>
               <div class="col-md-4"></div>
               <div class="col-md-4"></div>
            </div> 

         </div> 

      </div>

      <div class="spacer-100"></div>
   
   </section>  

   <?php $getHomeReview = getSections(414); ?>

   <!--review-->
   <section id="review" class="bg-dirty-white" data-scroll-section>
      <div class="spacer-100"></div>

      <div class="container posRel">

         <div class="section-header">

            <div class="row">
               
               <div class="col-md-6">
                  
                  <h2 class="section-title fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="30" data-scroll-class="animateThis">   <?php print $getHomeReview['title_top'];?>
                  </h2>

                  <p class="section-subtitle fnt-gold preAnimate" data-scroll data-scroll-repeat data-scroll-delay="60" data-scroll-class="animateThis">
                     <?php print $getHomeReview['title_middle'];?>
                  </p>             

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

            <!-- <div class="review-dot position-absolute start-50 translate-middled desktop-view">

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