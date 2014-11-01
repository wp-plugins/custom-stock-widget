<?php

define('STOCK_PLUGIN_UTILS', true, false); //flag for whether this file was already included anywhere

//helper function for all min/max integers
function stock_plugin_validate_integer($new_val, $min_val, $max_val, $default) {
   if (!is_numeric($new_val)) { return $default; }

   return min(max((integer)$new_val, $min_val), $max_val);
}

function stock_plugin_validate_font_family($new_val, $default) {
   // FOR FUTURE: add in valid font settings: arial, times, etc
   if (empty($new_val))      { return $default; }
   if (is_numeric($new_val)) { return $default; } //throw it out if its a number
   return $new_val; 
}

//for all color settings
function stock_plugin_validate_color($new_val, $default) {
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

function stock_plugin_validate_opacity($new_val, $default) {
   //expected float value
   if (!is_numeric($new_val)) { return $default; }

   return min(max((float)$new_val, 0), 1);
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// for the category stock list portion of the admin UI
// drawing to screen and updating
function stock_plugin_create_category_stock_list($id, $stocks_string) { //this is a helper function for stock_plugin_create_per_category_stock_lists()
    $name = ($id == 'default') ? 'Default' : get_cat_name($id);
    echo <<<LABEL
        <label for="input_{$id}_stocks">{$name}</label><br/>
        <input id="input_{$id}_stocks" name="stocks_for_{$id}" type="text" value="{$stocks_string}" style="width:100%; font-size:14px" />
        
LABEL;
}

//Generates the html input lines for the list of stocks in each category
function stock_plugin_create_per_category_stock_lists($plugin_type) { //plugin_type = widget/ticker
    
    $per_category_stock_lists = get_option("stock_{$plugin_type}_per_category_stock_lists"); 
    //this is a sparce array indexed by category ID, the values will be a string of stocks
    // Array('default'=>'blah,blah,blah', '132'=>'foo,bar') etc
    
    stock_plugin_create_category_stock_list('default', $per_category_stock_lists['default']);
    echo "<br/><span style='font-weight:bold;'>WARNING:</span><br/>If Default is blank, Stock {$plugin_type}s on pages without categories will be disabled.<br/>";
    
    $category_terms = get_terms('category');
    if (count($category_terms)) { //NOTE: this may display without any categories below IF and only if there is only the uncategorized category
        echo "<h4 style='display:inline-block;'>Customize Categories</h4><div id='category_toggle' class='section_toggle'>+</div>
              <div class='section-options-display'>
                <p>If a custom stock list for a category is defined below, 
                Any article/page that belongs to that category will attempt to use the custom stock list for that category instead of the default.
                An article/page that belongs to multiple categories will merge multiple stock lists together</p>";
        
        foreach ($category_terms as $term) {
            if ($term->slug == 'uncategorized') { continue; }
            $cat_id = $term->term_id; 
            $stocks_string = (array_key_exists($cat_id, $per_category_stock_lists) ? $per_category_stock_lists[$cat_id] : '');
            stock_plugin_create_category_stock_list($cat_id, $stocks_string);
        }
        echo "</div>";
    }
    else {
        echo "<p> Your site does not appear to have any categories to display.</p>";
    }
}


function stock_plugin_get_post_vars_for_category_stock_lists() {
    $arr_result = array(); //to be returned
    foreach ($_POST as $key => $value) {    
        if(substr($key, 0, 11)  == 'stocks_for_') {
             $arr_result[substr($key, 11)] = $value; //use the portion of the key that isn't stocks_for_
        }
    }
    return $arr_result;
}

function stock_plugin_update_per_category_stock_lists($plugin_type) { //plugin_type = widget/ticker

    //Start with what is already in the database, so that we don't erase what is there in the case where categories get removed then added back in later
    $per_category_stock_lists  = get_option("stock_{$plugin_type}_per_category_stock_lists", array()); //defaults to empty array
    $all_stock_list            = array();
    $category_stock_input_list = stock_plugin_get_post_vars_for_category_stock_lists();
    
    foreach ($category_stock_input_list as $key => $value) {
        if (empty($value)) {
            $per_category_stock_lists[$key]  = $value;  //final string value and nothing more needed
            $category_stock_input_list[$key] = array(); //for future
        }
        else {
            $stock_str = preg_replace('/\s+/', '', strtoupper($value)); //capitalize the stock values, and remove spaces
            $stock_arr = explode(',', $stock_str);
            $category_stock_input_list[$key] = $stock_arr; //replace the string with an array for future use
            $all_stock_list = array_merge($all_stock_list, $stock_arr);
        }
    }
    $cache_output = stock_plugin_get_data(array_unique($all_stock_list)); //from stock_plugin_cache.php
    $invalid_stocks = $cache_output['invalid_stocks']; //we only need the invalid_stocks for validation
    foreach ($category_stock_input_list as $key => $stock_list) {
        //remove any invalid_stocks from the stock_list, then convert back to string for storage
        $per_category_stock_lists[$key] = implode(',', array_diff($stock_list, $invalid_stocks)); //NOTE: we need to do this even if invalid stocks are empty
    }
    echo "<p style='font-size:14px;font-weight:bold;'>The following stocks were not found:" . implode(', ', $invalid_stocks) . "</p>";
    update_option("stock_{$plugin_type}_per_category_stock_lists", $per_category_stock_lists); //shove the updated option back into database
}


// for updating old format to new format
function stock_plugin_convert_old_category_stock_list($plugin_type) { //plugin_type = widget/ticker
    //Assert valid options for plugin_type?
    $new_category_stock_list = array();
    $old_category_stock_list = get_option("stock_{$plugin_type}_category_stock_list");
    $category_terms          = get_terms('category');
    foreach ($old_category_stock_list as $old_category => $old_stock_list) {
        if ($old_category == 'Default') { $new_category = 'default'; }
        else {
            foreach ($category_terms as $term) {
                if (preg_replace('/\s+/', '', $term->name) == $old_category) {
                    $new_category = $term->term_id;
                    break; //break out of inner loop
                }
            }
        }
        //NOTE: if we didn't find a new_category, just throw it out and continue
        $new_stock_list = implode(',', $old_stock_list);
        $new_category_stock_list[$new_category] = $new_stock_list;
    }
    
    update_option("stock_{$plugin_type}_per_category_stock_lists", $new_category_stock_list); //can't use add because add would have run on initialize
    delete_option("stock_{$plugin_type}_category_stock_list");
}

?>
