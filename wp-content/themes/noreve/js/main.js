
jQuery(window).on("load", function(){

	// const convertStyle = () => {
	// 	const height = window.innerHeight;
	// 	Array.from(document.getElementsByTagName("section")).forEach((element) => {
	// 		element.style.height = `${height}px`;
	// 	});
	// };
	// window.addEventListener("resize", convertStyle);
	// window.addEventListener("DOMContentLoaded", convertStyle);

	const htmlLang = jQuery("html").attr("lang"); 
	var sliderlang = false;
	var sliderAnimateOut = "animate__fadeOutLeft";
	if(htmlLang == "ar"){
		sliderlang = true;
		sliderAnimateOut = "animate__fadeOutRight";
	}

	var getMobileMenuHeight = jQuery("#main-navigation .mobile-menu").height();
	var getDesktopMenuHeight = jQuery("#main-navigation .desktop-menu").height();

	//console.log(getMobileMenuHeight);

	jQuery(".mobile-menu-spacer").css("height", getMobileMenuHeight+"px");
	jQuery(".not-front .desktop-menu-spacer").css("height", getDesktopMenuHeight+"px");



	/*---Banner--- */
    jQuery("#banner-slider").owlCarousel({
			items: 1,
			loop: false,
			dots: false,
			nav: false,
			autoplay: false, 
			margin:0,  
			stagePadding: 0,           
			responsiveClass: true,
			mouseDrag: true,
			touchDrag: true,
			autoplayHoverPause: true,
			animateOut: 'fadeOut',
			animateIn: 'fadeIn',
			rtl: sliderlang,
			smartSpeed: 600
    });	

    jQuery('.banner-dot .bird-dots .btn-dot').each(function(){

	    jQuery(this).click(function(e){
	    	e.preventDefault();
	    	var index = jQuery(this).data('slide');
	    	jQuery('.banner-dot .bird-dots .btn-dot').removeClass("active");
	    	jQuery("#banner-slider").trigger('to.owl.carousel', [index, 600]);
	    	jQuery(this).addClass("active");
		});

    });

	jQuery("#banner-slider").on('changed.owl.carousel', function(event) {

	    var index = event.item.index - event.relatedTarget._clones.length / 2;
	    var count = event.item.count;
	    index = ((index % count) + count) % count;

	    jQuery('.banner-dot .bird-dots .btn-dot').removeClass("active");
	    jQuery('.banner-dot .bird-dots .btn-dot[data-slide="'+index+'"]').addClass('active');
	});


	jQuery("#product-list").each(function(){

		jQuery(this).owlCarousel({
		     loop: true,
		     dots: false,
		     nav: false,
		     autoplay: true, 
		     margin:20,       
		     responsiveClass: true,
		     mouseDrag: true,
		     touchDrag: true,
		     smartSpeed:600,
		     rtl: sliderlang,
		     responsive: { 
		       0: { items: 2 }, 
		       600: { items: 2 }, 
		       1000: { items: 3 } 
		     },
		});

	});

    jQuery('.product-dot .bird-dots .btn-dot').each(function(){

	    jQuery(this).click(function(e){
	    	e.preventDefault();
	    	var index = jQuery(this).data('slide');
	    	jQuery('.product-dot .bird-dots .btn-dot').removeClass("active");
	    	jQuery("#product-list").trigger('to.owl.carousel', [index, 600]);
	    	jQuery(this).addClass("active");
		});

    });

	jQuery("#product-slider").on('changed.owl.carousel', function(event) {

	    var index = event.item.index - event.relatedTarget._clones.length / 2;
	    var count = event.item.count;
	    index = ((index % count) + count) % count;

	    jQuery('.product-dot .bird-dots .btn-dot').removeClass("active");
	    jQuery('.product-dot .bird-dots .btn-dot[data-slide="'+index+'"]').addClass('active');
	
	});

    jQuery("#product-slide").owlCarousel({
	     loop: true,
	     dots: false,
	     nav: false,
	     autoplay: false, 
         margin:0,  
         stagePadding: 0,      
	     responsiveClass: true,
	     mouseDrag: true,
	     touchDrag: true,
	     smartSpeed:600,
	     rtl: sliderlang,
	     responsive: { 
	       0: { items: 1 }, 
	       600: { items: 1 }, 
	       1000: { items: 1 } 
	     },
	});	


	jQuery("#review-list").each(function(){

		jQuery(this).owlCarousel({
		     loop: true,
		     dots: false,
		     nav: false,
		     autoplay: true, 
		     margin:20,       
		     responsiveClass: true,
		     mouseDrag: true,
		     touchDrag: true,
		     smartSpeed:600,
		     rtl: sliderlang,
		     responsive: { 
		       0: { items: 2 }, 
		       600: { items: 3 }, 
		       1000: { items: 4 } 
		     },
		});

	});

	jQuery('.review-dot .bird-dots .btn-dot').each(function(){

	    jQuery(this).click(function(e){
	    	e.preventDefault();
	    	var index = jQuery(this).data('slide');
	    	jQuery('.review-dot .bird-dots .btn-dot').removeClass("active");
	    	jQuery("#review-list").trigger('to.owl.carousel', [index, 600]);
	    	jQuery(this).addClass("active");
		});

	});

	jQuery("#review-slider").on('changed.owl.carousel', function(event) {

	    var index = event.item.index - event.relatedTarget._clones.length / 2;
	    var count = event.item.count;
	    index = ((index % count) + count) % count;

	    jQuery('.review-dot .bird-dots .btn-dot').removeClass("active");
	    jQuery('.review-dot .bird-dots .btn-dot[data-slide="'+index+'"]').addClass('active');
	});

	jQuery("#partner-slider").owlCarousel({
		loop: true,
		dots: false,
		nav: false,
		autoplay: true, 
		margin:0,            
		responsiveClass: true,
		mouseDrag: false,
		touchDrag: false,
		autoplay: true,
		autoplayTimeout: 4500,
		autoplaySpeed: 4500, 
		smartSpeed: 4500, 
		slideTransition: 'linear',
		rtl: sliderlang,
		responsive: { 
			0: { items: 4 }, 
			600: { items: 5 }, 
			1000: { items: 10 } 
         },
    });

    jQuery("#ad-list").owlCarousel({
				loop: true,
				dots: false,
				nav: false,
				autoplay: true, 
				margin:0,            
				responsiveClass: true,
				mouseDrag: false,
				touchDrag: false,
				autoplay: true,
				autoplayTimeout: 4000,
				autoplaySpeed: 4000, 
				smartSpeed: 4000, 
				slideTransition: 'linear',
				rtl: sliderlang,
				responsive: { 
				0: { items: 1 }, 
				600: { items: 1 }, 
				1000: { items: 1 } 
         },
    });

    jQuery("#ad-listM").owlCarousel({
				loop: true,
				dots: false,
				nav: false,
				autoplay: true, 
				margin:0,            
				responsiveClass: true,
				mouseDrag: false,
				touchDrag: false,
				autoplay: true,
				autoplayTimeout: 4000,
				autoplaySpeed: 4000, 
				smartSpeed: 4000, 
				slideTransition: 'linear',
				rtl: sliderlang,
				responsive: { 
				0: { items: 1 }, 
				600: { items: 1 }, 
				1000: { items: 1 } 
         },
    });

	/*--- store tab 
	jQuery("#storeTabOther .btn-nxt").on("click touch", function(e){

		e.preventDefault();
		var getNext = jQuery("#storeTabContent .tab-pane.show .owl-carousel").attr("id");
		jQuery("#"+getNext+"").trigger('next.owl.carousel'); 

	});


	jQuery("#storeTabOther .btn-prev").on("click touch", function(e){

		e.preventDefault();
		var getprev = jQuery("#storeTabContent .tab-pane.show .owl-carousel").attr("id");
		jQuery("#"+getprev+"").trigger('prev.owl.carousel'); 

	});
	---*/
	
	/*--- join list 
    jQuery("#ticker-list").owlCarousel({
		loop: true,
		dots: false,
		nav: false,
		autoplay: true, 
		margin:0,            
		responsiveClass: true,
		mouseDrag: false,
		touchDrag: false,
		autoplay: true,
		autoplayTimeout: 4000,
		autoplaySpeed: 4000, 
		smartSpeed: 4000, 
		slideTransition: 'linear',
		rtl: sliderlang,
		responsive: { 
		0: { items: 1 }, 
		600: { items: 1 }, 
		1000: { items: 1 } 
         },
    });
    --- */

	/*--- testimony list
    jQuery("#tesimony-list").owlCarousel({
		loop: true,
		dots: false,
		nav: false,
		autoplay: true, 
		margin:50,            
		responsiveClass: true,
		mouseDrag: false,
		touchDrag: false,
		autoplay: true,
		autoplayTimeout: 4500,
		autoplaySpeed: 4500, 
		smartSpeed: 4500, 
		slideTransition: 'linear',
		rtl: sliderlang,
		responsive: { 
		0: { items: 2 }, 
		600: { items: 3 }, 
		1000: { items: 4 } 
         },
    });
	 --- */

	/*--- join list
    jQuery("#join-list").owlCarousel({
		loop: true,
		dots: false,
		nav: false,
		autoplay: true, 
		margin:0,            
		responsiveClass: true,
		mouseDrag: false,
		touchDrag: false,
		autoplay: true,
		autoplayTimeout: 4000,
		autoplaySpeed: 4000, 
		smartSpeed: 4000, 
		slideTransition: 'linear',
		rtl: sliderlang,
		responsive: { 
		0: { items: 2 }, 
		600: { items: 3 }, 
		1000: { items: 4 } 
         },
    });
     --- */

    /*--- about offer list
	jQuery("#about-offer-list").owlCarousel({
	     loop: true,
	     dots: false,
	     nav: false,
	     autoplay: false, 
	     margin:5,       
	     responsiveClass: true,
	     mouseDrag: true,
	     touchDrag: true,
	     autoplayHoverPause: true,
	     rtl: sliderlang,                                                 
	     responsive: { 
	       0: { 
	       	items: 1,
	       	center: false, 
	       }, 
	       600: { 
	       	center: true,
	       	items: 2 
	       }, 
	       1000: { 
	       	center: true,
	       	items: 3 
	       } 
	     },
	});

	jQuery("#aboutOfferArrow .btn-nxt").on("click touch", function(e){

		e.preventDefault();
		jQuery("#about-offer-list").trigger('next.owl.carousel'); 

	});

	jQuery("#aboutOfferArrow .btn-prev").on("click touch", function(e){

		e.preventDefault();
		jQuery("#about-offer-list").trigger('prev.owl.carousel'); 

	});	

	--- */

	jQuery("#expert .nav-link").each(function(){

		jQuery(this).on("click touch", function(e){

			e.preventDefault();

			var text = jQuery(this).text().trim();
			var textarea = jQuery('#smartexpert');

		    if (textarea.val() === '') {
		        textarea.val(text);
		    } else {
		        textarea.val(textarea.val() + ', ' + text);
		    }
		});

	});

	jQuery(".consultation-form .btn-consultation").each(function(){

		jQuery(this).on("click touch", function(e){

			e.preventDefault();

			var text = jQuery(this).text().trim();
			var inputText = jQuery(this).parent().parent().find('input');

			if(inputText.attr("id") == "otherProducts"){

				inputText.val("");
				inputText.val(text);

			}else{

			    if (inputText.val() === '') {
			        inputText.val(text);
			    } else {
			        inputText.val(inputText.val() + ', ' + text);
			    }				

			}


		});

	});

	jQuery("#upload").on("click touch", function(){
		//console.log("asdasd");
		//jQuery("#formFile").trigger("click");
		document.getElementById("formFile").click();
	});

	jQuery("#date").on("focus", function(){
		jQuery(this).attr("type","date");
	});

	jQuery("#date").on("blur", function(){
		jQuery(this).attr("type","text");
	});

	// jQuery("#searchModal .swp-input").addClass("form-control");
	// jQuery("#searchModal .swp-button").addClass("btn-dark btn-gernetic-black-center");
	jQuery("#searchwp-form-1 .swp-input").addClass("form-control");
	jQuery("#searchwp-form-1 .swp-button").addClass("btn-dark btn-gernetic-black-center");
	jQuery("#searchwp-form-2 .swp-input").addClass("form-control");
	jQuery("#searchwp-form-2 .swp-button").addClass("btn-dark btn-gernetic-black-center");

	if(htmlLang == "ar"){
		jQuery("#searchwp-form-1 .swp-button").val("يبحث");
		jQuery("#searchwp-form-2 .swp-button").val("يبحث");
	}


	const scroll = new LocomotiveScroll({
		el: document.querySelector('[data-scroll-container]'),
		smooth: true,
		smartphone: {
			smooth: true,
			multiplier: 1.0
		},
		tablet: {smooth: true},
	});	

	scroll.update();

// 	let originalHeight = window.innerHeight;
// 
// 	 jQuery(window).on("resize", function () {
// 	    let currentHeight = window.innerHeight;
// 
// 	    if (currentHeight < originalHeight * 0.75) {
// 	      setTimeout(scroll.update(), 300);
// 	    } else {
// 	      setTimeout(scroll.update(), 300);
// 	    }
// 	 });
// 
// 	jQuery('input, textarea, select').each(function(){
// 
// 		 jQuery(this).on("focus blur", function(){
// 
// 		 	console.log("focus blur");
// 		 	setTimeout(scroll.update(), 500);
// 		   
// 		 });	
// 
// 	});


	document.querySelectorAll('.accordion-collapse').forEach(function(el){

	    el.addEventListener('shown.bs.collapse', function () {
	        //console.log('Opened:', this.id);
	        scroll.update();
	    });

	    el.addEventListener('hidden.bs.collapse', function () {
	        //console.log('Closed:', this.id);
	        scroll.update();
	    });

	});


	jQuery(".bg-change").each(function(){

		if(window.innerWidth <= 768){
			var bgImg = jQuery(this).attr('data-bg-mobile');
		}else{
			var bgImg = jQuery(this).attr('data-bg-desktop');
		}

		jQuery(this).css('background-image', 'url(' + bgImg + ')');

		scroll.update();

	});

	jQuery(".btn-more").on("click touch", function(e){
		e.preventDefault();

		jQuery('.product-categories .list-group-item:gt(6)').toggleClass("hidden-category");

		jQuery(this).toggleClass("show");

	    jQuery(this).html(
	        jQuery(this).hasClass('show')
	        ? '<i class="fa-solid fa-angle-up fa-mar-5"></i><b>Less</b>'
	        : '<i class="fa-solid fa-angle-down fa-mar-5"></i><b>More</b>'
	    );

	     scroll.update();
	});

	jQuery('.product-categories .list-link').each(function(){


		jQuery(this).hover(function(){

		    jQuery(this).find('.fa-solid').css("color","rgba(174, 153, 98, 1)");

		}, function(){

		    jQuery(this).find('.fa-solid').css("color","#fff");

		});

		jQuery(this).on("click touch", function(){

			jQuery(this).toggleClass("active");

			var jsonResult = JSON.stringify(getDataCats());
			//console.log(jsonResult);

		});
	});

  	jQuery(".b2c-page .product-categories .list-link").each(function(){

         jQuery(this).on("click touch", function(){

            var getTermId = jQuery(this).data("cat-id");

            setTimeout(function(){
               var getTermIds = getActiveData();
               //console.log(getTermIds);

                  jQuery.ajax({
                   url: "/wp-admin/admin-ajax.php",
                   type: 'POST',
                   data: {
                     action: 'product_filter',
                     categories: JSON.stringify(getTermIds)
                   },
                   success: function (response) {
                     //console.log(response);

                     jQuery("#product-lists").html("").fadeOut(100);
                     jQuery("#product-lists").html(response).fadeIn(300);

                  	setTimeout(() => {
                  		// scroll.scrollTo('#product-scroll-to');
						      scroll.update();
					    	}, 400);

                   }

                 });

            }, 600);
           
            
         });

   });

  	jQuery(".home.b2c-page .product-categories .list-link").each(function(){

         jQuery(this).on("click touch", function(){

         	//console.log("clicked");

            var getTermId = jQuery(this).data("cat-id");

            setTimeout(function () {
               var getTermIds = getActiveData();
               //console.log(getTermIds);

                  jQuery.ajax({
                   url: "/wp-admin/admin-ajax.php",
                   type: 'POST',
                   data: {
                     action: 'product_filter_home',
                     categories: JSON.stringify(getTermIds)
                   },
                   success: function (response) {
                     //console.log(response);

                     jQuery('#product-list').trigger('destroy.owl.carousel');
                     jQuery("#product-list").html("").fadeOut(100);
                     jQuery("#product-list").html(response).fadeIn(300);
							jQuery("#product-list").owlCarousel({
								     loop: true,
								     dots: false,
								     nav: false,
								     autoplay: true, 
								     margin:20,       
								     responsiveClass: true,
								     mouseDrag: true,
								     touchDrag: true,
								     smartSpeed:600,
								     rtl: sliderlang,
								     responsive: { 
								       0: { items: 2 }, 
								       600: { items: 2 }, 
								       1000: { items: 3 } 
								     },
								});

                  	setTimeout(() => {
                  		//console.log("test");
					      	scroll.update();
						      // scroll.scrollTo('#product-scroll-to');
				    		}, 400);

                   }

                 });

            }, 600);
           
            
         });

   });

  	jQuery(".b2b-page .product-categories .list-link").each(function(){

         jQuery(this).on("click touch", function(){

            var getTermId = jQuery(this).data("cat-id");

            setTimeout(function(){
               var getTermIds = getActiveData();
               //console.log(getTermIds);

                  jQuery.ajax({
                   url: "/wp-admin/admin-ajax.php",
                   type: 'POST',
                   data: {
                     action: 'product_filter_b2b',
                     categories: JSON.stringify(getTermIds)
                   },
                   success: function (response) {
                     //console.log(response);

                     jQuery("#product-lists").html("").fadeOut(100);
                     jQuery("#product-lists").html(response).fadeIn(300);

                  	setTimeout(() => {
						      scroll.update();
						      // scroll.scrollTo('#product-scroll-to');
					    	}, 400);

                   }

                 });

            }, 600);
           
            
         });

   });

  	jQuery(".page-template-content-front-b2b.b2b-page .product-categories .list-link").each(function(){

         jQuery(this).on("click touch", function(){

         	//console.log("clicked");

            var getTermId = jQuery(this).data("cat-id");

            setTimeout(function () {
               var getTermIds = getActiveData();
               //console.log(getTermIds);

                  jQuery.ajax({
                   url: "/wp-admin/admin-ajax.php",
                   type: 'POST',
                   data: {
                     action: 'product_filter_home_b2b',
                     categories: JSON.stringify(getTermIds)
                   },
                   success: function (response) {
                     console.log(response);

                     jQuery('#product-list').trigger('destroy.owl.carousel');
                     jQuery("#product-list").html("").fadeOut(100);
                     jQuery("#product-list").html(response).fadeIn(300);
							jQuery("#product-list").owlCarousel({
								     loop: true,
								     dots: false,
								     nav: false,
								     autoplay: true, 
								     margin:20,       
								     responsiveClass: true,
								     mouseDrag: true,
								     touchDrag: true,
								     smartSpeed:600,
								     rtl: sliderlang,
								     responsive: { 
								       0: { items: 2 }, 
								       600: { items: 2 }, 
								       1000: { items: 3 } 
								     },
								});

                  	setTimeout(() => {
					      	scroll.update();
						      // scroll.scrollTo('#product-scroll-to');
				    		}, 400);

                   }

                 });

            }, 600);
           
            
         });

   });


	jQuery(".addToQuote").each(function(){

		jQuery(this).on("click touch", function(e){

			e.preventDefault();

			let el = this;

			var getProdId = jQuery(this).attr("data-product-id");

			//console.log(getProdId);

         jQuery.ajax({

	          url: "/wp-admin/admin-ajax.php",
	          type: 'POST',
	          data: {
	            action: 'product_addto_quote',
	            id: getProdId 
	          },
	          success: function (response){

	          	//console.log(response);

					let tooltip = bootstrap.Tooltip.getOrCreateInstance(el);  
					
					if(response.success == 0){
						tooltip.setContent({ '.tooltip-inner': response.message});
					}
					

					tooltip.show();

	         	setTimeout(() => {
				      scroll.update();
			    	}, 350);						

	         	setTimeout(() => {
				      tooltip.hide();
			    	}, 5000);



	          }

         });
		

		});

	});

	jQuery(".delItem").each(function(){

		jQuery(this).on("click touch", function(){

			var getDataId = jQuery(this).attr("data-id");

         jQuery.ajax({

	          url: "/wp-admin/admin-ajax.php",
	          type: 'POST',
	          data: {
	            action: 'product_delfrom_quote',
	            id: getDataId 
	          },
	          success: function (response){

	          	//console.log(response);

	         	setTimeout(() => {
				      scroll.update();
			    	}, 350);						



	          }

         });				

		})

	});


	// if (jQuery.is_checkout()) {
	//         jQuery.scroll_to_notices = function(scrollElement) {
	//             // Stops the page from jumping, leaving native scroll behavior
	//             return false; 
	//         };
	//     }



// 	if (document.body.classList.contains('single-product') || document.body.classList.contains('woocommerce-cart') || document.body.classList.contains('woocommerce-checkout')) {
// 	    
// 	    if (typeof scroll !== "undefined") {
// 	        scroll.destroy();
// 	    }
// 
// 	    document.documentElement.classList.remove('has-scroll-smooth');
// 	    document.body.style.overflow = 'auto';
// 
// 	}

});

function getActiveData() {

   let dataArray = [];

     jQuery(".product-categories .active").each(function(){

         dataArray.push(jQuery(this).data("cat-id"));
     });

   return dataArray;
}

function getDataCats(){
	var dataArray = [];
	jQuery('.product-categories .list-link.active').each(function () {
	     var value = jQuery(this).attr('data-cat-id');
	     dataArray.push(value);
	});
	return dataArray;
}


function checkPosition(scroll){

    scroll.on("scroll",(instance) => {

	    //console.log(instance.scroll.y);

	    if (instance.scroll.y > 260) {
	      
	      // jQuery("#main-navigation .navbar-bottom").addClass("scrolled");
  
	    }else{

	      // jQuery("#main-navigation .navbar-bottom").removeClass("scrolled");

	    }   

    });

    scroll.update();

} 

