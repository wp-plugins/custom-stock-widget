jQuery(document).ready(function(){
	var admin_options_toggle=jQuery('.admin_toggle');
	var option_display=jQuery('.widget-options-display');
	option_display.toggle();
	var toggle_option=function(target){
		if(target.text()=="+"){
			target.text('-');
		}else{
			target.text('+');
		}
		target.next('.widget-options-display').toggle(200);
	}
	admin_options_toggle.click(function(){
		toggle_option(jQuery(this));
	});

	

});
