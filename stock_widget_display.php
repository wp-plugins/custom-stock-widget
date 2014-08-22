<?php

/*
 * Generates output table.
 */
add_action('init', 'stock_widget_jquery_init');
function stock_widget_jquery_init() {
	wp_register_script('stock_widget_script',plugins_url('stock_widget_script.js', __FILE__) ,array( 'jquery' ),false, false);
	wp_register_style('stock_widget_style',plugins_url('stock_widget_style.css', __FILE__));
	wp_enqueue_script('stock_widget_script');
	wp_enqueue_style('stock_widget_style');
}
function stock_widget($atts){
	$size=get_option('stock_widget_display_size');
	$color=get_option('stock_widget_color_scheme');
	$font_options=get_option('stock_widget_font_options');
	extract( shortcode_atts( array(
   	'display'		=> get_option('stock_widget_max_display'),
	'height'	=> $size[1],
	'width'	=> $size[0],
	'background_color1' => $color[2],
	'background_color2' => $color[3],
	'text_color' => $color[0],
	'change_box' => get_option('stock_widget_change_style'),
	), $atts ) );

	$stock_categories=get_option('stock_widget_category_stock_list');
	$category=get_the_category();
	$category_name=$category[0]->name;
	$category_name=str_replace(' ','',$category_name);
	$stock_list=(!empty($stock_categories[$category_name]) ? $stock_categories[$category_name] : $stock_categories['Default']);
	$data_list=stock_widget_get_data($stock_list);
	$data_list=$data_list['valid_stocks'];
	$output=stock_widget_create_table($data_list, $display, $width, $height, $text_color, $background_color1,$background_color2);
	return $output;
}
	/*
	* Creates the table
	*/

function stock_widget_create_table($data_list, $display, $width, $height, $text_color, $background_color1,$background_color2){
	$output='';

	$widget_wrap='width:'.$width.'px; height:'.$height.'px;'.get_option('stock_widget_advanced_style');
	$output.= '<div class="stock_table" style="'.$widget_wrap.'">';
	//Prints the stocks in the options. 
	$counter=0;
	$max=$display;
	$display_data=get_option('stock_widget_data_display');
	$number_of_elements=0;
	foreach($display_data as $active_data){
		$number_of_elements+=$active_data;

	}
	$number_of_stocks=0;
	foreach($data_list as $data){
		$number_of_stocks++;
		if($number_of_stocks==$max){
			break;

		}
	}
	if($number_of_stocks==0){
		$number_of_stocks++;
	}
	$element_width=$width/$number_of_elements;
	$element_height=$height/($number_of_stocks);
	$top_position=0;
	$row_color_counter=true;
	switch(get_option('stock_widget_display_type')){
		case "First":
			foreach($data_list as $data){
				if($counter==$max){
					break;
				}
				//$top_or_bottom=1 means the top of the row is rounded. 0 means no rounding, and 2 means bottom is rounded. 
				//Used to give the whole table a rounded look.
				if($counter==0){
					$top_or_bottom=1;

				}elseif($counter==($number_of_stocks-1)){
					$top_or_bottom=2;
				}else{
					$top_or_bottom=0;
				}
				if($row_color_counter){
					$row_background_color=$background_color1;
					$row_color_counter=false;
				}else{
					$row_background_color=$background_color2;
					$row_color_counter=true;
				}
				$output.=stock_widget_create_row($top_position, $row_background_color,$data, $text_color,$element_width, $element_height, $width, $top_or_bottom);
				$top_position+=$element_height;
				$counter++;
			}
		break;
		case "Random":
			$already_done=array();
			//loops until either the counter has reached the max, or all the stocks are printed
			//Prints a new random stock from the list until enough stocks are printed. 
			while($counter<$max && $counter<$number_of_stocks){
				$random_index=array_rand($data_list);
				if(!in_array($random_index, $already_done)){
					//The random index chosen has not yet been printed..
					$already_done[]=$random_index;	
					$data=$data_list[$random_index];
					//$top_or_bottom=1 means the top of the row is rounded. 0 means no rounding, and 2 means bottom is rounded. 
					//Used to give the whole table a rounded look.
					if($counter==0){
						$top_or_bottom=1;

					}elseif($counter==($number_of_stocks-1)){
						$top_or_bottom=2;
					}else{
						$top_or_bottom=0;
					}
					if($row_color_counter){
						$row_background_color=$background_color1;
						$row_color_counter=false;
					}else{
						$row_background_color=$background_color2;
						$row_color_counter=true;
					}
					$output.=stock_widget_create_row($top_position, $row_background_color,$data, $text_color,$element_width, $element_height, $width, $top_or_bottom);
					$top_position+=$element_height;
					$counter++;
				}else{
					//The random index chosen has already been printed.
					continue;
				}

			}
		break;

		default:
		//echo "Invalid display option.";
		break;
	
	}
	$output.= "</div>";
	return $output;
}
/* background-color: '.$background_color.';border:1px solid '. $border_color.';
 * Builds a single row with the given data.
 */
function stock_widget_create_row($top_position, $background_color, $stock_data, $text_color, $element_width, $element_height, $row_width, $top_or_bottom){
	if(empty($stock_data['last_val'])){
		return;
		}
	$display_data=get_option('stock_widget_data_display');
	$font_options=get_option('stock_widget_font_options');
	$widget_content="";
	$colors=get_option('stock_widget_color_scheme');
	$font_size=$font_options[0];
	$font_family=$font_options[1];
	$left_position=0;
		switch($top_or_bottom){
	
		case 0:
			$border_radius='';
		break;
	
		case 1:
			$border_radius='border-top-right-radius: 4px;border-top-left-radius: 4px;';
		break;
			
		case 2:
			$border_radius='border-bottom-right-radius: 4px;border-bottom-left-radius: 4px;';
		break;
	
		default:
	
		break;

	}
	$widget_row='background-color:'.$background_color.' ; top:'.$top_position.'px;height: '.$element_height.'px;width: '.$row_width.'px;'.$border_radius;
	$widget_data='font-size:'.$font_options[0].
	'px; font-family:'.$font_options[1].',serif; width:'.$element_width.'px;';
	$change_box_height=get_option('stock_widget_font_options');
	$change_box_height=$change_box_height[0]+4;
	$change_box_width=$element_width*.7;
	$change_box_top_margin=($element_height/2)-($change_box_height/2+2);
	$link_url=get_option('stock_page_url');
	if($link_url==false||$link_url==''){

	}else{
		$output.='<a href="'.$link_url[0].$stock_data['stock_sym'].$link_url[1].'" target="_blank" rel="external nofollow">';
	}
	$output.= '<div class="stock_table_row" style="'.$widget_row.'">';

	if(get_option('stock_widget_draw_vertical_dash')){
		$output.=stock_widget_draw_vertical_dashes($element_height, $row_width);

	}

	if(get_option('stock_widget_draw_horizontal_dash')){
		$output.=stock_widget_draw_horizontal_dashes($element_height, $row_width, $top_or_bottom);

	}

	$display_options=get_option('stock_widget_data_display');
	if($display_options['stock_sym']==1){

		$data_item=$stock_data['stock_sym'];
		if($data_item=="^GSPC"){
			$data_item='S&P500';
		}elseif($data_item=="^IXIC"){
			$data_item="NASDAQ";
		}
		$output.= 		'<div class="stock_table_symbol stock_table_element" style="left:'.$left_position.'px; color: '.$text_color.';line-height:'.$element_height.'px;'.$widget_data.' ?>">';
		$output.= 				$data_item;

		$output.=  		'</div>';
		$left_position+=$element_width;
	}

	if($display_options['last_val']==1){
		$data_item=$stock_data['last_val'];
		$data_item=round($data_item, 2);
		$output.= 		'<div class="stock_table_last_value stock_table_element" style="left:'.$left_position.'px;text-align:center; color: '.$text_color.';line-height:'.$element_height.'px;'.$widget_data.' ?>">';
		$output.= 				$data_item;
		$output.=  		'</div>';
		$left_position+=$element_width;
	}

	if($display_options['change_val']==1){
		$data_item=$stock_data['change_val'];
		if($data_item>0){
			$change_color="green";
			$change_border="#006800";
			$data_item=round($data_item, 2);
			$data_item="+".$data_item;
		
		}elseif($data_item<0){
			$change_color="red";
			$change_border="#990000";
			$data_item=round($data_item, 2);

		}else{
			$change_color="grey";
			$change_border='#7A7A7A';
			$data_item=round($data_item, 2);
			$data_item="+".$data_item.".00";

		}
		//the div that holds this section
		$output.='<div class="stock_table_change_value stock_table_element" style="left:'.$left_position.'px;color: '.$text_color.';'.$widget_data.' ?>">';
			//the div that holds the colorful change box
			switch(get_option('stock_widget_change_style')){
				case 'Box':
					$change_val_css='
						border-radius:4px;
						border: 2px '.$change_border.' solid;
						background-color: '.$change_color.';
						line-height: '.$change_box_height.'px;
						height:'.$change_box_height.'px;
						width:'.$change_box_width.'px;
						margin:'.$change_box_top_margin.'px 0px 0px '.($element_width*.15-2).'px;';
				break;
				case 'Parentheses':
					$change_val_css='color:'.$change_color.';line-height:'.$element_height.'px;';
				break;
				case 'None':
					$change_val_css='line-height:'.$element_height.'px;';
				break;
				default:
					$change_val_css='line-height:'.$element_height.'px;';
				break;
			}
		$output.=	'<div style="text-align:center;'.$change_val_css.'">';		
		$output.= 				$data_item;
		$output.=  			'</div>';
		$output.=  		'</div>';
		$left_position+=$element_width;
	}

	if($display_options['change_percent']==1){
		$data_item=$stock_data['change_percent'];
		$data_item=str_replace('%','',$data_item);
		if($data_item>0){
			$change_color="green";
			$change_border="#006800";
			$data_item=round($data_item, 2);
			$data_item="+".$data_item.'%';
		
		}elseif($data_item<0){
			$change_color="red";
			$change_border="#990000";
			$data_item=round($data_item, 2);
			$data_item=$data_item.'%';
		}else{
			$change_color="grey";
			$change_border='#7A7A7A';
			$data_item=round($data_item, 2);
			$data_item="+".$data_item.".00%";
		}
		$output.= 		'<div class="stock_table_change_percent stock_table_element" style="left:'.$left_position.'px; color: '.$text_color.';'.$widget_data.' ?>">';		
			switch(get_option('stock_widget_change_style')){
				case 'Box':
					$change_percent_css='border-radius:4px;border: 2px '.$change_border.' solid;background-color: '.$change_color.';line-height: '.$change_box_height.'px;height:'.$change_box_height.'px;width:'.$change_box_width.'px;margin:'.$change_box_top_margin.'px 0px 0px '.($element_width*.15-2).'px;';
				break;
				case 'Parentheses':
					$change_percent_css='color:'.$change_color.';line-height:'.$element_height.'px;';
					$data_item='('.$data_item.')';
				break;
				case 'None':
					$change_percent_css='line-height:'.$element_height.'px;';
				break;
				default:
					$change_percent_css='line-height:'.$element_height.'px;';
				break;									
			}
			$output.=  '<div style="text-align:center;'.$change_percent_css.'">';	
				$output.= 				$data_item;
			$output.=  		'</div>';
		$output.=  		'</div>';
		$left_position+=$element_width;
	}
		

	$output.=  '</div>';
	if($link_url==false||$link_url==''){

	}else{
		$output.='</a> ';
	}
	return $output;

}

function stock_widget_draw_vertical_dashes($element_height, $row_width){

	$dash_css='height:'.$element_height.'px;';
	$num_of_dashes=round($row_width/6)-1;
	$dash_space=$row_width/$num_of_dashes;
	$dash_left=$dash_space-1;
	$output='';
	$output.='<div class="stock_table_vertical_dashes">';
	for($i=1;$i<$num_of_dashes;$i++){
		
		$output.='<div class="widget_verti_dash" style="'.$dash_css.'left:'.$dash_left.'px ;">';
		$output.='</div>';	
		$dash_left+=$dash_space;
	}
	$output.='</div>';
	return $output;
}

function stock_widget_draw_horizontal_dashes($element_height, $row_width, $top_or_bottom){
		$dash_css='width:'.($row_width-20).'px;';

		$output='';
		$output.='<div class="stock_table_horizontal_dashes">';
		switch($top_or_bottom){
		//Row is in the middle
		case 0:
			$output.='<div class="widget_horizontal_dash" style="'.$dash_css.'">';
			$output.='</div>';	
			$output.='<div class="widget_horizontal_dash" style="'.$dash_css.'top:'.($element_height-1).'px ;">';
			$output.='</div>';	
		break;
		//Row is at the bottom
		case 2:
			$output.='<div class="widget_horizontal_dash" style="'.$dash_css.'">';
			$output.='</div>';	
		break;
		//Row is at the top
		case 1:
			$output.='<div class="widget_horizontal_dash" style="'.$dash_css.'top:'.($element_height-1).'px ;">';
			$output.='</div>';	
		break;
	
		default:
	
		break;

	}
	$output.='</div>';
	return $output;
}

?>
