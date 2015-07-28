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
	

});