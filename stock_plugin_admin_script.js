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
    var sec = readCookie(sectionToToggle);
    if (sec) {                //if cookie has a value
        if (sec == "none") {  //if the section is collapsed when clicked
            document.cookie = sectionToToggle + "=block";  //set the new cookie state to uncollpased
        } else {              //if the section is not collapsed when clicked
            document.cookie = sectionToToggle + "=none";   //set the new cookie state to collpased
        }
    } else {                //if cookie doesn't have a value
        document.cookie = sectionToToggle + "=block";      //user clicked a section for the first time, it is now uncollapsed
    }
}

function fadeNotification() {
    // jQuery('.updated').delay(5000).animate({height:'hide', marginTop:'hide', marginBottom:'hide'}, 1000)   // slides notifacation up, I dont like how this looks
    jQuery('.updated').delay(5000).fadeTo(1000,0) // fades notification out
}


function swap_layout(new_layout) { // when the user clicks a radio button for a new layout, disable inputs that arent valid for that input
    var heightpx,heightpxlabel,heightnum,heightnumlabel,itmp,ltmp0,ltmp1;
    heightpx  = jQuery('#input_height');      // height in px
    heightpxlabel = jQuery('label[for="input_height"]');
    heightnum = jQuery('#input_max_display'); // height in stocks (number of stocks to display)
    heightnumlabel = jQuery('label[for="input_max_display"]');
    switch(new_layout) {
        case 1: itmp = [true,true]; break; // true = disable input
        case 2: itmp = [false,false]; break; // false = enable input
        case 3: itmp = [false,false]; break;
        case 4: itmp = [false,true]; break;
    }
    heightpx.prop('disabled',itmp[0]);
    heightnum.prop('disabled',itmp[1]);
    ltmp0 = (itmp[0] ? 0.2 : 1);
    ltmp1 = (itmp[1] ? 0.2 : 1);
    heightpxlabel.css({opacity:ltmp0});
    heightnumlabel.css({opacity:ltmp1});
}

function toggle_suboption(button,target,invert) { // options page dependency function, button is the checkbox, target is a css class containing all lables & inputs for dependants
    var target_input = jQuery(target).filter('input'); // seperate inputs from labels
    var target_label = jQuery(target).filter('label'); 
    button = jQuery(button);  // This has to be a jQuery object
    var status = (!invert) ? button.prop('checked') : !button.prop('checked'); // If invert is true, status is opposite of the button state
    target_input.prop('disabled', status); // Set disabled to equal status
    if (status) {
        target_label.addClass("label_disabled");
    } else {
        target_label.removeClass("label_disabled");
    }
}

//TODO - move this to the top, inside the document ready up there?
jQuery(document).ready(function() {
    var d_list =[['#input_text_color_change','.disable_text',           false],
                 ['#input_bg_color_change',  '.disable_bg',             false],
                 ['#input_show_header',      '.disable_header',         true],
                 ['#input_stock_symbol',     '.disable_stock_symbol',   true],
                 ['#input_last_value',       '.disable_last_val',       true],
                 ['#input_change_value',     '.disable_change_value',   true],
                 ['#input_change_percent',   '.disable_change_percent', true]];

    for (var index = 0; index < d_list.length; ++ index) {
        toggle_suboption(jQuery(d_list[index][0]), d_list[index][1],d_list[index][2]); // run the function once on pageload
        jQuery(d_list[index][0]).change(d_list[index], function(event) {               // and register it to the .change event handler for future changes
            toggle_suboption(this,event.data[1],event.data[2])
        });
    }
});

