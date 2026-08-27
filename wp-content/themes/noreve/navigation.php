<?php 
	$lang = pll_current_language(); 

	$reqLang = "ar";
	if($lang == "ar"){
		$reqLang = "en";
	}

	$postId = get_the_id();
	$arrayBBids = getB2b();

	$cartLink = get_pll_links(333);

	$notif_cart = 0;

	if (WC()->cart){
	    $cart_count = WC()->cart->get_cart_contents_count();
	    $notif_cart = 0;
	    $count_cart = 0;
	    if($cart_count != 0){
	    	$notif_cart = 1;
	    	$count_cart = $cart_count;
	    }
	}

	if(in_array($postId,$arrayBBids)){
		$cartLink = get_pll_links(574);
		if(isset($_SESSION['product_ids']) && !empty($_SESSION['product_ids'])){
			$notif_cart = 1;
		}		
	}


	//print_r($arrayBBids);

	// $navClass = "";
	// if (!is_front_page()){
	// 	$navClass = "nav-light";
	// }

	// $my_account_url = wc_get_page_permalink('myaccount');
	// $orders_url = wc_get_account_endpoint_url('orders');
	// $cart_url = site_url('cart-ar');

	// if($lang == "en"){

	// 	$my_account_url = site_url()."/en/my-account";
	// 	$orders_url = site_url()."/en/my-account/orders";
	// 	$cart_url = site_url('cart-en');

	// } 

	$getAds = getAds();
?>


<div id="main-navigation" class="bg-black fixed-top">
	
	<div class="desktop-view desktop-menu">

		<div class="navbar-top">

			<div class="container">
				<div class="row align-items-center">

					<div class="col-md-4">
						
						<div class="d-flex justify-content-start">

							<ul class="nav">
								<li class="nav-item">
									<?php if(is_user_logged_in()){ ?>

										<a class="nav-link" href="<?php print get_pll_links(516); ?>">

									<?php }else{ ?>

										<a class="nav-link" data-bs-toggle="modal" data-bs-target="#loginModal">

									<?php }?>
						          
						          	<img src="<?php print get_template_directory_uri(); ?>/img/icons/user.svg" alt="icons">
						          </a>
						        </li>

						        <li class="nav-item">
						          <a class="nav-link cart-cont" href="<?php print $cartLink; ?>">
						          	<?php if($notif_cart != 0){ ?>
							    		<div class="cart-count"></div>
							    	<?php } ?>
						          	<img src="<?php print get_template_directory_uri(); ?>/img/icons/cart.svg" alt="icons">
						          </a>
						        </li>

						        <li class="nav-item">	
						          <a class="nav-link" data-bs-toggle="modal" data-bs-target="#searchModal">
						          	<img src="<?php print get_template_directory_uri(); ?>/img/icons/search.svg" alt="icons">
						          </a>						          					          
						        </li>

						        <li id="lang-switcher" class="nav-item dropdown">
						        	<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						        		<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-<?php print $lang; ?>.png" alt="icons">
						        	</a>
						        	<ul class="dropdown-menu">

						        		<?php $get_language_switcher = get_language_switcher(get_the_ID()); ?>

						        		<li class="<?php if($lang == "ar"){print "hideThis";}?>">
						        			<a class="dropdown-item ar" href="<?php print $get_language_switcher['ar'];?>">
						        				<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-ar.png" alt="icons">
						        				العربية
						        			</a>
						        		</li>
						        		<li class="<?php if($lang == "en"){print "hideThis";}?>">
						        			<a class="dropdown-item en" href="<?php print $get_language_switcher['en'];?>">
						        				<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-en.png" alt="icons">
						        				English
						        			</a>
						        		</li>
						        		<!-- <li class="<?php if($lang == "fr"){print "hideThis";}?>">
						        			<a class="dropdown-item fr" href="<?php print $get_language_switcher['fr'];?>">
						        				<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-fr.png" alt="icons">
						        				Français
						        			</a>
						        		</li> -->

						        	</ul>						        	
						        </li>					        
							<ul>	
					
						</div>

					</div>

					<div class="col-md-4">

						<?php if(!empty($getAds)) { ?>
							
								<div class="ads-top">
									<div id="ad-list" class="owl-carousel">

										<?php foreach($getAds as $adsRow){ ?>

											<div class="item d-flex justify-content-center">
												<center>
													<?php print $adsRow['content']; ?>
												</center>
											</div>

										<?php }?>
									</div>

									<center>
										
									</center>
								</div>
										
					   <?php }?>

					</div>

					<div class="col-md-4">

						<div class="d-flex justify-content-end hideThis">
							<ul class="nav b2b-menu">
								<?php print get_custom_bb_top_menu_items(); ?>
								<!-- <li class="nav-item"><a class="nav-link" href="#">Salons & Clinics</a></li>
								<li class="nav-item btn-brown"><a class="nav-link" href="#">Individuals</a></li> -->
							</ul>
						</div>						

					</div>
					
				</div>				
			</div>
		
		</div>

		<div class="navbar-bottom">

		  	<div class="container">
		  		<div class="spacer-20"></div>

		  		<div class="row d-flex align-items-center">

		  			<div class="col-md-2">

		  				<div class="d-flex justify-content-start">
						    <a class="navbar-brand" href="<?php print pll_home_url(); ?>">
						    	<img src="<?php print get_template_directory_uri(); ?>/img/logos/logo-white.svg" alt="logo" class="img-fluid">
						    </a>
		  				</div>

		  			</div>

		  			<div class="col-md-10">

		  				<div class="d-flex justify-content-end">
							<ul class="nav">

								<?php  
									if(in_array($postId,$arrayBBids)){
										print get_bb_main_menu_items();
									}else{
										print get_custom_main_menu_items();
									} 

								?>

							</ul>		  					
		  				</div>

		  			</div>
	  		
		  		</div>

		  		<div class="spacer-20"></div>
		  	</div>			
			
		</div>

	</div>

	<div class="mobile-view mobile-menu">

		<div class="mobile-top-menu">

			<div class="container-fluid">

				<div class="spacer-20"></div>

				<div class="row align-items-center b2b-menu">

					<div class="hideThis">
						<?php print get_custom_bb_top_menu_mobile_items(); ?>
					</div>
					

					<!-- <div class="col-6 bg-gold">
						<div class="spacer-20"></div>
						<center>
							<a class="nav-link" href="#">Individuals</a>
						</center>
						<div class="spacer-20"></div>
					</div>

					<div class="col-6">
						<div class="spacer-20"></div>
						<center>
							<a class="nav-link" href="#">Salons & Clinics</a>
						</center>
						<div class="spacer-20"></div>
					</div> -->

					<div class="col-12">
						<div class="spacer-10"></div>
						<div class="ads-top">
							<?php if(!empty($getAds)) { ?>
								
								<div id="ad-listM" class="owl-carousel">

									<?php foreach($getAds as $adsRowM){ ?>

										<div class="item d-flex justify-content-center">
											<center>
												<?php print $adsRowM['content']; ?>
											</center>
										</div>

									<?php }?>
								</div>
									
						   <?php }?>
						</div>
						<div class="spacer-10"></div>
					</div>
				</div>				

			</div>

			<div class="spacer-40"></div>
			
		</div>

		<div class="mobile-bottom-menu container">

			<div class="row align-items-end">

				<div class="col-5">
					<div class="navbar-brand text-center">
						<a href="<?php print pll_home_url(); ?>">
							<img src="<?php print get_template_directory_uri(); ?>/img/logos/logo-white.svg" alt="logo" class="img-fluid">
						</a>
					</div>
				</div>				

				<div class="col-7">
					<div class="spacer-20"></div>
					<ul class="nav navbar-expand nav-mobile">

				        <li class="nav-item remPad">	
				          <a class="nav-link" data-bs-toggle="modal" data-bs-target="#searchModal">
				          	<img src="<?php print get_template_directory_uri(); ?>/img/icons/search.svg" alt="icons">
				          </a>						          					          
				        </li>	
				        					
						<li class="nav-item remPad">
							<?php if(is_user_logged_in()){ ?>

								<a class="nav-link" href="<?php print get_pll_links(516); ?>">

							<?php }else{ ?>

								<a class="nav-link" data-bs-toggle="modal" data-bs-target="#loginModal">

							<?php }?>
				          
				          	<img src="<?php print get_template_directory_uri(); ?>/img/icons/user.svg" alt="icons">
				          </a>
				        </li>

				        <li class="nav-item remPad">
				          <a class="nav-link" href="<?php print get_pll_links(333); ?>">
				          	<img src="<?php print get_template_directory_uri(); ?>/img/icons/cart.svg" alt="icons">
				          </a>
				        </li>

				        <li class="nav-item remPad">
							<a class="nav-link btn btn-menu-mobile fnt-white" data-bs-toggle="offcanvas" href="#offcanvasGernetic">
								<img src="<?php print get_template_directory_uri(); ?>/img/icons/mobile-menu-btn.svg" alt="icon" class="">
							</a>
				        </li>

					</ul>
				</div>



				<!-- <div class="col-3">
					<div class="spacer-20"></div>
					<div class="text-end">
						<a class="btn btn-menu-mobile fnt-white" data-bs-toggle="offcanvas" href="#offcanvasGernetic">
							<img src="<?php print get_template_directory_uri(); ?>/img/icons/mobile-menu-btn.svg" alt="icon" class="img-fluid">
						</a>
					</div>
				</div> -->

			</div>

			<div class="spacer-40"></div>
		</div>

		<div class="offcanvas offcanvas-start" id="offcanvasGernetic">

			<div class="offcanvas-header">

				<div class="row d-flex align-items-center w-100">
					<div class="col-6">
						<div class="navbar-brand text-start">
							<a href="<?php print pll_home_url(); ?>">
								<img src="<?php print get_template_directory_uri(); ?>/img/logos/logo-white.svg" alt="logo" class="img-fluid">
							</a>
						</div>
						
					</div>

					<div class="col-6">
						<div class="text-end">
							<a class="btn btn-menu-mobile fnt-white" data-bs-dismiss="offcanvas">
								<i class="fa-solid fa-xmark"></i>
							</a>							
						</div>
					</div>
				</div>				

			</div>

			<div class="offcanvas-body">
			      <ul class="navbar-nav">

					<?php  

						if(in_array($postId,$arrayBBids)){
							print get_bb_main_menu_items();
						}else{
							print get_custom_main_menu_items();
						} 

					?>

		        <li id="lang-switcher" class="nav-item dropdown">
		        	<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
		        		<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-<?php print $lang; ?>.png" alt="icons">
		        	</a>
		        	<ul class="dropdown-menu">

		        		<li class="<?php if($lang == "ar"){print "hideThis";}?>">
		        			<a class="dropdown-item ar" href="<?php print $get_language_switcher['ar'];?>">
		        				<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-ar.png" alt="icons">
		        				العربية
		        			</a>
		        		</li>
		        		<li class="<?php if($lang == "en"){print "hideThis";}?>">
		        			<a class="dropdown-item en" href="<?php print $get_language_switcher['en'];?>">
		        				<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-en.png" alt="icons">
		        				English
		        			</a>
		        		</li>
		        		<!-- <li class="<?php if($lang == "fr"){print "hideThis";}?>">
		        			<a class="dropdown-item fr" href="<?php print $get_language_switcher['fr'];?>">
		        				<img src="<?php print get_template_directory_uri(); ?>/img/icons/flag-fr.png" alt="icons">
		        				Français
		        			</a>
		        		</li> -->

		        	</ul>						        	
		        </li>			      		

			      </ul>
			</div>
		</div>
	
	</div>


</div>

<div class="mobile-menu-spacer mobile-view"></div>

<?php  if (!in_array($postId,array(149,151,153))){ ?>

	<div class="desktop-menu-spacer desktop-view"></div>

<?php }?> 
