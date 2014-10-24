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

include WP_CONTENT_DIR.'/plugins/custom-stock-widget/stock_plugin_cache.php';
include WP_CONTENT_DIR."/plugins/custom-stock-widget/stock_widget_display.php";

add_shortcode('stock-widget', 'stock_widget');

add_option("stock_widget_category_stock_list", array('Default'=>array('GOOG','YHOO','AAPL')));
add_option('stock_widget_default_market', "DOW");
add_option('stock_widget_all_markets', array("DOW","TYO","LON","FRA","SHA"));
//data display option: Market, Symbol, Last value, change value, change percentage, last trade
add_option('stock_widget_data_display',array('market'=>0,'stock_sym'=>1,'last_val'=>1,'change_val'=>1,'change_percent'=>1,'last_trade'=>0));

add_option('stock_widget_display_option_strings', array("Market","Symbol","Last Value","Change Value","Change Percentage","Last Trade"));
//controls the maximum number of stocks displayed
add_option('stock_widget_max_display', 5);
//controls the stocks that are displayed if more are chosen than the maximum. Options are 'first' and 'random'
add_option('stock_widget_display_type', 'First');
//All of the display types
add_option('stock_widget_all_display_types', array('First', 'Random'));
//Controls the color scheme the array holds [Text, Border,Background1, Background2]
add_option('stock_widget_color_scheme', array('#5DFC0A','#5DFC0A','black', 'grey'));
//the size option holds (width,height)
add_option('stock_widget_display_size', array(300,70));
//Font options are (size, family)
add_option('stock_widget_font_options', array(12, "Times"));
//Default font types
add_option('stock_widget_default_fonts', array('Arial','cursive','Gadget','Georgia','Impact','Palatino','sans-serif','serif','Times'));

//options for vertical and horizontal lines
add_option('stock_widget_draw_vertical_dash',false);

add_option('stock_widget_draw_horizontal_dash', false);

//The available change icon styles
add_option('stock_widget_available_change_styles', array('None','Box','Parentheses'));

add_option('stock_widget_advanced_style', 'margin:auto;');

add_option('stock_widget_change_style', 'Box');

add_option('stock_page_url','');

//Holds the default settings
add_option('stock_widget_default_settings',array(
	'Classic'=>array(
		'name'=>'Classic (black/white)', 
		'font_family' =>'Arial', 
		'font_color'=>'white', 
		'back_color1'=> 'black',
		'back_color2'=> 'black', 
		'hori_lines'=>true, 
		'verti_lines'=>false, 
		'check_box'=>'Box'),
	'Ocean'=>array(
		'name'=>'Ocean (purple/blue)', 
		'font_family' =>'Arial', 
		'font_color'=>'white', 
		'back_color1'=> '#3366CC', 
		'back_color2'=> '#19A3FF', 
		'hori_lines'=>false, 
		'verti_lines'=>true, 
		'check_box'=>'None'),
	'Matrix'=>array(
		'name'=>'Matrix (green/black)', 
		'font_family' =>'Arial', 
		'font_color'=>'#66FF33', 
		'back_color1'=> 'black', 
		'back_color2'=> 'black', 
		'hori_lines'=>true, 
		'verti_lines'=>false, 
		'check_box'=>'None'),
	'Minimal'=>array(
		'name'=>'Minimal (transparent/black)', 
		'font_family' =>'Arial', 
		'font_color'=>'black', 
		'back_color1'=> 'transparent', 
		'back_color2'=> 'transparent', 
		'hori_lines'=>true, 
		'verti_lines'=>false, 
		'check_box'=>'Parentheses'),
	'Cotton Candy'=>array(
		'name'=>'Cotton Candy (pink/purple)', 
		'font_family' =>'cursive', 
		'font_color'=>'#00FFFF', 
		'back_color1'=> '#FF5050', 
		'back_color2'=> '#CC66FF', 
		'hori_lines'=>true, 
		'verti_lines'=>false, 
		'check_box'=>'None'),
	));


add_action('admin_init', 'stock_widget_admin_init');
function stock_widget_admin_init() {
	wp_register_style('stock_widget_admin_style',plugins_url('stock_widget_admin_style.css', __FILE__));
	wp_enqueue_style('stock_widget_admin_style');
	wp_register_script('stock_widget_admin_script',plugins_url('stock_widget_admin_script.js', __FILE__) ,array( 'jquery' ),false, false);
	wp_enqueue_script('stock_widget_admin_script');
}

add_action('admin_menu', 'stock_widget_admin_actions');



 function stock_widget_admin_actions(){

 	add_options_page('StockWidget', 'StockWidget', 'manage_options', __FILE__, 'stock_widget_admin');

}




/*
*This is the admin page. 
*
*/
function stock_widget_admin(){
?>

	
<div id="widget-options-page" style="max-width:850px;">

	<h1>Custom Stock Widget</h1>
	<p>The Custom Stock Widget plugin allows you to create and run your own custom stock table widgets.</p>
	<p>Choose your stocks and display settings below.<br />
	Then place your the shortcode <code>[stock-widget]</code> inside a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
	Or, you can use <code>&lt;?php echo do_shortcode('[stock-widget]'); ?&gt;</code> inside your theme files or <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.
	</p>
	
	
	<?php
	echo '<form action="" method="POST">';
	if(isset($_POST['save_changes'])){
		stock_widget_update_display_options();
		stock_widget_create_display_options();
	}else{
		stock_widget_create_display_options();
	}
	echo '</form>';

	echo '	<div class="postbox-container widget-options" style="display:block; clear:both; width:750px;">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div id="referrers" class="postbox">
						<h3 class="hndle"><span>Preview</span></h3>
						<div class="inside">
						<p>Based on the last saved settings, this is what the default <code>[stock-widget]</code> shortcode will generate:</p>
';
	echo do_shortcode('[stock-widget]');
	echo '			<p>To preview your latest changes to settings, you must first save changes.</p>
						</div>
					</div>
				</div>
			</div>';
		echo '	<div class="postbox-container widget-options" style="display:block; clear:both; width:750px;">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div id="referrers" class="postbox">
						<h3 class="hndle"><span>Advanced</span></h3>
						<div class="inside">
							<p>If you want to run a custom style, you can specify the style parameters in the shortcode. See the example below:</p>
								<input type="text" onclick="this.select();" readonly="readonly" value="[stock-widget display=&quot;4&quot; width=&quot;300&quot; height=&quot;100&quot; background_color1=&quot;#336633&quot; background_color2=&quot;#663333&quot; text_color=&quot;#ffff00&quot;]" class="shortcode-in-list-table wp-ui-text-highlight code" style="width: 100%; font-size: smaller;"></p>';

		echo do_shortcode('[stock-widget display="4" width="300" height="100" background_color1="#336633" background_color2="#663333" text_color="#ffff00"]');

		echo '
						</div>
					</div>
				</div>
			</div>';
	echo '</div>';

}

//Creates the entire options page. Useful for formatting.
function stock_widget_create_display_options(){
		echo '<div class="postbox-container widget-options" style="width: 50%; margin-right: 10px; clear:left;">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div id="referrers" class="postbox">
						<h3 class="hndle">Display Settings</h3>
						<div class="inside">';
							stock_widget_create_default_settings_field();
		echo '				<p>All options below are <b>optional</b>.<br>All are reset by choosing a style above.</p>
							<div class="widget-options-subsection">
								<h4>Widget Settings</h4> 
								<div class="admin_toggle">+</div>
								<div class="widget-options-display">';
									stock_widget_create_size_field();
									stock_widget_create_max_display_field();
		echo'						<br>';
									stock_widget_create_background_color_field();
		echo '					</div>
							</div>';
		echo '				<div class="widget-options-subsection">
								<h4>Text Settings</h4>
								<div class="admin_toggle">+</div>
								<div class="widget-options-display">';
									stock_widget_create_font_field();
		echo '					</div>
							</div>';
		echo '				<div class="widget-options-subsection">
								<h4>Widget Features</h4>
								<div class="admin_toggle">+</div>
								<div class="widget-options-display">';
									stock_widget_create_display_options_list();
									stock_widget_create_draw_dash_field();
									echo '<br>';
									stock_widget_create_display_type_field();
									echo '<br>';
								

		echo '					</div>
							</div>';
		echo '				<div class="widget-options-subsection">
								<h4>Advanced Styling</h4>
								<div class="admin_toggle">+</div>
								<div class="widget-options-display">';
									stock_widget_create_style_field();
		echo '					</div>
							</div>';
		echo '				<div class="widget-options-subsection">
								<h4>URL Link</h4>
								<div class="admin_toggle">+</div>
								<div class="widget-options-display">';
									stock_widget_create_url_field();
		echo '					</div>
							</div>';
		echo '			</div>
					</div>
				</div>
				<input style="margin-bottom:20px;" type="submit" name="save_changes" value="Save Changes" class="button-primary"/>
			</div>';
		
		echo '	<div class="postbox-container widget-options" style="width: 45%; clear:right;">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div id="referrers" class="postbox">
							<h3 class="hndle"><span>Stocks</span></h3>
							<div class="inside">
								<p>Type in your stocks as a comma-separated list.<br> 
								Example: <code>GOOG,YHOO,AAPL</code>.</p>
								<p>
									When a page loads with a ticker, the stocks list of the category of that page is loaded. 
									If that category has no stocks associated with it, the default list is loaded.
								</p>
								<p>For Nasdaq, use <code>^IXIC</code>. For S&amp;P500, use <code>^GSPC</code>. Unfortunately, DOW is currently not available.</p>
								';
								stock_widget_create_category_stock_list();
		echo '				</div>
						</div>
					</div>
				</div>';
	return;
}

function stock_widget_update_display_options(){
		stock_widget_update_category_stock_list();
	//	stock_widget_update_market_list();
		stock_widget_update_display_options_list();
		stock_widget_update_max_display_field();
		stock_widget_update_draw_dash_field();
		stock_widget_update_display_type_field();
		stock_widget_update_color_field();
		stock_widget_update_size_font_field();
		stock_widget_update_default_settings_field();
		stock_widget_update_style_field();
		stock_widget_update_url_field();
		return;
}

//Generates the html for the listbox of markets
function stock_widget_create_market_list(){
	?>
		Default Market:
			<select name="markets">
				<?php
				$default_mark=get_option('stock_widget_default_market');
				echo '<option selected>'.$default_mark;
				$markets=get_option('rele_all_markets');
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


//Generates the html for the list of stocks in each category
function stock_widget_create_category_stock_list(){
	$category_stock_list=get_option('stock_widget_category_stock_list');
	$category_ids = get_all_category_ids();
	array_unshift ($category_ids,-1);
	$nocat=false;
	foreach($category_ids as $id){
		if($id==-1){
			$cat_id='Default';
			$name='';
		}else{
			$name = get_cat_name($id);
			$cat_id=preg_replace('/\s+/', '', $name);
			if($cat_id=='Uncategorized'){
				continue;
			}
		}

		$stock_list=$category_stock_list[$cat_id];
		$stocks_string="";
		//if the list of this category is not empty, built the list of stocks for the output
		if(!empty($stock_list)){		
			//Append each stock to the output string. Check to see if the stock is in the default market
			//(in which case do not output the market.)	
				
			$stocks_string=implode(',',$stock_list);
		}elseif($cat_id=='Default'){
			echo '<h4>Warning: Leaving this field blank may cause some widgets to not show up.</h4>';

		}

?>
		
		<?php echo $name; ?>
		<br>
		<input style="width:100%; font-size:14px" type="text" name="<?php echo $cat_id; ?>_stocks" id="<?php echo $cat_id; ?>_stock_list" value="<?php echo $stocks_string; ?>"/>
		
			
	
	<?php
		if($cat_id=='Default'){
			echo '	<h4 style="display:inline-block;">Customize Categories</h4>
					<div id="category_toggle" class="admin_toggle">+</div>
					
						<div class="widget-options-display">';
			//if default is the only category, this variable will become true, indicating that the
			//site does not have any categories. Used to display a little message
			$nocat=true;
		}else{
			$nocat=false;
		}
	}
	if($nocat){
		echo '<p> Your site does not appear to have any categories to display.</p>';
	}
	echo '</div>';
}

function stock_widget_update_category_stock_list(){
	$category_stock_list=array();
	$category_ids = get_all_category_ids();
	array_unshift ($category_ids,-1);
	$all_bad_stock_list=array();
	foreach($category_ids as $id){
		if($id==-1){
			$cat_id='Default';
		}else{
			$name = get_cat_name($id);
			$cat_id=preg_replace('/\s+/', '', $name);
			if($cat_id=='Uncategorized'){
				continue;
			}
		}
		$input = strtoupper ($_POST[$cat_id."_stocks"]);
		$input=preg_replace('/\s+/', '', $input);
		$input_list = explode(",", $input);
		if(empty($input_list)){
			$category_stock_list[$cat_id]=array();
			continue;
		}
		$input_list=array_unique($input_list);
		//runs the caching function on the given stocks list to see if any of the stocks were invalid.
		$cache_output=stock_plugin_get_data($input_list);
		$bad_stock_list=$cache_output['invalid_stocks'];
		if(!empty($bad_stock_list)){
			//get the difference of the two arrays, filter the empty values, and condense the array
			$stock_list_difference=array_diff($input_list, $bad_stock_list);
			$validated_stock_list=array_values($stock_list_difference);
			$all_bad_stock_list=array_merge($bad_stock_list, $all_bad_stock_list);
		}else{
			$validated_stock_list=array_filter($input_list);
		}
		$category_stock_list[$cat_id]=$validated_stock_list;

	}
	if(!empty($all_bad_stock_list)){
		?>
			<p style="font-size:14px;font-weight:bold;">
				The following stocks were not found: 
				<?php
					echo implode(', ',$all_bad_stock_list);
				?>.
			</p>
		<?php
	}
	update_option('stock_widget_category_stock_list', $category_stock_list);	
}

//Generates the html for the list of configurable options
function stock_widget_create_display_options_list(){
	$display_options=get_option('stock_widget_data_display');
	$display_option_strings=get_option('stock_widget_display_option_strings');
	$counter=-1;
	foreach ($display_options as $display){
		$counter++;
		//Skips displaying the "symbol" option. Who would want to hide the symbol?
		//Also skips last trade and market. Who needs that stuff?
		if($counter==1||$counter==0||$counter==5){
			continue;
		}
		if($display==1){
			$checked="checked";
		}else{
			$checked="";
		}
		?>
		
		<input name="stock_checkbox[]" type="checkbox" id="checkbox_<?php echo $counter;?>" value="<?php echo $counter;?>"<?php echo $checked;?>>
		<label for="checkbox_<?php echo $counter;?>"><?php echo $display_option_strings[$counter];?></label>	
		<br>
		<?php
		
	}
	?>
	
	<?php

}
function stock_widget_update_display_options_list(){
	$new_display_options=array(0,1,0,0,0,0);
    foreach($_POST['stock_checkbox'] as $key){
        $new_display_options[$key]=1;
    }
    $new_display_hash=array('market'=>$new_display_options[0],'stock_sym'=>$new_display_options[1],'last_val'=>$new_display_options[2],
    	'change_val'=>$new_display_options[3],'change_percent'=>$new_display_options[4],'last_trade'=>$new_display_options[5]);
    update_option('stock_widget_data_display',$new_display_hash);
}

function stock_widget_create_max_display_field(){
	?>
		<label for="max_display">Maximum number of stocks displayed: </label>
		<input style="width:29px; font-size:14px; text-align:center" type="text" name="max_display" id="max_display" value="<?php echo get_option('stock_widget_max_display'); ?>"/>
	<?php
}

function stock_widget_update_max_display_field(){
	$new_max=intval($_POST['max_display']);
	if($new_max>100){
		$new_max=100;
	}
	update_option('stock_widget_max_display', $new_max);
}

function stock_widget_create_display_type_field(){
	$all_types=get_option('stock_widget_all_display_types');
	$current_type=get_option('stock_widget_display_type');
	?>
		<label for="display_type">Order: </label>
		<select name="display_type" id="display_type" style="width: 70px;">
			<option selected>
		<?php 
			echo $current_type;
			foreach($all_types as $type){
				if($type==$current_type){
					continue;
				}
				echo "<option>".$type;
			}
		?>
		</select>
	<?php
	$all_change_styles=get_option('stock_widget_available_change_styles');
	$current_style=get_option('stock_widget_change_style');
	?>
		<br>
		<label for="changle_style">Price Change Style: </label>
		<select name="changle_style" id="changle_style" style="width: 100px;">
			<option selected>
		<?php 
			echo $current_style;
			foreach($all_change_styles as $style){
				if($style==$current_style){
					continue;
				}
				echo "<option>".$style;
			}
		?>
		</select>
	<?php
}

function stock_widget_update_display_type_field(){
	update_option('stock_widget_display_type',$_POST['display_type']);
	update_option('stock_widget_change_style',$_POST['changle_style']);
}

function stock_widget_create_background_color_field(){
	$current_colors=get_option('stock_widget_color_scheme');
	?>
	
	<label for="background_color1">Color 1</label><sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" style="text-decoration:none" ref="external nofollow" target="_blank" title="Use hex to pick colors!">[?]</a></sup>:
	<input style="width:70px;" name="background_color1" id="background_color1" type="text" value="<?php echo $current_colors[2]; ?>"/>
	<br>
	<label for="background_color2">Color 2</label><sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" style="text-decoration:none" ref="external nofollow" target="_blank" title="Use hex to pick colors!">[?]</a></sup>:
	<input style="width:70px;" name="background_color2" id="background_color2" type="text" value="<?php echo $current_colors[3]; ?>"/>


<?php

}

function stock_widget_update_color_field(){
	$new_colors=array($_POST['text_color'],$_POST['border_color'],$_POST['background_color1'],$_POST['background_color2']);
	$default_colors=get_option('stock_widget_color_scheme');
	if($new_colors[0]==""){
		$new_colors[0]=$default_colors[0];
	}
	if($new_colors[1]==""){
		$new_colors[1]=$default_colors[1];
	}
	if($new_colors[2]==""){
		$new_colors[2]=$default_colors[2];
	}
	if($new_colors[3]==""){
		$new_colors[3]=$default_colors[3];
	}
	update_option('stock_widget_color_scheme', $new_colors);
}

function stock_widget_create_size_field(){
	$size=get_option('stock_widget_display_size');
	?>
		<label for="stock_widget_width">Width: </label>
		<input style="width:60px; font-size:14px" type="text" name="stock_widget_width" id="stock_widget_width" value="<?php echo $size[0]; ?>"/>
		<label for="stock_widget_height">Height: </label>
		<input style="width:60px; font-size:14px" type="text" name="stock_widget_height" id="stock_widget_height" value="<?php echo $size[1]; ?>"/>
		<br>


	<?php

}
function stock_widget_create_font_field(){
		$font_options=get_option('stock_widget_font_options');
	$default_fonts=get_option('stock_widget_default_fonts');
	$current_colors=get_option('stock_widget_color_scheme');
	?>
		<label for="text_color">Color</label><sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" style="text-decoration:none" ref="external nofollow" target="_blank" title="Use hex to pick colors!">[?]</a></sup>:
		<input style="width:70px; text-align:left;" name="text_color" id="text_color" type="text" value="<?php echo $current_colors[0]; ?>"/>
		<label for="font_size">Size: </label>
		<input style="width:29px; text-align:left;" type="text" name="font_size" id="font_size" value="<?php echo $font_options[0]; ?>"/>
		<label for="font_family">Family: </label>
		<input style="width:70px; text-align:left;" name="font_family" id="font_family" list="font_families" autocomplete="on"/>
		<datalist id="font_families">
		<?php
			foreach($default_fonts as $font){
				echo '<option value="'.$font.'">';
			}
		?>
		</datalist>
	<?php
}

function stock_widget_update_size_font_field(){
	$display_size=get_option('stock_widget_display_size');
	$font_options=get_option('stock_widget_font_options');
	$old_data=array($display_size[0],$display_size[1],$font_options[0],$font_options[1]);
	$new_data=array($_POST['stock_widget_width'],$_POST['stock_widget_height'],$_POST['font_size'],$_POST['font_family']);
	if(!is_numeric($new_data[0])){
		return;
	}
	if(!is_numeric($new_data[1])){
		return;
	}
	if(!is_numeric($new_data[2])){
		return;
	}
	$counter=0;
	foreach($new_data as $data){
		if($data==""){
			$new_data[$counter]=$old_data[$counter];
		}
		$counter++;
	}
	if($new_data[2]>32||$new_data[2]<1){
		$new_data[2]=14;
	}
	update_option('stock_widget_display_size', array($new_data[0],$new_data[1]));
	update_option('stock_widget_font_options', array($new_data[2],$new_data[3]));
}

function stock_widget_create_draw_dash_field(){
	if(get_option('stock_widget_draw_vertical_dash')){
		$checked="checked";
	}else{
		$checked="";
	}
	?>
			<input name="create_vertical_dash" type="checkbox" id="create_vertical_dash_box" <?php echo $checked;?>>
			<label for="create_vertical_dash>">Vertical Lines</label>	
			<br>
	<?php

	if(get_option('stock_widget_draw_horizontal_dash')){
		$checked="checked";
	}else{
		$checked="";
	}
	?>
			<input name="create_horizontal_dash" type="checkbox" id="create_horizontal_dash_box" <?php echo $checked;?>>
			<label for="create_horizontal_dash">Horizontal Lines</label>	
	<?php
}


function stock_widget_update_draw_dash_field(){

        update_option('stock_widget_draw_vertical_dash',$_POST['create_vertical_dash']);
        update_option('stock_widget_draw_horizontal_dash',$_POST['create_horizontal_dash']);
}

function stock_widget_create_default_settings_field(){

	$all_settings=get_option('stock_widget_default_settings');
	?>
		<label for="widget_default_settings">Themes: </label>
		<select name="default_settings" id="widget_default_settings" style="width:180px;">
		<option selected> ------- </option>
		<?php 
			foreach($all_settings as $key=>$setting){
				echo '<option value="'.$key.'">'.$setting['name'].'</option>';
			}
		?>
		</select>
	<?php
}

function stock_widget_update_default_settings_field(){
	$selected_setting=$_POST['default_settings'];
	if($selected_setting=='-------'){
		return;
	}
	$all_settings=get_option('stock_widget_default_settings');
	$selected_setting=$all_settings[$selected_setting];

	$option_holder=get_option('stock_widget_font_options');
	$option_holder[1]=$selected_setting['font_family'];
	update_option('stock_widget_font_options', $option_holder);
	$option_holder=get_option('stock_widget_color_scheme');

	$option_holder[0]=$selected_setting['font_color'];
	$option_holder[2]=$selected_setting['back_color1'];
	$option_holder[3]=$selected_setting['back_color2'];
	update_option('stock_widget_color_scheme',$option_holder);

	update_option('stock_widget_draw_vertical_dash',$selected_setting['verti_lines']);

	update_option('stock_widget_draw_horizontal_dash', $selected_setting['hori_lines']);

	update_option('stock_widget_change_style', $selected_setting['check_box']);

}

function stock_widget_create_style_field(){
	echo '
		<p>
			If you have additional CSS rules you want to apply to the
			entire widget (such as alignment or borders) you can add them below.
		</p>
		<p>
			Example: <code>margin:auto; border:1px solid #000000;</code>
		</p>';
	$previous_setting=get_option('stock_widget_advanced_style');
	echo 
	'<input style="width:90%; font-size:14px" type="text" 
	name="widget_advanced_style" id="widget_advanced_style" value="'.$previous_setting.'"/>';

}
function stock_widget_update_style_field(){
	update_option('stock_widget_advanced_style',$_POST['widget_advanced_style']);
}


function stock_widget_create_url_field(){
	echo '
		<p>
			Write the url location of where you want your stocks to link to. Use *stock* to indicate
			the portion of the URL that requires a stock symbol.
		</p>
		<p>
			Example: https://www.google.com/finance?q=*stock*
		</p>';
	$previous_setting=get_option('stock_page_url');
	$old_setting='';
	if($previous_setting==false || $previous_setting==''){
		$old_setting='';
	}else{
		$old_setting=$previous_setting[0].'*stock*'.$previous_setting[1];
	}
	echo 
	'<input style="width:90%; font-size:14px" type="text" 
	name="stock_page_url" id="stock_page_url" value="'.$old_setting.'"/>';

}

function stock_widget_update_url_field(){
	$input=$_POST['stock_page_url'];
	$exploded_input=explode("*stock*", $input);
	if(empty($exploded_input[0])){
		update_option('stock_page_url', '');
	}else{
		update_option('stock_page_url', array($exploded_input[0],$exploded_input[1]));
	}
	

}


?>
