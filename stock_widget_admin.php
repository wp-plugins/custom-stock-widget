<?php

/*
    Plugin Name: Custom Stock Widget
    Plugin URI: http://relevad.com/wp-plugins/
    Description: Create customizable stock data table widgets that can be placed anywhere on a site using shortcodes.
    Author: Relevad
    Version: 1.0
    Author URI: http://relevad.com/

*/

/*  Copyright 2014 Relevad Corporation (email: stock-widget@relevad.com) 
 
    This program is free software; you can redistribute it and/or modify 
    it under the terms of the GNU General Public License as published by 
    the Free Software Foundation; either version 3 of the License, or 
    (at your option) any later version. 
 
    This program is distributed in the hope that it will be useful, 
    but WITHOUT ANY WARRANTY; without even the implied warranty of 
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
    GNU General Public License for more details. 
 
    You should have received a copy of the GNU General Public License 
    along with this program; if not, write to the Free Software 
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
*/

if (!defined('STOCK_PLUGIN_UTILS') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-widget/stock_plugin_utils.php'; //contains validation functions
}
if (!defined('STOCK_PLUGIN_CACHE') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-widget/stock_plugin_cache.php';
}

include WP_CONTENT_DIR . '/plugins/custom-stock-widget/stock_widget_display.php';

$stock_widget_vp = array( //validation_parameters
'max_display'  => array(1,20),
'width'        => array(100,500),
'height'       => array(100,1000),
'font_size'    => array(5,32),
'change_styles'=> array("None", "Box", "Parentheses")
);

function stock_widget_activate() {
    add_option('stock_widget_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces
    //add_option('stock_widget_default_market',          "DOW");  //unused but maybe in future
    //add_option('stock_widget_all_markets',             array("DOW", "TYO", "LON", "FRA", "SHA"));  //unused but maybe in future

                                                        //data display option: (Market, Symbol, Last value, change value, change percentage, last trade)
    add_option('stock_widget_data_display',             array(0,1,1,1,1,0)); //NOTE: Hardcoded flags for which stock elements to display in a stock entry
    //add_option('stock_widget_display_option_strings',  array("Market", "Symbol", "Last Value", "Change Value", "Change Percentage", "Last Trade"));  //In Future may allow user config

    add_option('stock_widget_max_display',             5);        //controls the maximum number of stocks displayed
    add_option('stock_widget_display_type',            "Preset"); //controls the order in which the stocks are displayed

    //NOTE: border color is not used anywhere
    add_option('stock_widget_color_scheme',            array("#5DFC0A", "#5DFC0A", "#000000", "#7F7F7F")); //[Text, Border, Background1, Background2]
    add_option('stock_widget_display_size',            array(300, 70));     //(width, height)

    add_option('stock_widget_font_options',            array(12, "Times")); //(size, family)
    //add_option('stock_widget_default_fonts',           array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times"));

    add_option('stock_widget_draw_vertical_dash',      false);
    add_option('stock_widget_draw_horizontal_dash',    false);

    //add_option('stock_widget_available_change_styles', array("None", "Box", "Parentheses"));
    add_option('stock_widget_change_style',            "Box");

    add_option('stock_widget_advanced_style',          "margin: auto;");
    add_option('stock_page_url',                       "https://www.google.com/finance?q=__stock__");

//Holds the default settings
    add_option('stock_widget_default_settings', array(
        'Classic' => array(
            'name'         => 'Classic (black/white)', 
            'font_family'  => 'Arial', 
            'font_color'   => '#FFFFFF', 
            'back_color1'  => '#000000',
            'back_color2'  => '#000000', 
            'hori_lines'   => true, 
            'verti_lines'  => false, 
            'check_box'    => 'Box'),
        'Ocean' => array(
            'name'         => 'Ocean (purple/blue)', 
            'font_family'  => 'Arial', 
            'font_color'   => '#FFFFFF', 
            'back_color1'  => '#3366CC', 
            'back_color2'  => '#19A3FF', 
            'hori_lines'   => false, 
            'verti_lines'  => true, 
            'check_box'    => 'None'),
        'Matrix' => array(
            'name'         => 'Matrix (green/black)', 
            'font_family'  => 'Arial', 
            'font_color'   => '#66FF33', 
            'back_color1'  => '#000000', 
            'back_color2'  => '#000000', 
            'hori_lines'   => true, 
            'verti_lines'  => false, 
            'check_box'    => 'None'),
        'Minimal' => array(
            'name'         => 'Minimal (transparent/black)', 
            'font_family'  => 'Arial', 
            'font_color'   => '#000000', 
            'back_color1'  => 'transparent', 
            'back_color2'  => 'transparent', 
            'hori_lines'   => true, 
            'verti_lines'  => false, 
            'check_box'    => 'Parentheses'),
        'Cotton Candy' => array(
            'name'         => 'Cotton Candy (pink/purple)', 
            'font_family'  => 'cursive', 
            'font_color'   => '#00FFFF', 
            'back_color1'  => '#FF5050', 
            'back_color2'  => '#CC66FF', 
            'hori_lines'   => true, 
            'verti_lines'  => false, 
            'check_box'    => 'None'),
    ));
}
register_activation_hook( __FILE__, 'stock_widget_activate' );

//*********cleanup and conversion functions for updating versions 1.0 -> 1.1 *********
if (get_option('stock_widget_category_stock_list')) { //this old option exists
    stock_plugin_convert_old_category_stock_list('widget');
    
    $tmp = get_option('stock_widget_data_display');
    update_option('stock_widget_data_display', array_values($tmp));
}


function stock_widget_admin_enqueue($hook) {
    if ($hook != 'settings_page_stock_widget_admin') {return;} //do not run on other admin pages

    wp_register_style ('stock_widget_admin_style',  plugins_url('stock_widget_admin_style.css', __FILE__));
    wp_register_script('stock_widget_admin_script', plugins_url('stock_widget_admin_script.js', __FILE__), array( 'jquery' ), false, false);

    wp_enqueue_style ('stock_widget_admin_style');
    wp_enqueue_script('stock_widget_admin_script');
    
    stock_widget_scripts_enqueue(true); //we also need these scripts
}
add_action('admin_enqueue_scripts', 'stock_widget_admin_enqueue');

function stock_widget_admin_actions(){
    $hook = add_options_page('StockWidget', 'StockWidget', 'manage_options', 'stock_widget_admin', 'stock_widget_admin_page'); //wrapper for add_submenu_page specifically into "settings"
    //add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $menu_slug, $function ); // do not use __FILE__ for menu_slug
}
add_action('admin_menu', 'stock_widget_admin_actions');


function stock_widget_reset_options() {
    update_option('stock_widget_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces
    update_option('stock_widget_default_market',          "DOW"); //unused but maybe in future
    //update_option('stock_widget_all_markets',             array("DOW", "TYO", "LON", "FRA", "SHA"));  //unused maybe in future
                                                          //data display option: (Market, Symbol, Last value, change value, change percentage, last trade)
    update_option('stock_widget_data_display',            array(0,1,1,1,1,0));
    //update_option('stock_widget_display_option_strings',  array("Market", "Symbol", "Last Value", "Change Value", "Change Percentage", "Last Trade"));  //In Future may allow user config

    update_option('stock_widget_max_display',             5);        //controls the maximum number of stocks displayed
    update_option('stock_widget_display_type',            "Preset"); //controls the order in which the stocks are displayed

    update_option('stock_widget_color_scheme',            array("#5DFC0A", "#5DFC0A", "#000000", "#7F7F7F")); //[Text, Border, Background1, Background2]
    update_option('stock_widget_display_size',            array(300, 70));     //(width, height)
    update_option('stock_widget_font_options',            array(12, "Times")); //(size, family)
    //update_option('stock_widget_default_fonts',           array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times"));
    update_option('stock_widget_draw_vertical_dash',      false);
    update_option('stock_widget_draw_horizontal_dash',    false);
    //update_option('stock_widget_available_change_styles', array("None", "Box", "Parentheses"));
    update_option('stock_widget_advanced_style',          "margin: auto;");
    update_option('stock_widget_change_style',            "Box");
    update_option('stock_page_url',                       "https://www.google.com/finance?q=__stock__");
}




/** Creates the admin page. **/
function stock_widget_admin_page(){

    echo <<<HEREDOC
<div id="widget-options-page" style="width:850px;">

    <h1>Custom Stock Widget</h1>
    <p>The Custom Stock Widget plugin allows you to create and run your own custom stock table widgets.</p>
    <p>Choose your stocks and display settings below.<br />
    Then place your the shortcode <code>[stock-widget]</code> inside a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
    Or, you can use <code>&lt;?php echo do_shortcode('[stock-widget]'); ?&gt;</code> inside your theme files or <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.
    </p>
    
HEREDOC;
    
    if (isset($_POST['save_changes'])){
        stock_widget_update_options();   
    } 
    elseif (isset($_POST['reset_options'])) {
        stock_widget_reset_options();
    }
        stock_widget_create_options_config();

    echo <<<HEREDOC
            <div class="postbox-container widget-options" style="display:block; clear:both; width:818px; margin-top:20px;">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <div id="referrers" class="postbox">
                        <h3 class="hndle"><span>Preview</span></h3>
                        <div class="inside">
                           <p>Based on the last saved settings, this is what the default <code>[stock-widget]</code> shortcode will generate:</p>
HEREDOC;
    echo do_shortcode('[stock-widget]');
    $example = "[stock-widget id='example' display='2' width='250' height='90' bgcolor1='#363' bgcolor2='#633' text_color='#ff0' change_style='Parentheses']";
    echo <<<HEREDOC
                           <p>To preview your latest changes to settings, you must first save changes.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="postbox-container widget-options" style="display:block; clear:both; width:818px;">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <div id="referrers" class="postbox">
                        <h3 class="hndle"><span>Advanced</span></h3>
                        <div class="inside">
                            <p>If you want to run a custom style, you can specify the style parameters in the shortcode. See the example below:</p>
                                <textarea onclick="this.select();" readonly="readonly" class="shortcode-in-list-table wp-ui-text-highlight code" style="width: 100%; font-size: smaller;">{$example}</textarea></p>
HEREDOC;
        echo do_shortcode($example);
    echo <<<HEREDOC
                            <p>Note: In order to display stock widgets with different settings on the same page, each <b>must</b> have a unique id assigned in the shortcode.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
</div><!-- end options page -->
HEREDOC;
}

//Creates the entire options page. Useful for formatting.
function stock_widget_create_options_config(){
        echo "<form action='' method='POST'>
             <div class='postbox-container widget-options' style='width: 50%; margin-right: 10px; clear:left;'>
                <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                    <div id='referrers' class='postbox'>
                        <h3 class='hndle'>Default Widget Display Settings</h3>
                        <div class='inside'>";
                            stock_widget_create_template_field();  //this is actually apply template
        echo "              <p>All options below are <b>optional</b>.<br />All are reset by choosing a style above.</p>
                            <div class='widget-options-subsection'>
                                <h4>Widget Config</h4> 
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_widget_config_section();
        echo "                  </div>
                            </div>
                            <div class='widget-options-subsection'>
                                <h4>Text Config</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_text_config();
        echo "                  </div>
                            </div>
                            <div class='widget-options-subsection'>
                                <h4>Stock Display Config</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_display_options();
       echo "                   </div>
                            </div>
                           <div class='widget-options-subsection'>
                                <h4>Advanced Styling</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_style_field();
        echo "                  </div>
                            </div>
                           <div class='widget-options-subsection'>
                                <h4>URL Link</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_url_field();
        echo "                  </div>
                    </div>
                </div>
                    </div><!--end referrers -->
                </div>
                <input type='submit' name='save_changes'  value='Save Changes'      class='button-primary' />
                <input type='submit' name='reset_options' value='Reset to Defaults' class='button-primary' /><sup>*</sup>
                <br />
                <sup>* NOTE: 'Reset to Defaults' also clears all stock lists.</sup>
            </div>
        
            <div class='postbox-container widget-options' style='width: 45%; clear:right;'>
                    <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                        <div id='referrers' class='postbox'>
                            <h3 class='hndle'><span>Stocks</span></h3>
                            <div class='inside'>
                                <p>Type in your stocks as a comma-separated list.<br /> 
                                Example: <code>GOOG,YHOO,AAPL</code>.</p>
                                <p>For Nasdaq, use <code>^IXIC</code>. For S&amp;P500, use <code>^GSPC</code>. Unfortunately, DOW is currently not available.</p>
                                <p>Here are some example stocks you can try:<br/>
                                BAC, CFG, AAPL, YHOO, SIRI, VALE, QQQ, GE, MDR, RAD, BABA, SUNE, FB, BBRY, MSFT, MU, PFE, F, GOOG</p>";
                                stock_plugin_create_per_category_stock_lists('widget');
        echo "              </div>
                        </div>
                    </div>
            </div>
            </form>";
    return;
}

function stock_widget_update_options() {
    stock_plugin_update_per_category_stock_lists('widget');  //NOTE: This has to be done every time, because templates don't contain any stock lists

    $apply_template = $_POST['template'];
    if($apply_template != '-------') {
        stock_widget_apply_template($apply_template); //this is actually apply template
    }
    else { //all of these settings are handled by the template, therefore don't bother updating them if template being applied

        $new_display_options = array( //these are checkboxes
            0, //market
            1, //stock symbol
            array_key_exists('last_value',     $_POST),
            array_key_exists('change_value',   $_POST),
            array_key_exists('change_percent', $_POST),
            0  //last trade
        );
        update_option('stock_widget_data_display', $new_display_options);
        update_option('stock_widget_draw_vertical_dash',   array_key_exists('vertical_dash',   $_POST));
        update_option('stock_widget_draw_horizontal_dash', array_key_exists('horizontal_dash', $_POST)); //these are checkboxes

        update_option('stock_widget_display_type',         $_POST['display_type']); //these are dropdowns
        update_option('stock_widget_change_style',         $_POST['change_style']);
        
        global $stock_widget_vp;
        //If returns false, it will NOT update them, and the display creation function will continue to use the most recently saved value
        //IN FUTURE: this will be replaced with AJAX and javascript validation
        // when we add in ajax later, we'll want to have a small piece of JS written to admin page, 
        //this will contain the ajax destination, that way we can merge javascript file code between plugins
        
        //NOTE: stock_plugin_validate_integer($new_val, $min_val, $max_val, $default)
        $tmp = stock_plugin_validate_integer($_POST['max_display'],  $stock_widget_vp['max_display'][0],  $stock_widget_vp['max_display'][1],  false);
        if ($tmp) { update_option('stock_widget_max_display', $tmp); }

        // VALIDATE overall height/width for the whole table
        //Could we make this used for the "row" height? Each stock row is this high?
        $current_display = get_option('stock_widget_display_size'); 
        $tmp1 = stock_plugin_validate_integer($_POST['width'],  $stock_widget_vp['width'][0],  $stock_widget_vp['width'][1],  $current_display[0]);
        $tmp2 = stock_plugin_validate_integer($_POST['height'], $stock_widget_vp['height'][0], $stock_widget_vp['height'][1], $current_display[1]);
        update_option('stock_widget_display_size', array($tmp1, $tmp2));
        
        // VALIDATE fonts
        $current_font_opts = get_option('stock_widget_font_options');
        $tmp1 = stock_plugin_validate_integer(    $_POST['font_size'],   $stock_widget_vp['font_size'][0],  $stock_widget_vp['font_size'][1],  $current_font_opts[0]);
        $tmp2 = stock_plugin_validate_font_family($_POST['font_family'], $current_font_opts[1]);
        update_option('stock_widget_font_options', array($tmp1, $tmp2));
        
        // VALIDATE COLORS
        $current_colors = get_option('stock_widget_color_scheme');
        $tmp1 = stock_plugin_validate_color($_POST['text_color'],        $current_colors[0]);
        //$tmp2 = stock_plugin_validate_color($_POST['border_color'],      $current_colors[1]); //NOTE: border is currently not used at all
        $tmp3 = stock_plugin_validate_color($_POST['background_color1'], $current_colors[2]);
        $tmp4 = stock_plugin_validate_color($_POST['background_color2'], $current_colors[3]);
        update_option('stock_widget_color_scheme', array($tmp1, $current_colors[1], $tmp3, $tmp4));
        
        update_option('stock_page_url',              $_POST['stock_page_url']);  //FOR FUTURE: URL validation relative or absolute url
        
        $tmp = trim($_POST['widget_advanced_style']); //strip spaces
        if (substr($tmp, -1) != ';') { $tmp .= ';'; } //poormans making of a css rule
        update_option('stock_widget_advanced_style', $tmp);
    }
}


function stock_widget_create_template_field() {

    $all_settings = get_option('stock_widget_default_settings');
    ?>  
        <label for="input_template">Template: </label>
        <select id="input_template" name="template" style="width:250px;">
        <option selected> ------- </option>
        <?php
            foreach($all_settings as $key=>$setting){
                echo "<option value='{$key}'>{$setting['name']}</option>";
            }
        ?>
        </select>
    <?php
}


function stock_widget_create_widget_config_section() {
    $size           = get_option('stock_widget_display_size');
    $max            = get_option('stock_widget_max_display');
    $current_colors = get_option('stock_widget_color_scheme');
    echo <<< HEREDOC
        <label for="input_width">Width: </label>
        <input  id="input_width"  name="width"  type="text" value="{$size[0]}" style="width:60px; font-size:14px" />
        <label for="input_height">Height: </label>
        <input  id="input_height" name="height" type="text" value="{$size[1]}" style="width:60px; font-size:14px" />
        <br />
        <label for="input_max_display">Maximum number of stocks displayed: </label>
        <input  id="input_max_display" name="max_display" type="text" value="{$max}" style="width:40px; font-size:14px; text-align:center" />
        <br />
        <label for="input_background_color1">Odd Row Background Color:</label>
        <input  id="input_background_color1" name="background_color1" type="text" value="{$current_colors[2]}" style="width:99px;" />
        <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" style="text-decoration:none">[?]</a></sup>
        <br />
        <label for="input_background_color2">Even Row Background Color:</label>
        <input  id="input_background_color2" name="background_color2" type="text" value="{$current_colors[3]}" style="width:95px;" />
        <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" style="text-decoration:none">[?]</a></sup>
HEREDOC;
}


function stock_widget_create_text_config() {
    $font_options   = get_option('stock_widget_font_options');
    $current_colors = get_option('stock_widget_color_scheme');
    $default_fonts  = array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times");
    ?>
        <label for="input_text_color">Color: </label>
        <input  id="input_text_color" name="text_color" type="text" value="<?php echo $current_colors[0]; ?>" style="width:100px; text-align:left;" />
        <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" style="text-decoration:none">[?]</a></sup>
        
        <label for="input_font_size">Size: </label>
        <input  id="input_font_size" name="font_size" type="text" value="<?php echo $font_options[0]; ?>" style="width:40px; text-align:left;"/>
        
        <br />
        <label for="input_font_family">Font-Family: </label>
        <input  id="input_font_family" name="font_family" list="font_family" value="<?php echo $font_options[1]; ?>" autocomplete="on" style="width:125px; text-align:left;"/>
        <datalist id="font_family">
        <?php
            foreach($default_fonts as $font){
                echo "<option value='{$font}'></option>";
            }
        ?>
        </datalist>
    <?php
}

function stock_widget_create_display_options() {
    $all_types       = array('Preset', 'A-Z', 'Z-A', 'Random');
    $current_type    = get_option('stock_widget_display_type');
    $display_options = get_option('stock_widget_data_display');  //this contains stock symbol attributes
    //NOTE: options 0 and 1 are "market" and the "stock symbol" itself
    //      option 5 is the "last trade"
    ?>
    
    <label for='input_last_value'>Last Value</label>
    <input  id='input_last_value'     name='last_value'     type='checkbox' <?php echo ($display_options[2] == 1 ? 'checked' : '')?>>
    <br />
    <label for='input_change_value'>Change Value</label>
    <input  id='input_change_value'   name='change_value'   type='checkbox' <?php echo ($display_options[3] == 1 ? 'checked' : '')?>>
    <br />
    <label for='input_change_percent'>Change Percent</label>
    <input  id='input_change_percent' name='change_percent' type='checkbox' <?php echo ($display_options[4] == 1 ? 'checked' : '')?>>
    <br />
    <label for='input_vertical_dash'>Vertical Dash</label>
    <input  id='input_vertical_dash'  name='vertical_dash'  type='checkbox' <?php echo (get_option('stock_widget_draw_vertical_dash')   ? 'checked' : '')?>>
    <br />
    <label for='input_horizontal_dash'>Horizontal Dash</label>
    <input  id='input_horizontal_dash'name='horizontal_dash'type='checkbox' <?php echo (get_option('stock_widget_draw_horizontal_dash') ? 'checked' : '')?>>
    <br />
    <br />
    
        <label for="input_display_type">Order: </label>
        <select id="input_display_type" name="display_type"  style="width: 100px;">
        <?php 
            foreach($all_types as $type) {
                echo "<option " . ($type == $current_type ? "selected" : "") . ">{$type}</option>";
            }
        ?>
        </select>
        <br />
    <?php
    global $stock_widget_vp;
    $all_change_styles = $stock_widget_vp['change_styles'];
    $current_style     = get_option('stock_widget_change_style');
    ?>
        <label for="input_change_style">Price Change Style: </label>
        <select id="input_change_style" name="change_style"  style="width: 130px;">
        <?php 
            foreach($all_change_styles as $style) {
                echo "<option " . ($style == $current_style ? "selected" : "") . ">{$style}</option>";
            }
        ?>
        </select>
        <br />
    <?php
}


function stock_widget_apply_template($selected_template) {

    $all_settings      = get_option('stock_widget_default_settings');
    $selected_template = $all_settings[$selected_template];

    $option_holder    = get_option('stock_widget_font_options');
    $option_holder[1] = $selected_template['font_family'];
    update_option('stock_widget_font_options', $option_holder);

    $option_holder    = get_option('stock_widget_color_scheme');  //NOTE: Border is not updated
    $option_holder[0] = $selected_template['font_color'];
    $option_holder[2] = $selected_template['back_color1'];
    $option_holder[3] = $selected_template['back_color2'];
    update_option('stock_widget_color_scheme', $option_holder);

    update_option('stock_widget_draw_vertical_dash',   $selected_template['verti_lines']);
    update_option('stock_widget_draw_horizontal_dash', $selected_template['hori_lines']);
    update_option('stock_widget_change_style',         $selected_template['check_box']);

}

function stock_widget_create_style_field() {
    $previous_setting = get_option('stock_widget_advanced_style');
    echo "
        <p>
            If you have additional CSS rules you want to apply to the
            entire widget (such as alignment or borders) you can add them below.
        </p>
        <p>
            Example: <code>margin:auto; border:1px solid #000000;</code>
        </p>
        <input id='input_widget_advanced_style' name='widget_advanced_style' type='text' value='{$previous_setting}' style='width:90%; font-size:14px;' />";
}

function stock_widget_create_url_field() {
    $current_url = get_option('stock_page_url');
    echo "<p>Url that clicking on a stock will link to.  __STOCK__ will be replaced with the stock symbol.</p>
          <p>Example/Default: https://www.google.com/finance?q=__STOCK__</p>
          <input id='stock_page_url' name='stock_page_url' type='text' value='{$current_url}' style='width:90%; font-size:14px;' />";
}



/*  Unused
//Generates the html for the listbox of markets
function stock_widget_create_market_list(){
    ?>
        Default Market:
            <select name="markets">
                <?php
                $default_mark=get_option('stock_widget_default_market');
                echo '<option selected>'.$default_mark;
                $markets=get_option('stock_widget_all_markets');
                if(!empty($markets)){
                    foreach($markets as $market){
                        if($default_mark!=$market){
                        echo "<option >".$market;
    }
                    }
                }   
                ?>
    
            </select>

    <?php
}
function stock_widget_update_market_list(){

        $market = $_POST['markets'];
        update_option('stock_widget_default_market', $market);
}
*/

?>
