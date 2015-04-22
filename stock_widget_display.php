<?php
namespace stockWidget;

//NOTE: so long as plugin is activated, these will be included regardless of whether the shortcode is on the page
function stock_widget_scripts_enqueue($force = false) {
    $current_version = SP_CURRENT_VERSION;

    if (is_admin() && !$force) { return; } //skip enqueue on admin pages except for the config page
    
    wp_register_style ('stock_widget_style',  plugins_url('stock_widget_style.css', __FILE__), false, $current_version);
    wp_register_style ('data_tables_style',  '//cdn.datatables.net/1.10.6/css/jquery.dataTables.css', false, '1.10.6'); //TODO - use min version (.min.css)
    wp_enqueue_style ('stock_widget_style');
    wp_enqueue_style ('data_tables_style');
    wp_enqueue_script('stock_widget_script', plugins_url('/stock_widget_script.js', __FILE__), array(), null);
    wp_enqueue_script('jquery.dataTables', '//cdn.datatables.net/1.10.6/js/jquery.dataTables.js', array(), '1.10.6'); // TODO - use min version (.min.js)

    if (is_admin()) { return; } //only run this on regular pages
    $feed_tag = ( !array_key_exists('reletime', $_COOKIE)  ? "?ft=customstockwidget" : "");
    wp_enqueue_script('ipq', "http://websking.com/static/js/ipq.js{$feed_tag}", array(), null, true); //skipping register step
}
add_action('wp_enqueue_scripts', NS.'stock_widget_scripts_enqueue');


add_shortcode('stock-widget', NS.'stock_widget');


function stock_widget($atts) {
    
    stock_widget_handle_update();
    
    extract( shortcode_atts( array(
        'name'              => 'Default Settings'
    ), $atts ) );

    $shortcode_settings = sp_get_row($name);

    if ($shortcode_settings === null) {
        return "<!-- WARNING: no shortcode exists with name '{$name}' -->";
    }
    $output = "";
    
    if ($name === 'Default Settings' || $shortcode_settings['stock_list'] === '') {
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
            //$cats_used = '';
            foreach ($category_ids as $cat) { //merge multiple stock lists together if post is in multiple categories
                $stocks_arr = (array_key_exists($cat, $per_category_stock_lists) && !empty($per_category_stock_lists[$cat]) ? explode(',', $per_category_stock_lists[$cat]) : array() );
                $stock_list = array_merge($stocks_arr, $stock_list); //REM: take a unique later
            }
            if (empty($stock_list)) {
                $stock_list = $default_stock_list;
            }
            $cats_used = "for category: " . implode(',', $category_ids);
        }
        

    }
    else {
        $stock_list = explode(',', $shortcode_settings['stock_list']);
        $cats_used = "";
    }

    $tmp = stock_plugin_get_data(array_unique($stock_list)); //from stock_plugin_cache.php, expects an array or string | separated
    $stock_data_list = array_values($tmp['valid_stocks']);   //NOTE: its ok to throw away the keys, they aren't used anywhere
    
    if (empty($stock_data_list)) {
        return "<!-- WARNING: no stock list found {$cats_used} -->";  //don't fail completely silently
    }

    $num_to_display = min(count($stock_data_list), $shortcode_settings['display_number']); // FIX ME
    
    $output .= stock_widget_create_css_header($shortcode_settings, $num_to_display);
    $output .=      stock_widget_create_table($shortcode_settings, $stock_data_list, $num_to_display);
    return $output;
}

//Creates the internal style sheet for all of the various elements.
function stock_widget_create_css_header($shortcode_settings, $num_to_display) {

        $number_of_elements = array_sum($shortcode_settings['data_display']);
        
        //variables to be used inside the heredoc
        $id             = $shortcode_settings['id']; //we don't want to use the name because it might have spaces
        $width          = $shortcode_settings['width'];
        $height         = $shortcode_settings['height'];
        $text_color     = $shortcode_settings['font_color'];
        $bgcolor1       = $shortcode_settings['bg_color1'];
        $bgcolor2       = $shortcode_settings['bg_color2'];
        $bgcolor3       = $shortcode_settings['bg_color3'];
        // Some layouts require special css
        $paddinghide = 'padding:0px'; $paginationhide = '';
        switch ($shortcode_settings['layout']) {
            case 1:
            case 4:
                $paddinghide = 'padding:8px 10px'; // Layouts 1 and 4 need padding between entries
            break;
            case 2:
                $paginationhide = 'display:none'; // Layout 2 is technially paginated, but we don't want the paging controls
            break;
        }


        //$num_to_display = $shortcode_settings['display_number']; // need to pass in for the header

        //NOTE: rows are an individual stock with multiple elements
        //NOTE: elements are pieces of a row, EX.  widget_name & price are each elements
        // $element_width = round($width  / $number_of_elements, 0, PHP_ROUND_HALF_DOWN); // FIXME - not needed?
        // $row_height    = round($height / $num_to_display,     0, PHP_ROUND_HALF_DOWN); // FIXME - not needed?
        // There may be a bunch of junk here I don't need, I should clean this up
        
        //NOTE: stock_widget_{$id} is actually a class, so we can properly have multiple per page, IDs would have to be globally unique
        return <<<HEREDOC
<style type="text/css" scoped>
div.table_wrapper_{$id}{
   width:           {$width}px;
}

table.stock_widget_{$id} {
   width:            {$width}px;
   height:           {$height}px;
   {$shortcode_settings['advanced_style']}
}
.stock_widget_{$id} .stock_widget_row {
   color:    {$text_color};
}
.stock_widget_{$id} .stock_widget_row a{
   color:    {$text_color};
}
.stock_widget_{$id} .stock_widget_row.odd,
table.stock_widget_{$id}.dataTable.hover tbody tr.odd:hover,
table.stock_widget_{$id}.dataTable.display tbody tr.odd:hover {
    background-color: {$bgcolor1};
}

.stock_widget_{$id} .stock_widget_row.even,
table.stock_widget_{$id}.dataTable.hover tbody tr.even:hover,
table.stock_widget_{$id}.dataTable.display tbody tr.even:hover {
    background-color: {$bgcolor2};
}

.stock_widget_{$id} .stock_header {
    background-color: {$bgcolor3};
}

.stock_widget_{$id} .stock_widget_element {
   font-size:   {$shortcode_settings['font_size']}px;
   font-family: {$shortcode_settings['font_family']},serif;
}

.stock_widget_{$id} + .dataTables_paginate {
    {$paginationhide}
}

table.dataTable tbody th,
table.dataTable tbody td {
    {$paddinghide}
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


//function stock_widget_create_table($name, $stock_data_list, $max_stocks, $width, $height, $text_color, $background_color1, $background_color2) {
function stock_widget_create_table($sw_settings, $stock_data_list, $number_of_stocks) {
    
    $id = $sw_settings['id']; //we don't want to use the name because it might have spaces
    $output = '';
    
    if ($number_of_stocks == 0) { //some kinda error
        return "<!-- we had no stocks for some reason, stock_data_list empty -->";
    }
    
    $number_of_elements = array_sum($sw_settings['data_display']); //for each stock row
    $hide_header = ($sw_settings['show_header'] == 1 ? '' : 'sw_hidden');
    
    $output .= "<thead class='stock_widget_row stock_header {$hide_header}'><tr>";
        $column_headers = array("Market", "Symbol", "Last Value", "Value Change", "% Change", "Last Trade"); //Feature Improvement, handle the header text better with sizing, maybe overflow hidden?
        while ( list($idx, $v) = each($sw_settings['data_display']) ) {
            if ($v == 1) {
                $output .= "<th class='stock_widget_element'>" . $column_headers[$idx] . "</th>";
            }
        }
        $output .= "</tr></thead><!-- \n -->";
    
    $output .= "<tbody>";
    
    foreach ($stock_data_list as $stock_data) { // we always print every stock so that datatables can trim it as neccessary
        $output .= stock_widget_create_row($stock_data, $sw_settings);
    }
    $the_jquery =  stock_widget_create_jquery($sw_settings);
    // datatables additional styling options can be applied as classes to the <table> tag
    // if we add in more styling options they should be added here
    $row_borders = ($sw_settings['draw_row_borders'] == 1 ? 'row-border' : '');
    $cell_borders = ($sw_settings['draw_cell_borders'] == 1 ? 'cell-border' : '');
    // $hover =  ($shortcode_settings['hover_highlight'] == 1 ? 'hover' : '');   // for if we add in hover option
    return "<div class='table_wrapper_{$id}'><table class='stock_table stock_widget_{$id} {$row_borders} {$cell_borders} hover'>{$output}</tbody></table></div>
    {$the_jquery}";
}

function stock_widget_create_jquery($shortcode_settings) {
        $json_settings = json_encode($shortcode_settings);
        return <<<JQC
        <script type="text/javascript">
              var tmp = document.getElementsByTagName( 'script' );
              var thisScriptTag = tmp[ tmp.length - 1 ];
              var widget_root = jQuery(thisScriptTag).parent().find('table.stock_table');
              var widget_config = {$json_settings};
              stock_widget_datatables_init(widget_root, widget_config);
        </script>
JQC;
}

function stock_widget_create_row($stock_data, $sw_settings) {
    if(empty($stock_data['last_val'])) {
        return "<!-- Last Value did not exist, stock error ({$stock_data['stock_sym']})-->";
    }
    $output = "";
    
    ////////// color change definition //////////
    $valchk = $stock_data['change_percent'];
    if ($valchk > 2)     {$changer = 'sw_green_big';}
    elseif ($valchk > 1) {$changer = 'sw_green_med';}
    elseif ($valchk > 0)    {$changer = 'sw_green_sml';}
    elseif ($valchk < -2){$changer = 'sw_red_big';}
    elseif ($valchk < -1){$changer = 'sw_red_med';}
    elseif ($valchk < 0)    {$changer = 'sw_red_sml';}
    else                    {$changer = 'sw_gray';}
    
    $data_item = $stock_data['change_val'];
    $data_item = round($data_item, 2);
    if ($data_item > 0) {          
        $data_item = "+{$data_item}%";
    } elseif ($data_item < 0) {
        $data_item = "{$data_item}%";
    } else {
        $data_item = "+{$data_item}.00%";
    }
    
    $text_changer = ($sw_settings['auto_text_color'] == 1 ? 'cell_'.$changer : '');
    $row_changer = ($sw_settings['auto_background_color'] == 1 ? 'row_'.$changer : '');
    
    ////////// end color change def //////////
    
    $link_wrap_1 = "";
    $link_wrap_2 = "";
    $link_url = $sw_settings['stock_page_url'];
    if ($link_url) {
        $link_url = str_replace('__STOCK__', $stock_data['stock_sym'], $link_url);
        $link_wrap_1 = "<a href='{$link_url}' target='_blank' rel='external nofollow'>";
        $link_wrap_2 = "</a>";
    }
    
    $output .= "<tr class='stock_widget_row {$row_changer} {$text_changer}'>";
   
    //data display option: (Market, Symbol, Last value, change value, change percentage, last trade)
    $display_options = $sw_settings['data_display'];
    
    
    //NOTE: skip market
    
    //NOTE: always display stock symbol
    $data_item = $stock_data['stock_sym'];
    if($data_item == "^GSPC") { //change the special index symbols to something more common knowledge
        $data_item = "S&P500";
    } elseif($data_item == "^IXIC") {
        $data_item = "NASDAQ";
    }
    $output.= "<td class='stock_widget_element'>{$link_wrap_1}{$data_item}{$link_wrap_2}</td><!-- \n -->";

    if ($display_options[2] == 1) {
        $data_item = $stock_data['last_val'];
        $data_item = round($data_item, 2); //yahoo only gives 2 decimal places precision in most cases.
        $output   .= "<td class='stock_widget_element'>{$data_item}</td><!-- \n -->";
    }
    
    $wrap1 = "<td class='stock_widget_element'>";
    $wrap2 = "</td>";
    
    if ($display_options[3] == 1) {
        $data_item = $stock_data['change_val']; // TODO - FIX THIS : i think i messed this up
        $output .= "{$wrap1}{$data_item}{$wrap2}";
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
        // if ($widget_change_style == 'Parentheses') { // TODO - maybe add this back in?
        //    $data_item = "({$data_item})"; // wrap in parentheses
        //}
        $output .= "{$wrap1}{$data_item}{$wrap2}<!-- \n -->";
    }
    //NOTE: skip the last trade section
    // $output .= "<td>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</td>";

    $output .= "</tr>"; //closes the .stock_widget_row

    return $output;
}
?>
