<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Gernetic
 */
	
	 $lang = pll_current_language();

    $iconsLang = "en";
    if($lang == "ar"){$iconsLang = "ar";}
    if($lang == "fr"){$iconsLang = "en";}

	$arrayBBids = getB2b();
	$postId = get_the_id();

?>

	<footer data-scroll-section>
		<div class="spacer-80"></div>

		<div class="footer-top">

			<div class="container">
				
				<div class="row">
					
					<div class="col-6 order-2 col-md order-md-1">
						<h5><?php pll_e('About GERnetic'); ?></h5>

						<ul>

							<?php print get_footer_menu_items(1); ?>

						</ul>

						<div class="spacer-20"></div>
					</div>

					<div class="col-6 order-3 col-md order-md-2">
						<h5><?php pll_e('Shop'); ?></h5>

						<ul>

							<?php print get_footer_menu_items(2); ?>

						</ul>						
						<div class="spacer-20"></div>
					</div>

					<div class="col-6 order-4 col-md order-md-3">
						<h5><?php pll_e('Support and Help'); ?></h5>

						<ul>

							<?php print get_footer_menu_items(3); ?>
						
						</ul>						
						<div class="spacer-20"></div>
					</div>

					<!-- <div class="col-6 order-5 col-md order-md-4">
						<h5><?php pll_e('Consultations'); ?></h5>

						<ul>

							<?php print get_footer_menu_items(4); ?>

						</ul>						
						<div class="spacer-20"></div>
					</div> -->

					<div class="col-12 order-1 col-md order-md-5">

						<div class="row">
							<div class="col-1"></div>
							<div class="col-10">

							<center>
								<a href="<?php print pll_home_url(); ?>">
									<img src="<?php print get_template_directory_uri(); ?>/img/logos/logo-black.svg" alt="logo" class="img-fluid">
								</a>
							</center>								

							</div>
							<div class="col-1"></div>
						</div>


						<div class="spacer-40"></div>
						<div class="spacer-40 mobile-view"></div>
					</div>																				
				</div>

			</div>			
		</div>

		<!-- <div class="spacer-80"></div> -->

		<div class="footer-bottom">

			<div class="container">
				<div class="row align-items-center">

					<div class="col-12 order-3 col-md-4 order-md-1">
						<div class="desktop-view">
							<p class="copyright"><?php pll_e('Copyright'); ?></p>
						</div>
						<div class="mobile-view">
							<center>
								<p class="copyright"><?php pll_e('Copyright'); ?></p>
							</center>
						</div>
						<div class="spacer-40"></div>
					</div>

					<div class="col-12 order-1 col-md-3 order-md-2">

						<?php

						 $social_media_and_contacts_options = get_option( 'social_media_and_contacts_option_name' ); // Array of All Options
						 $email_0 = $social_media_and_contacts_options['email_0']; // Email
						 $phone_1 = $social_media_and_contacts_options['phone_1']; // Phone
						 $mobile_2 = $social_media_and_contacts_options['mobile_2']; // Mobile
						 $instagram_3 = $social_media_and_contacts_options['instagram_3']; // Instagram
						 $linked_in_4 = $social_media_and_contacts_options['linked_in_4']; // Linked In
						 $twitter_x_5 = $social_media_and_contacts_options['twitter_x_5']; // Twitter X
						 $tiktok_6 = $social_media_and_contacts_options['tiktok_6']; // Tiktok
						 $whatsapp = $social_media_and_contacts_options['whatsapp']; // whatsapp

						?>

						<center>
							<ul id="social" class="nav justify-content-center">
								<li class="nav-item">
									<a class="nav-link" href="<?php print $instagram_3; ?>"><i class="fa-brands fa-instagram"></i></a>
								</li>
								<li class="nav-item">
									<a class="nav-link" href="<?php print $linked_in_4; ?>"><i class="fa-brands fa-snapchat"></i></a>
								</li>
								<!-- <li class="nav-item">
									<a class="nav-link" href="<?php print $twitter_x_5; ?>"><i class="fa-brands fa-x-twitter"></i></a>
								</li> -->
								<li class="nav-item">
									<a class="nav-link" href="<?php print $tiktok_6; ?>"><i class="fa-brands fa-tiktok"></i></a>
								</li>
							</ul>
						</center>

						<div class="spacer-40"></div>
					</div>

					<div class="col-12 order-4 col-md-1 order-md-3"></div>

					<div class="col-12 order-2 col-md-4 order-md-4">
						<img src="<?php print get_template_directory_uri(); ?>/img/logos/payment-methods.svg" alt="logo" class="img-fluid">
						<div class="spacer-40"></div>
					</div>

				</div>				
			</div>
			
		</div>

		<div class="spacer-40"></div>
		<div class="spacer-100 mobile-view"></div>
		<div class="spacer-100 mobile-view"></div>
		<div class="spacer-100 mobile-view"></div>	

		<?php if(is_front_page() || is_cart() || is_checkout() || in_array($postId, array(149,151,153))){ ?>

			<div class="spacer-40 mobile-view"></div>

		<?php }else{ ?>

			<div class="spacer-100"></div>
			<div class="spacer-40"></div>

		<?php }?>
	</footer>

</div>

<!-- Search  -->
<div class="modal fade" id="searchModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
				<div class="header-img">
      		<img src="<?php print get_template_directory_uri(); ?>/img/login/header.png" alt="login" class="img-fluid">
      	</div>
        
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

      	<div class="search-products">

      		<?php 

	      		if($lang == "en"){
	      			print do_shortcode('[searchwp_form id=1]');
	      		}

	      		if($lang == "ar"){
	      			print do_shortcode('[searchwp_form id=2]');
	      		}

      		?>

      	</div>


      </div>

    </div>
  </div>
</div>

<?php if(!is_user_logged_in()){ ?>

<!-- Modal -->
<div class="modal fade" id="loginModal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
      	<div class="header-img">
      		<img src="<?php print get_template_directory_uri(); ?>/img/login/header.png" alt="login" class="img-fluid">
      	</div>
        
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

		<ul class="nav nav-tabs" id="loginTab">
		  <li class="nav-item" role="presentation">
		    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login" type="button">
		      <?php pll_e('Login'); ?>
		    </button>
		  </li>

		  <li class="nav-item" role="presentation">
		    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#register" type="button">
		      <?php pll_e('Register'); ?>
		    </button>
		  </li>
		</ul>

		<div class="spacer-40"></div>

		<div class="tab-content" id="loginTab">

		  <div class="tab-pane fade show active" id="login">

		    <div class="container">
		    	
		    	
		    	<?php print do_shortcode('[wc_login_only]');?>
		    	<?php print do_shortcode('[ventraconnect_sl_social_login providers="all"]');?>

		    	

		    </div>
		  </div>

		  <div class="tab-pane fade" id="register">
		    <div class="container">

		    	<h5><?php pll_e('Join'); ?></h5>
		    	<p><?php pll_e('Sign up'); ?></p>
		    	
		    	<?php print do_shortcode('[wc_register_only]');?>
		    	<?php //print do_shortcode('[ventraconnect_sl_social_login providers="all"]');?>

		    	<div class="spacer-10"></div>

		    </div>
		  </div>

		</div>

      </div>

    </div>
  </div>
</div>

<?php } ?>

<?php wp_footer(); ?>

<!--other scripts-->
<!-- <script src="https://kit.fontawesome.com/aa611fc61f.js" crossorigin="anonymous"></script> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> -->

<?php if(in_array($postId,$arrayBBids)){ ?>

	<script>
		jQuery(document).ready(function(){

			jQuery(".b2b-menu .nav-item.b2b").removeClass("btn-brown");
			jQuery(".b2b-menu .nav-item.b2b-0").addClass("btn-brown");
			jQuery(".b2b-menu .col-6.b2b").removeClass("bg-gold");
			jQuery(".b2b-menu .col-6.b2b-0").addClass("bg-gold");			

		});
	</script>

<?php } ?>

<?php if(is_product() || is_checkout()){ ?>

	<script>
		// jQuery(document).on('updated_checkout', function () {
		//     if (window.Tamara) {
		//         Tamara.refresh();
		//     }
		// });

		window.addEventListener("error", function (e) {
		    if (e.message.includes("Moyasar")) {
		        console.warn("Moyasar warning ignored");
		        e.preventDefault();
		    }
		});
	</script>

<?php } ?>

<?php if(is_checkout()){ ?>

<script>
	jQuery(document).ready(function() {
    
	});
</script>

 <script>
  //  registerPaymentMethod({
  //   name: 'tamara',
  //   canMakePayment: () => true
  // });

  //window.wc.wcBlocksRegistry.registerPaymentMethod();


 </script>

<?php } ?>

<?php if(is_cart()){ ?>

	<script>

// 		jQuery(document).ready(function(){
// 
// 		  	jQuery(".wc-block-cart-item__total-price-and-sale-badge-wrapper").each(function(){
// 
// 	  		   	setTimeout(() => {
// 	  		   		var getHeight = jQuery(this).parent().parent().height();
// 	  		   		console.log(getHeight);
// 	  		   		jQuery(this).css("height", getHeight+"px");
// 				}, 1000);
// 
// 		  	});		
// 
// 		});

	</script>

<?php } ?>

<?php if(!is_user_logged_in()){ ?>

	<script>
		jQuery(document).ready(function(){

			jQuery('.wsc-buttons').on('click', function () {
			  // Bootstrap modal close
			  var modalEl = document.querySelector('.modal.show');
			  if (modalEl) {
			    var modal = bootstrap.Modal.getInstance(modalEl);
			    modal.hide();
			  }
			});

		});
	</script>	

<?php } ?>	


</body>
</html>
