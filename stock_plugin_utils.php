<?php

//helper function for all min/max integers
function stock_plugin_validate_integer($new_val, $max_val, $min_val, $default) {
    if (!is_numeric($new_val)) { return $default; }
    
    return min(max((integer)$new_val, $min_val), $max_val);
}


function stock_plugin_validate_max_display($new_val, $default){
    return stock_plugin_validate_integer($new_val, 20, 1, $default);
}

function stock_plugin_validate_scroll_speed($new_val, $default) {
    return stock_plugin_validate_integer($new_val, 150, 1, $default);
}

function stock_plugin_validate_display_width($new_val, $default) {
    return stock_plugin_validate_integer($new_val, 2000, 200, $default); //NOTE: pulling these minimums out of thin air
}
function stock_plugin_validate_display_height($new_val, $default) {
    return stock_plugin_validate_integer($new_val, 100, 10, $default); //NOTE: pulling these minimums out of thin air
}
function stock_plugin_validate_font_size($new_val, $default) {
    return stock_plugin_validate_integer($new_val, 32, 5, $default); //NOTE: pulling these minimums out of thin air
}



function stock_plugin_validate_font_family($new_val, $default) {
    // FOR FUTURE: add in valid font settings: arial, times, etc
    if (empty($new_val))      { return $default; }
    if (is_numeric($new_val)) { return $default; } //throw it out if its a number
    return $new_val; 
}

//for all color settings
function stock_plugin_validate_color($new_val, $default) {
    // FOR FUTURE: allow valid color strings (black, yellow etc)
    if (substr($new_val, 0, 1) != '#')      { return $default; }
    if (!ctype_xdigit(substr($new_val, 1))) { return $default; }
    $tmp = strlen($new_val);
    if ($tmp < 4 || $tmp > 7 )              { return $default; } //#ff99bb or #f9b are both valid and mean the same thing

    return strtoupper($new_val);
}

function stock_plugin_validate_opacity($new_val, $default) {
    //expected float value
    if (!is_numeric($new_val)) { return $default; }
    
    return min(max((float)$new_val, 0), 1);
}

?>

