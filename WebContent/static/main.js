$(document).ready(function(){
	
	$(".navbar-toggle").click(function(){

	})

	
	/* ==================== menu ===================== */				
	$('#header .navbar-toggle').on('click', function(){
		var menu = $("#header .menu-wrapper");
			
		if (menu.hasClass('open')) {
			menu.removeClass('open');
		}
		else {
			menu.addClass('open');
		}		
	});
	
	
	$('#header .menu-wrapper').on('click', function(){
		var menu = $('#header .menu-wrapper');		
		if (menu.hasClass('open')) {
			menu.removeClass('open');
		}
		else {
			menu.addClass('open');
		}
		
	});	
	
	
	/*==================== front Page Slider =====================*/
	
	  $("#front-page-slide-owl").owlCarousel({			  
	      slideSpeed : 20,
	      paginationSpeed : 400,
	      singleItem:true,
	      autoPlay: true,
	      stopOnHover: true,
	      lazyLoad: true,
	      afterInit  : function callBack() {	        	  
	          var content =  $(".item .info-content");
	          content.delay(300).animate({"top": "15%", opacity: 1}, "slow");		
	      },
	      afterMove : function callBack() {	        	  
	          var content =  $(".item .info-content");
	          content.delay(300).animate({"top": "15%", opacity: 1}, "slow");		
	      }
	  });

});