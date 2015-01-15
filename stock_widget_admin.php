<?php

/*
    Plugin Name: Custom Stock Widget
    Plugin URI: http://relevad.com/wp-plugins/
    Description: Create customizable stock data table widgets that can be placed anywhere on a site using shortcodes.
    Author: Relevad
    Version: 1.3.1
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

// Feature Improvement: think about putting each individual config into a class, does that buy us anything?

if (!defined('STOCK_PLUGIN_UTILS') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-widget/stock_plugin_utils.php'; //used to contain validation functions
    
    if (!defined('RELEVAD_PLUGIN_UTILS')) {
        include WP_CONTENT_DIR . '/plugins/custom-stock-widget/relevad_plugin_utils.php';
    }
}
if (!defined('STOCK_PLUGIN_CACHE') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-widget/stock_plugin_cache.php';
}

    include WP_CONTENT_DIR . '/plugins/custom-stock-widget/stock_widget_display.php';

$stock_widget_vp = array( //validation_parameters
'max_display'  => array(1,100),
'width'        => array(100,500),
'height'       => array(100,1000),
'font_size'    => array(5,32),
'change_styles'=> array("None", "Box", "Parentheses")
);

function stock_widget_activate() {
    add_option('stock_widget_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces

    //Holds the default settings
    $stock_widget_default_settings = Array(
        'data_display'      => array(0,1,1,1,1,0),
        //'default_market'    => 'DOW',
        //'display_options_strings' => array("Market", "Symbol", "Last value", "Change value", "Change percentage", "Last trade"),
        'font_color'            => '#5DFC0A', 
        'bg_color1'             => '#000000',
        'bg_color2'             => '#7F7F7F', //NOTE: removed border color entirely
        'width'                 => 300,
        'height'                => 70,
        'font_size'             => 12,
        'font_family'           => 'Times',
        'display_number'        => 5,  //from max_display
        'advanced_style'        => 'margin: auto;',
        'draw_vertical_lines'   => false,  //vertical_dash
        'draw_horizontal_lines' => false,  //horizontal_dash
        'show_header'           => false,
        'display_order'         => 'Preset', //from display_type
        'change_style'          => 'Box',    //its how the stock change status is emphasized
        'stock_page_url'        => 'https://www.google.com/finance?q=__STOCK_'
        );
    add_option('stock_widget_default_settings', $stock_widget_default_settings); //one option to rule them all

}
register_activation_hook( __FILE__, 'stock_widget_activate' );

//*********cleanup and conversion functions for updating versions 1.0 -> 1.1 *********
if (get_option('stock_widget_category_stock_list')) { //this old option exists
    stock_plugin_convert_old_category_stock_list('widget');
    
    $tmp = get_option('stock_widget_data_display');
    update_option('stock_widget_data_display', array_values($tmp));
}

if (get_option('stock_widget_color_scheme')) { //this old option exists
    stock_widget_convert_old_options(); //version 1.1 -> 1.3
}


function stock_widget_admin_enqueue($hook) {
    //if ($hook != 'settings_page_stock_widget_admin') {return;} //do not run on other admin pages
    if ($hook != 'relevad-plugins_page_stock_widget_admin') {return;} //do not run on other admin pages

    wp_register_style ('stock_plugin_admin_style',  plugins_url('stock_plugin_admin_style.css', __FILE__));
    wp_register_script('stock_plugin_admin_script', plugins_url('stock_plugin_admin_script.js', __FILE__), array( 'jquery' ), false, false);

    wp_enqueue_style ('stock_plugin_admin_style');
    wp_enqueue_script('stock_plugin_admin_script');
    
    stock_widget_scripts_enqueue(true); //we also need these scripts
}
add_action('admin_enqueue_scripts', 'stock_widget_admin_enqueue');

function stock_widget_admin_actions() {
    
    relevad_plugin_add_menu_section(); //imported from relevad_plugin_utils.php
    
    //$hook = add_options_page('StockWidget', 'StockWidget', 'manage_options', 'stock_widget_admin', 'stock_widget_admin_page'); //wrapper for add_submenu_page specifically into "settings"
    $hook = add_submenu_page('relevad_plugins', 'StockWidget', 'StockWidget', 'manage_options', 'stock_widget_admin', 'stock_widget_admin_page'); 
    //add_submenu_page( 'options-general.php', $page_title,     $menu_title,    $capability,     $menu_slug,           $function ); // do not use __FILE__ for menu_slug
}
add_action('admin_menu', 'stock_widget_admin_actions');


//for debugging only
function stock_widget_reset_options() {
    update_option('stock_widget_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces
    
    $stock_widget_default_settings = Array(
        'data_display'      => array(0,1,1,1,1,0),
        //'default_market'    => 'DOW',
        //'display_options_strings' => array("Market", "Symbol", "Last value", "Change value", "Change percentage", "Last trade"),
        'font_color'            => '#5DFC0A', 
        'bg_color1'             => '#000000',
        'bg_color2'             => '#7F7F7F', //NOTE: removed border color entirely
        'width'                 => 300,
        'height'                => 70,
        'font_size'             => 12,
        'font_family'           => 'Times',
        'display_number'        => 5,  //from max_display
        'advanced_style'        => 'margin: auto;',
        'draw_vertical_lines'   => false,  //vertical_dash
        'draw_horizontal_lines' => false,  //horizontal_dash
        'show_header'           => false,
        'display_order'         => 'Preset', //from display_type
        'change_style'          => 'Box',    //its how the stock change status is emphasized
        'stock_page_url'        => 'https://www.google.com/finance?q=__STOCK_'
        );
    update_option('stock_widget_default_settings', $stock_widget_default_settings); //one option to rule them all
}


/** Creates the admin page. **/
function stock_widget_admin_page() {

    echo <<<HEREDOC
<div id="sp-options-page">

    <h1>Custom Stock Widget</h1>
    <p>The Custom Stock Widget plugin allows you to create and run your own custom stock table widgets.</p>
    <p>Choose your stocks and display settings below.<br />
    Then place your the shortcode <code>[stock-widget]</code> inside a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
    Or, you can use <code>&lt;?php echo do_shortcode('[stock-widget]'); ?&gt;</code> inside your theme files or <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.
    </p>
    
HEREDOC;
    
    if (isset($_POST['save_changes'])) {
        stock_plugin_update_per_category_stock_lists('widget');
        stock_widget_update_options();
    } 
    elseif (isset($_POST['reset_options'])) {
        stock_widget_reset_options();
    }
    stock_widget_create_options_config();

    echo <<<HEREDOC
            <div id="sp-preview" class="postbox-container sp-options">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <div id="referrers" class="postbox">
                        <h3 class="hndle"><span>Preview</span></h3>
                        <div class="inside">
                           <p>Based on the last saved settings, this is what the default <code>[stock-widget]</code> shortcode will generate:</p>
HEREDOC;
    echo do_shortcode('[stock-widget]');
    echo <<<HEREDOC
                           <p>To preview your latest changes to settings, you must first save changes.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
</div><!-- end options page -->
HEREDOC;
}

//Creates the entire options page. Useful for formatting.
function stock_widget_create_options_config() {
        $sw_ds = get_option('stock_widget_default_settings');
        echo "<form action='' method='POST'>
             <div id='sp-form-div' class='postbox-container sp-options'>
                <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                    <div id='referrers' class='postbox'>
                        <h3 class='hndle'>Default Widget Display Settings</h3>
                        <div class='inside'>";
                            stock_widget_create_template_field();
        echo "              <div class='sp-options-subsection'>
                                <h4>Widget Config</h4> 
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_widget_config_section($sw_ds);  //FOR FUTURE: add in a color swatch of some sort
        echo "                  </div>
                            </div>
                            <div class='sp-options-subsection'>
                                <h4>Text Config</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_text_config($sw_ds);
        echo "                  </div>
                            </div>
                            <div class='sp-options-subsection'>
                                <h4>Stock Display Config</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_display_options($sw_ds);
       echo "                   </div>
                            </div>
                           <div class='sp-options-subsection'>
                                <h4>Advanced Styling</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_style_field($sw_ds);
        echo "                  </div>
                            </div>
                           <div class='sp-options-subsection'>
                                <h4>URL Link</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_widget_create_url_field($sw_ds);
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
        
            <div id='sp-cat-stocks-div' class='postbox-container sp-options'>
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


function stock_widget_templates() { //helper function to avoid global variables
    return array(
        'Default' => array(
            'name'                  => 'Default (green on black/gray)', 
            'font_family'           => 'Times', 
            'font_color'            => '#5DFC0A', 
            'bg_color1'             => '#000000',
            'bg_color2'             => '#7F7F7F', 
            'draw_horizontal_lines' => false, 
            'draw_vertical_lines'   => false, 
            'change_style'          => 'Box'),
        'Classic' => array(
            'name'                  => 'Classic (white on black)', 
            'font_family'           => 'Arial', 
            'font_color'            => '#FFFFFF', 
            'bg_color1'             => '#000000',
            'bg_color2'             => '#000000', 
            'draw_horizontal_lines' => true, 
            'draw_vertical_lines'   => false, 
            'change_style'          => 'Box'),
        'Ocean' => array(
            'name'                  => 'Ocean (white on purple/blue)', 
            'font_family'           => 'Arial', 
            'font_color'            => '#FFFFFF', 
            'bg_color1'             => '#3366CC', 
            'bg_color2'             => '#19A3FF', 
            'draw_horizontal_lines' => false, 
            'draw_vertical_lines'   => true, 
            'change_style'          => 'None'),
        'Matrix' => array(
            'name'                  => 'Matrix (green on black)', 
            'font_family'           => 'Arial', 
            'font_color'            => '#66FF33', 
            'bg_color1'             => '#000000', 
            'bg_color2'             => '#000000', 
            'draw_horizontal_lines' => true, 
            'draw_vertical_lines'   => false, 
            'change_style'          => 'None'),
        'Minimal' => array(
            'name'                  => 'Minimal (black on transparent)', 
            'font_family'           => 'Arial', 
            'font_color'            => '#000000', 
            'bg_color1'             => 'transparent', 
            'bg_color2'             => 'transparent', 
            'draw_horizontal_lines' => true, 
            'draw_vertical_lines'   => false, 
            'change_style'          => 'Parentheses'),
        'Cotton Candy' => array(
            'name'                  => 'Cotton Candy (blue on pink/purple)', 
            'font_family'           => 'cursive', 
            'font_color'            => '#00FFFF', 
            'bg_color1'             => '#FF5050', 
            'bg_color2'             => '#CC66FF', 
            'draw_horizontal_lines' => true, 
            'draw_vertical_lines'   => false, 
            'change_style'          => 'None'),
    );
}

function stock_widget_create_template_field() {

    $all_templates = stock_widget_templates();
    ?>  
        <label for="input_template">Template: </label>
        <select id="input_template" name="template" style="width:250px;">
        <option selected> ------- </option>
        <?php
            foreach($all_templates as $key=>$template){
                echo "<option value='{$key}'>{$template['name']}</option>";
            }
        ?>
        </select>
        <input type="submit" name="save_changes"  value="Apply" class="button-primary" />&nbsp;<sup>*</sup>
        <br/>
        <sup>* NOTE: Not all options are over-written by template</sup>
    <?php
}

function stock_widget_update_options() {
    
    global $stock_widget_vp;
    $sw_ds             = get_option('stock_widget_default_settings');
    $selected_template = $_POST['template'];  //NOTE: if this doesn't exist it'll be NULL
    $all_templates     = stock_widget_templates();
    
    $template_settings = array(); 
    if(array_key_exists($selected_template, $all_templates)) {
        
        $template_settings = $all_templates[$selected_template];
        unset($template_settings['name']); //throw out the name or we'll end up adding it to default settings (which we don't need)

    }

    $sw_ds_new = array();
    
    $new_display_options = array( //these are checkboxes
            0, //market
            1, //stock symbol
            (array_key_exists('last_value',     $_POST) ? 1 : 0),
            (array_key_exists('change_value',   $_POST) ? 1 : 0),
            (array_key_exists('change_percent', $_POST) ? 1 : 0),
            0  //last trade
    );
    $sw_ds_new['data_display']          = $new_display_options;
    $sw_ds_new['draw_vertical_lines']   = (array_key_exists('vertical_dash',   $_POST) ? 1 : 0);
    $sw_ds_new['draw_horizontal_lines'] = (array_key_exists('horizontal_dash', $_POST) ? 1 : 0);
    $sw_ds_new['show_header']           = (array_key_exists('show_header',     $_POST) ? 1 : 0);
    
    $sw_ds_new['display_order'] = $_POST['display_type']; //these are dropdowns so no validation necessary -- unless someone deliberately tries to post garbage to us
    $sw_ds_new['change_style']  = $_POST['change_style'];
    
    $tmp = relevad_plugin_validate_integer($_POST['max_display'],  $stock_widget_vp['max_display'][0],  $stock_widget_vp['max_display'][1],  false);
    if ($tmp) {
    $sw_ds_new['display_number'] = $tmp;
    }
    
    $sw_ds_new['width']  = relevad_plugin_validate_integer($_POST['width'],  $stock_widget_vp['width'][0],  $stock_widget_vp['width'][1],  $sw_ds['width']);
    $sw_ds_new['height'] = relevad_plugin_validate_integer($_POST['height'], $stock_widget_vp['height'][0], $stock_widget_vp['height'][1], $sw_ds['height']);

    $sw_ds_new['font_size']   = relevad_plugin_validate_integer(    $_POST['font_size'],   $stock_widget_vp['font_size'][0],  $stock_widget_vp['font_size'][1],  $sw_ds['font_size']);
    $sw_ds_new['font_family'] = relevad_plugin_validate_font_family($_POST['font_family'], $sw_ds['font_family']);

    $sw_ds_new['font_color'] = relevad_plugin_validate_color($_POST['text_color'],        $sw_ds['font_color']);
    $sw_ds_new['bg_color1']  = relevad_plugin_validate_color($_POST['background_color1'], $sw_ds['bg_color1']);
    $sw_ds_new['bg_color2']  = relevad_plugin_validate_color($_POST['background_color2'], $sw_ds['bg_color2']);
    
    $sw_ds_new['stock_page_url'] = $_POST['stock_page_url'];
    
    $tmp = trim($_POST['widget_advanced_style']); //strip spaces
    if ($tmp != '' && substr($tmp, -1) != ';') { $tmp .= ';'; } //poormans making of a css rule
    $sw_ds_new['advanced_style'] = $tmp;
    
    //****** fix scaling *******
    //NOTE: we would have to increase the overall width to compensate.
    //NOTE: Header overlap is the biggest problem
    //this section is to fix the width/height attributes so that incase the ticker would have had overlapping text, it fixes itself to a minimum acceptable level
    $minimum_width = $sw_ds_new['font_size'] * 4 * array_sum($new_display_options);  //point font * 4 characters * X elements ~ aproximate
    if ($minimum_width > $sw_ds_new['width']) {
        echo "<div id='sp-warning'><h1>Warning:</h1>";
        echo "Chosen font size of " . $sw_ds_new['font_size'] . " when used with width of " . $sw_ds_new['width'] . " could cause overlap of text.</div>";
    }
    //****** end fix scaling ******* 
    
    //now merge template settings > post changes > old unchanged settings
    update_option('stock_widget_default_settings', array_replace($sw_ds, $sw_ds_new, $template_settings));
}

function stock_widget_create_widget_config_section($sw_ds) {
    $width     = $sw_ds['width'];
    $height    = $sw_ds['height'];
    $max       = $sw_ds['display_number'];
    $bg_color1 = $sw_ds['bg_color1'];
    $bg_color2 = $sw_ds['bg_color2'];
    echo <<< HEREDOC
        <label for="input_width">Width: </label>
        <input  id="input_width"  name="width"  type="text" value="{$width}"  class="itxt"/>
        <label for="input_height">Height: </label>
        <input  id="input_height" name="height" type="text" value="{$height}" class="itxt"/>
        <br />
        <label for="input_max_display">Maximum number of stocks displayed: </label>
        <input  id="input_max_display" name="max_display" type="text" value="{$max}" class="itxt" style="width:40px;" />
        <br />
        <label for="input_background_color1">Odd Row Background Color:</label>
        <input  id="input_background_color1" name="background_color1" type="text" value="{$bg_color1}" class="itxt" style="width:99px;" />
        <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
        <br />
        <label for="input_background_color2">Even Row Background Color:</label>
        <input  id="input_background_color2" name="background_color2" type="text" value="{$bg_color2}" class="itxt" style="width:95px;" />
        <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
HEREDOC;
}


function stock_widget_create_text_config($sw_ds) {
    $default_fonts  = array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times");  //maybe extract this list into utils
    ?>
        <label for="input_text_color">Color: </label>
        <input  id="input_text_color" name="text_color" type="text" value="<?php echo $sw_ds['font_color']; ?>" class="itxt" style="width:100px;" />
        <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
        
        <label for="input_font_size">Size: </label>
        <input  id="input_font_size" name="font_size" type="text" value="<?php echo $sw_ds['font_size']; ?>" class="itxt" style="width:40px;"/>
        
        <br />
        <label for="input_font_family">Font-Family: </label>
        <input  id="input_font_family" name="font_family" list="font_family" value="<?php echo $sw_ds['font_family']; ?>" autocomplete="on" style="width:125px;"/>
        <datalist id="font_family">
        <?php
            foreach($default_fonts as $font){
                echo "<option value='{$font}'></option>";
            }
        ?>
        </datalist>
    <?php
}

function stock_widget_create_display_options($sw_ds) {
    $all_orders      = array('Preset', 'A-Z', 'Z-A', 'Random');
    //NOTE for data_display: options 0 and 1 are "market" and the "stock symbol" itself
    //      option 5 is the "last trade"
    ?>
    
    <label for='input_show_header'>Show Headers</label>
    <input  id='input_show_header'    name='show_header'    type='checkbox' <?php checked($sw_ds['show_header']); ?>>
    <br />
    <label for='input_last_value'>Last Value</label>
    <input  id='input_last_value'     name='last_value'     type='checkbox' <?php checked(1, $sw_ds['data_display'][2]);?>>
    <br />
    <label for='input_change_value'>Change Value</label>
    <input  id='input_change_value'   name='change_value'   type='checkbox' <?php checked(1, $sw_ds['data_display'][3]);?>>
    <br />
    <label for='input_change_percent'>Change Percent</label>
    <input  id='input_change_percent' name='change_percent' type='checkbox' <?php checked(1, $sw_ds['data_display'][4]);?>>
    <br />
    <label for='input_vertical_dash'>Vertical Dash</label>
    <input  id='input_vertical_dash'  name='vertical_dash'  type='checkbox' <?php checked($sw_ds['draw_vertical_lines']);?>>
    <br />
    <label for='input_horizontal_dash'>Horizontal Dash</label>
    <input  id='input_horizontal_dash'name='horizontal_dash'type='checkbox' <?php checked($sw_ds['draw_horizontal_lines']);?>>
    <br />
    <br />
    
    <label for="input_display_type">Order: </label>
    <select id="input_display_type" name="display_type"  style="width: 100px;">
    <?php 
        foreach($all_orders as $order) {
            echo "<option " . selected($order, $sw_ds['display_order']) . ">{$order}</option>";
        }
    ?>
    </select>
    <br />
    <?php
    global $stock_widget_vp;
    $all_change_styles = $stock_widget_vp['change_styles'];
    ?>
    <label for="input_change_style">Price Change Style: </label>
    <select id="input_change_style" name="change_style"  style="width: 130px;">
    <?php 
        foreach($all_change_styles as $style) {
            echo "<option " . selected($style, $sw_ds['change_style']) . ">{$style}</option>";
        }
    ?>
    </select>
    <br />
    <?php
}




function stock_widget_create_style_field($sw_ds) {
    $advanced_style = $sw_ds['advanced_style'];
    echo "
        <p>
            If you have additional CSS rules you want to apply to the
            entire widget (such as alignment or borders) you can add them below.
        </p>
        <p>
            Example: <code>margin:auto; border:1px solid #000000;</code>
        </p>
        <input id='input_widget_advanced_style' name='widget_advanced_style' type='text' value='{$advanced_style}' class='itxt' style='width:90%; text-align:left;' />";
}

function stock_widget_create_url_field($sw_ds) {
    $current_url = $sw_ds['stock_page_url'];
    echo "<p>Url that clicking on a stock will link to.  __STOCK__ will be replaced with the stock symbol.</p>
          <p>Example/Default: https://www.google.com/finance?q=__STOCK__</p>
          <input id='stock_page_url' name='stock_page_url' type='text' value='{$current_url}' class='itxt' style='width:90%; text-align:left;' />";
}


function stock_widget_convert_old_options() {
    $tmp1 = get_option('stock_widget_color_scheme');
    $tmp2 = get_option('stock_widget_display_size');
    $tmp3 = get_option('stock_widget_font_options');
    
    $stock_widget_default_settings = Array(
        'data_display'          => get_option('stock_widget_data_display'),
        'font_color'            => $tmp1[0], 
        'bg_color1'             => $tmp1[2],
        'bg_color2'             => $tmp1[3],
        'width'                 => $tmp2[0],
        'height'                => $tmp2[1],
        'font_size'             => $tmp3[0],
        'font_family'           => $tmp3[1],
        'display_number'        => get_option('stock_widget_max_display'),
        'advanced_style'        => get_option('stock_widget_advanced_style'),
        'draw_vertical_lines'   => get_option('stock_widget_draw_vertical_dash'),
        'draw_horizontal_lines' => get_option('stock_widget_draw_horizontal_dash'),
        'show_header'           => false, //added brandnew option
        'display_order'         => get_option('stock_widget_display_type'),
        'change_style'          => get_option('stock_widget_change_style'),
        'stock_page_url'        => get_option('stock_page_url')
        );

    delete_option('stock_widget_color_scheme'); //cleanup the old stuff
    delete_option('stock_widget_display_size');
    delete_option('stock_widget_font_options');
    
    delete_option('stock_widget_data_display');
    delete_option('stock_widget_max_display');
    delete_option('stock_widget_advanced_style');
    
    delete_option('stock_widget_draw_vertical_dash');
    delete_option('stock_widget_draw_horizontal_dash');
    delete_option('stock_widget_display_type');
    delete_option('stock_widget_change_style');
    delete_option('stock_page_url');
    
    //never used so cleanup now anyways
    delete_option('stock_widget_available_change_styles');
    delete_option('stock_widget_all_display_types');
    delete_option('stock_widget_default_market');
    delete_option('stock_widget_all_markets');
    delete_option('stock_widget_default_fonts');
    delete_option('stock_widget_display_option_strings');
    
    update_option('stock_widget_default_settings', $stock_widget_default_settings); //NOTE: update_option will add if does not exist
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
