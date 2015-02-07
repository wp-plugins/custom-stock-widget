<?php
namespace stockWidget;
//define('STOCK_PLUGIN_UTILS', true, false); //flag for whether this file was already included anywhere

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// for the category stock list portion of the admin UI
// drawing to screen and updating
function stock_plugin_create_category_stock_list($id, $stocks_string) { //this is a helper function for stock_plugin_create_per_category_stock_lists()
    $name = ($id == 'default') ? 'Default' : get_cat_name($id);
    echo <<<LABEL
        <label for="input_{$id}_stocks">{$name}</label><br/>
        <input id="input_{$id}_stocks" name="stocks_for_{$id}" type="text" value="{$stocks_string}" style="width:100%;"/>
        
LABEL;
}

function stock_plugin_cookie_helper($subsection, $plugin_type) {
    $the_cookie = (isset ($_COOKIE["{$plugin_type}sec"][$subsection]) ? $_COOKIE["{$plugin_type}sec"][$subsection] : 'none');
    echo "<div class='section_toggle' id='{$plugin_type}sec[{$subsection}]'>";
    if ($the_cookie == "none") {
        echo "+</div>";
    } else {
        echo "-</div>";
    }
    echo "<div class='section-options-display' style='display:".$the_cookie."';>";
}



//Generates the html input lines for the list of stocks in each category
function stock_plugin_create_per_category_stock_lists($plugin_type) { //plugin_type = widget/ticker
    
    $per_category_stock_lists = get_option("stock_{$plugin_type}_per_category_stock_lists"); 
    //this is a sparce array indexed by category ID, the values will be a string of stocks
    // Array('default'=>'blah,blah,blah', '132'=>'foo,bar') etc
    
    stock_plugin_create_category_stock_list('default', $per_category_stock_lists['default']);
    if (empty($per_category_stock_lists['default'])) { //only display this if the default stock list is actually empty
        echo "<br/><span style='font-weight:bold;'>WARNING:</span><br/>If Default is blank, Stock {$plugin_type}s on pages without categories will be disabled.<br/>";
    }
    
        
    $category_terms = get_terms('category');
    if (count($category_terms)) { //NOTE: this may display without any categories below IF and only if there is only the uncategorized category
        echo "<h4 style='display:inline-block;'>Customize Categories</h4>";
                stock_plugin_cookie_helper(0, $plugin_type);
                    echo "<p><b>Optional:</b><br />
                    Use this section to display specific stocks for posts/pages in specific categories.<br />
                    If a post belongs to multiple categories, the plugin will merge the stocks specified for those categories.<br />
                    If you leave a field blank, the category will use the default stock list specified above.</p>";
        
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
    if (!empty($invalid_stocks)) {
        echo "<p style='font-size:14px;font-weight:bold;'>The following stocks were not found (and were automatically removed):<br />" . implode(', ', $invalid_stocks) . "</p>";
    }
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

//helper functions for using the table storage. 
function convert_data_display($to_convert) {
    //expects to convert an array of bits into an integer representation and vice versa
    //array(0,1,1,1,1,0), //    32,16,8,4,2,1
    if (is_array($to_convert)) {
        return bindec(implode('', $to_convert));
    }
    elseif (is_int($to_convert)) {
        $num = 6; //NOTE: this will need to be updated if we add more options to this flags array
        $tmp = sprintf( "%0{$num}d", decbin($to_convert));
        return str_split($tmp);
    }
    else
        return false; //for error detection
}

//Drop in replacements for add_option, get_option, update_option, delete_option
function sp_add_row($table_name, $values) {
    global $wpdb;
    
    if (is_array($values['data_display'])) { //if this is not defined it is false
        $values['data_display'] = convert_data_display($values['data_display']);
    }
    
    //insert(table_name, data)
    $status = $wpdb->insert($table_name, $values);
    //NOTE: if we need the new auto_increment id its here $wpdb->insert_id
    //false on error, otherwise returns 1 row updated
    return $status;
}

//Function should expect the array parameters same as update_option()
function sp_update_row($table_name, $name, $values) {
    global $wpdb;
    
    if (is_array($values['data_display'])) {
        $values['data_display'] = convert_data_display($values['data_display']);
    }
    
    //update(table_name, data, where)
    $status = $wpdb->update($table_name, $values, array('name' => $name));
    //false on error, number of rows updated otherwise
    return $status;
}

function sp_get_row($table_name, $name) {
    global $wpdb;
    
    $sql = "SELECT * FROM {$table_name} WHERE name = '{$name}'";
    $result = $wpdb->get_row($sql, ARRAY_A);
    
    //NOTE: everything that comes out of mysql is a string -- is there anything else I need to convert?
    $result['data_display'] = convert_data_display((int)$result['data_display']);

    //print_r($result);
    return $result; //don't forget to check for NULL if nothing returned
}

//to implement later
function sp_multi_get_row($table_name, $name) { //how do we want to retrieve these? what options? Sorting etc
    global $wpdb;
    
    
}

//to implement later
function sp_delete_row($table_name, $name) {
    global $wpdb;
    
    //NOTE: do not allow id=1 to be deleted, this one is special
}

//to implement later
function sp_clone_row() { //for copying a row as baseline for a new one
    global $wpdb;
}

?>
