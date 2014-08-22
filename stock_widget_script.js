jQuery(document).ready(function(){
	var stock_table=jQuery('.stock_table');
/*	var stock_row=jQuery('.stock_table_row');
	var ticker_offset=stock_table.offset();
	var ticker_height=stock_table.height();
	var dim = function(thing){

		jQuery(thing).fadeOut(50);
		unDim(thing);
	};

	var unDim = function(thing){

		jQuery(thing).fadeIn(2000);
		dim(thing);
	};
	
	var scroll_up=function(target_element, time){
		var row_offset=target_element.offset();
		var row_height=target_element.height();
		var distance=(row_offset.top-ticker_offset.top);
		var fraction= distance/ticker_height;
		target_element.animate({
		top: "-="+distance
		},time*fraction,'linear');
		var slide_time=row_height*time/ticker_height;
		target_element.slideUp(slide_time,'linear');
	};*/
	stock_table.fadeIn('slow');

//		scroll_up(jQuery(this),10000);
	//})
	

});
