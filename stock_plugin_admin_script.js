jQuery(document).ready(function() {
    var admin_options_toggle = jQuery('.section_toggle');
    var option_display = jQuery('.section-options-display');
    //option_display.toggle();
	

    var toggle_option = function(target) {
        if(target.text() == "+"){
            target.text('-');
        }else{
            target.text('+');
        }
        target.next('.section-options-display').toggle(200);
    }
    admin_options_toggle.click(function(event) {
        toggle_option(jQuery(this));
		toggleSection(event.target.id);
    });
});

function enhanceTypeRange(rangeId, rangeTextId) {
	var html5Range = document.createElement("input"); // test compatibility for html5 range input attribute
	html5Range.setAttribute("type", "range");
	html5Range = html5Range.type !== "text";
	if(html5Range === true) { // if range attribute is supported, change opacity text inputs into opacity range (slider) inputs
		var typeRange = document.getElementById(rangeId);
		typeRange.setAttribute("step", "0.05"); // default step is 1, not useful for opacity slider
		typeRange.setAttribute("min", "0"); // 0 percent opacity is 0
		typeRange.setAttribute("max", "1"); // 100 percent opacity is 1
		typeRange.setAttribute("type", "range"); // type is set to range last due to compatibility issues
		typeRange.style.width="130px";
		document.getElementById(rangeTextId + "0").innerHTML = ""; // clean up 0-1 span from text box
		document.getElementById(rangeTextId + "1").innerHTML = "0%"; // add in values on either end of the slider for clarity
		document.getElementById(rangeTextId + "2").innerHTML = "100%";
	}
}

function enhanceTypeColor(colorId, colorTextId) {
	var html5Color = document.createElement("input"); // test compatibility for html5 color input attribute
	html5Color.setAttribute("type", "color");
	html5Color = html5Color.type !== "text";
	
	if(html5Color === true) { // if color attribute is supported, change color text inputs into html5 color pickers
		document.getElementById(colorId).setAttribute("type", "color");
		document.getElementById(colorTextId).innerHTML = "";
	}
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}


function toggleSection(sectionToToggle) {
    sec = readCookie(sectionToToggle);
    if (sec) {                //if cookie has a value
        if (sec == "none") {  //if the section is collapsed when clicked
            document.cookie = sectionToToggle + "=block";  //set the new cookie state to uncollpased
        } else {              //if the section is not collapsed when clicked
            document.cookie = sectionToToggle + "=none";   //set the new cookie state to collpased
        }
    } else {                //if cookie doesn't have a value
        document.cookie = sectionToToggle + "=block";	   //user clicked a section for the first time, it is now uncollapsed
    }
}