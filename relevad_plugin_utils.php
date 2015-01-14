<?php

define('RELEVAD_PLUGIN_UTILS', true, false); //flag for whether this file was already included anywhere

//helper function for all min/max integers
function relevad_plugin_validate_integer($new_val, $min_val, $max_val, $default) {
   if (!is_numeric($new_val)) { return $default; }

   return min(max((integer)$new_val, $min_val), $max_val);
}

function relevad_plugin_validate_font_family($new_val, $default) {
   // FOR FUTURE: add in valid font settings: arial, times, etc
   if (empty($new_val))      { return $default; }
   if (is_numeric($new_val)) { return $default; } //throw it out if its a number
   return $new_val; 
}

//for all color settings
function relevad_plugin_validate_color($new_val, $default) {
   $valid_color_strings = explode(' ', 'Transparent Aliceblue Antiquewhite Aqua Aquamarine Azure Beige Bisque Black Blanchedalmond Blue Blueviolet Brown Burlywood Cadetblue Chartreuse Chocolate Coral Cornflowerblue Cornsilk Crimson Cyan Darkblue Darkcyan Darkgoldenrod Darkgray Darkgreen Darkkhaki Darkmagenta Darkolivegreen Darkorange Darkorchid Darkred Darksalmon Darkseagreen Darkslateblue Darkslategray Darkturquoise Darkviolet Deeppink Deepskyblue Dimgray Dodgerblue Firebrick Floralwhite Forestgreen Fuchsia Gainsboro Ghostwhite Gold Goldenrod Gray Green Greenyellow Honeydew Hotpink Indianred Indigo Ivory Khaki Lavender Lavenderblush Lawngreen Lemonchiffon Lightblue Lightcoral Lightcyan Lightgoldenrodyellow Lightgreen Lightgrey Lightpink Lightsalmon Lightseagreen Lightskyblue Lightslategray Lightsteelblue Lightyellow Lime Limegreen Linen Magenta Maroon Mediumauqamarine Mediumblue Mediumorchid Mediumpurple Mediumseagreen Mediumslateblue Mediumspringgreen Mediumturquoise Mediumvioletred Midnightblue Mintcream Mistyrose Moccasin Navajowhite Navy Oldlace Olive Olivedrab Orange Orangered Orchid Palegoldenrod Palegreen Paleturquoise Palevioletred Papayawhip Peachpuff Peru Pink Plum Powderblue Purple Red Rosybrown Royalblue Saddlebrown Salmon Sandybrown Seagreen Seashell Sienna Silver Skyblue Slateblue Slategray Snow Springgreen Steelblue Tan Teal Thistle Tomato Turquoise Violet Wheat White Whitesmoke Yellow YellowGreen');
   // FOR FUTURE: Add in ability to handle rgb(255,0,0)  and rgba(255,0,0,0.3)  hsl(120,100%,50%)  hsla(120,100%,50%,0.3) ??
   if (substr($new_val, 0, 1) == '#') { //if its in hex format
       if (!ctype_xdigit(substr($new_val, 1))) { return $default; }
       $tmp = strlen($new_val);
       if ($tmp < 4 || $tmp > 7 )              { return $default; } //#ff99bb or #f9b are both valid and mean the same thing
       
       return strtoupper($new_val);
   }
    
    $new_val = ucwords($new_val); //make the first letter uppercase before comparison
    if (!in_array($new_val, $valid_color_strings)) {
        return $default;
    }

    return $new_val;
}

function relevad_plugin_validate_opacity($new_val, $default) {
   //expected float value
   if (!is_numeric($new_val)) { return $default; }

   return min(max((float)$new_val, 0), 1);
}



function relevad_plugin_add_menu_section() {
    if (!defined('RELEVAD_PLUGIN_MENU') ) {
        add_object_page(   'Relevad Plugins', 'Relevad Plugins', 'manage_options', 'relevad_plugins', 'relevad_plugin_welcome_screen' ); //this function is just a welcome screen thingy
        //add_object_page( $page_title,        $menu_title,          $capability,       $menu_slug,        $function,                      $icon_url )
        
        //Dummy so that we don't get an automatic submenu option of the above
        add_submenu_page('relevad_plugins', 'Relevad Plugins', 'Welcome', 'manage_options', 'relevad_plugins', 'relevad_plugin_welcome_screen' );
        
        define('RELEVAD_PLUGIN_MENU', true, false); //flag for whether this menu has already been added
    }
}

function relevad_plugin_welcome_screen() {
    echo <<<HEREDOC
<div id="sp-options-page">

    <h1>Relevad Plugins</h1>
    <p>The following plugins by Relevad are active.</p>
    <ul>
HEREDOC;
    
    $active_plugins = get_option('active_plugins');
    if ( in_array('custom-stock-ticker/stock_ticker_admin.php', $active_plugins)) {
        echo "<li><a href='/wp-admin/admin.php?page=stock_ticker_admin'>Custom Stock Ticker</a></li>";
    }
    if ( in_array('custom-stock-widget/stock_widget_admin.php', $active_plugins)) {
        echo "<li><a href='/wp-admin/admin.php?page=stock_widget_admin'>Custom Stock Widget</a></li>";
    }
    if ( in_array('fit-my-sidebar/fit-my-sidebar.php', $active_plugins)) {
        echo "<li><a href='/wp-admin/admin.php?page=fms_admin_config'>Fit My Sidebar</a></li>";
    }
    echo <<<HEREDOC
    </ul>
</div><!-- end options page -->
HEREDOC;
}

?>
