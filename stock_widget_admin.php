<?php
namespace stockWidget;
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\');


global $wpdb;

global $list_table; //needs to be created inside stock_widget_add_screen_options, but utilized within stock_widget_list_page

global $relevad_plugins;
if (!is_array($relevad_plugins)) {
    $relevad_plugins = array();
}
$relevad_plugins[] = array(
'url'  => admin_url('admin.php?page=stock_widget_list'),
'name' => 'Custom Stock Widget'
);


//NOTE: These will automatically be within the namespace
define(NS.'SP_TABLE_NAME', $wpdb->prefix . 'stock_widgets');
define(NS.'SP_CHARSET',    $wpdb->get_charset_collate()); //requires WP v3.5

define(NS.'SP_CURRENT_VERSION', '2.1');   //NOTE: should always match Version: ### in the plugin special comment
define(NS.'SP_TYPE', 'widget');
define(NS.'SP_VALIDATION_PARAMS', <<< DEFINE
{
"max_display":   [1,   100],
"width":         [100, 500],
"height":        [100, 1000],
"font_size":     [5,   32],
"change_styles": ["None", "Box", "Parentheses"]
}
DEFINE
);  //access with (array)json_decode(SP_VALIDATION_PARAMS);

// Feature Improvement: think about putting each individual config into a class, does that buy us anything?
// http://stackoverflow.com/questions/1957732/can-i-include-code-into-a-php-class

include plugin_dir_path(__FILE__) . 'stock_plugin_utils.php'; //used to contain validation functions
include plugin_dir_path(__FILE__) . 'relevad_plugin_utils.php';
include plugin_dir_path(__FILE__) . 'stock_plugin_cache.php';
include plugin_dir_path(__FILE__) . 'stock_widget_display.php';

function stock_widget_create_db_table() {  //NOTE: for brevity into a function
    $table_name = SP_TABLE_NAME;
    $charset    = SP_CHARSET;
    static $run_once = true; //on first run = true
    if ($run_once === false) return;
    
    //NOTE: later may want: 'default_market'    => 'DOW',   'display_options_strings' 
    $sql = "CREATE TABLE {$table_name} (
    id                      mediumint(9)                    NOT NULL AUTO_INCREMENT,
    name                    varchar(50)  DEFAULT ''         NOT NULL,
    bg_color1               varchar(7)   DEFAULT '#000000'  NOT NULL,
    bg_color2               varchar(7)   DEFAULT '#7F7F7F'  NOT NULL,
    font_color              varchar(7)   DEFAULT '#5DFC0A'  NOT NULL,
    font_family             varchar(20)  DEFAULT 'Times'    NOT NULL,
    display_order           varchar(20)  DEFAULT 'Preset'   NOT NULL,
    change_style            varchar(20)  DEFAULT 'Box'      NOT NULL,
    font_size               tinyint(3)   DEFAULT 12         NOT NULL,
    width                   smallint(4)  DEFAULT 300        NOT NULL,
    height                  smallint(4)  DEFAULT 70         NOT NULL,
    data_display            tinyint(2)   DEFAULT 30         NOT NULL,
    display_number          tinyint(3)   DEFAULT 5          NOT NULL,
    draw_vertical_lines     tinyint(1)   DEFAULT 0          NOT NULL,
    draw_horizontal_lines   tinyint(1)   DEFAULT 0          NOT NULL,
    show_header             tinyint(1)   DEFAULT 0          NOT NULL,
    stock_page_url          text         NOT NULL,
    stock_list              text         NOT NULL,
    advanced_style          text         NOT NULL,
    UNIQUE KEY name (name),
    PRIMARY KEY (id)
    ) {$charset};";
    
    //NOTE: Extra spaces for readability screw up dbDelta, so we remove those
    $sql = preg_replace('/ +/', ' ', $sql);
    //NOTE: WE NEED 2 spaces exactly between PRIMARY KEY and its definition.
    $sql = str_replace('PRIMARY KEY', 'PRIMARY KEY ', $sql);
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql ); //this will return an array saying what was done, if we want to output it
    $run_once = false;
}

function stock_widget_activate() {
    $current_version = SP_CURRENT_VERSION;

    if (!get_option('stock_widget_category_stock_list') && !get_option('stock_widget_per_category_stock_lists')) {
        //if neither of these exist then assume initial install
        stock_widget_create_db_table();
        $values = array( //NOTE: the rest should all be the defaults
                        'id'             => 1, //explicitly set this or else mysql configs where the default is not 1 will be broken
                        'name'           => 'Default Settings',
                        'advanced_style' => 'margin: auto;',
                        'stock_page_url' => 'https://www.google.com/finance?q=__STOCK__'
                        );
        sp_add_row($values);
        add_option('stock_widget_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL'));
        add_option('stock_widget_version',                         $current_version);
        add_option('stock_widget_version_text', "Initial install v{$current_version}");
    }
}
register_activation_hook( $main_plugin_file, NS.'stock_widget_activate' ); //references  $main_plugin_file  which was defined in the bootstrap file



//*********cleanup and conversion functions for updating versions *********
function stock_widget_handle_update() {
    $current_version = SP_CURRENT_VERSION;
    
    $db_version = get_option('stock_widget_version', '0');

    //NOTE: Don't forget to add each and every version number as a case
    switch($db_version) {
        case '0': //if versioning did not exist yet, then use old method
            //version 1.0 -> 1.1 
            if (get_option('stock_widget_category_stock_list')) { //this old option exists
                stock_plugin_convert_old_category_stock_list();
                
                $tmp = get_option('stock_widget_data_display');
                update_option('stock_widget_data_display', array_values($tmp));
            }
            //version 1.1 -> 1.3
            if (get_option('stock_widget_color_scheme')) { //this old option exists
                stock_widget_convert_old_options(); 
            }

        case '1.3.2': //Added this versioning system in this version
        case '1.3.3':
        case '1.3.4':
        case '1.3.5':
            stock_widget_create_db_table(); //Added table storage structure in 1.4
            
            $default_settings = get_option('stock_widget_default_settings', false);
            if ($default_settings !== false) {
                unset($default_settings['show_headers']); //if this exists get rid of it
                $default_settings['name'] = 'Default Settings';
                $default_settings['id']   = 1; //force the ID to be 1
                if (false !== sp_add_row($default_settings))
                    delete_option('stock_widget_default_settings');
            }
            else {
                stock_widget_activate(); //Recall the activate function and this problem should be fixed
            }

        case '1.4':
            stock_widget_create_db_table(); //bugfix for table storage in 1.4.1
            
        case '1.4.1':
	case '2.0':
        case '2.0.1':
            //*****************************************************
            //this will always be right above current_version case
            //keep these 2 updates paired
            update_option('stock_widget_version',      $current_version);
            update_option('stock_widget_version_text', " updated from v{$db_version} to");
            //NOTE: takes care of add_option() as well
        case $current_version:
            break;
        //NOTE: if for any reason the database entry disapears again we might have a problem updating or performing table modifcations on tables already modified.
        default: //this shouldn't be needed
            //future version? downgrading?
            update_option('stock_widget_version_text', " found v{$db_version} current version");
            break;
    }
}

function stock_widget_admin_enqueue($hook) {
    $current_version = SP_CURRENT_VERSION;

    //echo "<!-- testing {$hook} '".strpos($hook, 'stock_widget')."'-->";
    //example: relevad-plugins_page_stock_widget_admin
    if (strpos($hook, 'stock_widget') === false) {return;} //do not run on other admin pages

    wp_register_style ('stock_plugin_admin_style',  plugins_url('stock_plugin_admin_style.css', __FILE__), false,             $current_version);
    wp_register_script('stock_plugin_admin_script', plugins_url('stock_plugin_admin_script.js', __FILE__), array( 'jquery' ), $current_version, false);

    wp_enqueue_style ('stock_plugin_admin_style');
    wp_enqueue_script('stock_plugin_admin_script');
    
    stock_widget_scripts_enqueue(true); //we also need these scripts
}
add_action('admin_enqueue_scripts', NS.'stock_widget_admin_enqueue');

function stock_widget_admin_actions() {
    
    relevad_plugin_add_menu_section(); //imported from relevad_plugin_utils.php
    
           //add_submenu_page( 'options-general.php', $page_title, $menu_title,         $capability,     $menu_slug,           $function ); // do not use __FILE__ for menu_slug
    $hook1 = add_submenu_page('relevad_plugins', 'StockWidgets',   'StockWidgets',      'manage_options', 'stock_widget_list',   NS.'stock_widget_list_page'); 
    $hook2 = add_submenu_page('relevad_plugins', 'New Widget',     '&rarr; New Widget', 'manage_options', 'stock_widget_addnew', NS.'stock_widget_addnew'); 
    
    add_action( "load-{$hook1}", NS.'stock_widget_add_screen_options' ); 
    //this adds the screen options dropdown along the top
}
add_action('admin_menu', NS.'stock_widget_admin_actions');

function stock_widget_add_screen_options() {
    global $list_table;
    
    $option = 'per_page';
    $args = array(
         'label' => 'Shortcodes',
         'default' => 10,
         'option' => 'shortcodes_per_page'
    );
    add_screen_option( $option, $args );
    
    //placed in this function so that list_table can get the show/hide columns checkboxes automagically
    $list_table = new stock_shortcode_List_Table(); //uses relative namespace automatically
}

function stock_widget_set_screen_option($status, $option, $value) {
    //https://www.joedolson.com/2013/01/custom-wordpress-screen-options/
    //standard screen options are not filtered in this way
    //if ( 'shortcodes_per_page' == $option ) return $value;
    
    //return $status;
    
    return $value;
}
add_filter('set-screen-option', NS.'stock_widget_set_screen_option', 10, 3);


//ON default settings, should restore to defaults
//ON other shortcodes, should just reload the page
function stock_widget_reset_options() {
    update_option('stock_widget_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces
    
    $stock_widget_default_settings = Array(
        //'name'                  => 'Default Settings', //redundant
        'data_display'          => array(0,1,1,1,1,0),
        //'default_market'      => 'DOW',
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
        'stock_page_url'        => 'https://www.google.com/finance?q=__STOCK__'
        );
    
    sp_update_row($stock_widget_default_settings, array('name' => 'Default Settings'));
    
    stock_plugin_notice_helper("Reset 'Default Settings' to initial install values.");
}


function stock_widget_addnew() { //default name is the untitled_id#
    
    stock_widget_handle_update();
    
    //Add row to DB with defaults, name = same as row id
    $values = array( //NOTE: the rest should all be the defaults
                        //'name'           => 'Default Settings', //no name to start with, have to do an update after
                        'advanced_style' => 'margin: auto;',
                        'stock_page_url' => 'https://www.google.com/finance?q=__STOCK__'
                        );
    $new_id = sp_add_row($values);
    
    if ($new_id !== false) {
        stock_plugin_notice_helper("Added New Widget");
        stock_widget_admin_page($new_id);
    }
    else {
        stock_plugin_notice_helper("ERROR: Unable to create new widget. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
    }
    
    return;
}

// Default Admin page.
// PAGE for displaying all previously saved widgets.
function stock_widget_list_page() {
    global $list_table;
    
    stock_widget_handle_update();

    //This page is referenced from all 3 options: copy, edit, delete and will transfer control to the appropriate function
    $action = (isset($_GET['action'])    ? $_GET['action']    : '');
    $ids    = (isset($_GET['shortcode']) ? $_GET['shortcode'] : false); //form action post does not clear url params

    //action = -1 is from the search query
    if (!empty($action) && $action !== '-1' && !is_array($ids) && !is_numeric($ids)) {
        stock_plugin_notice_helper("ERROR: No shortcode ID for action: {$action}.", 'error');
        $action = ''; //clear the action so we skip to default switch action
    }
    
    switch ($action) {
        case 'copy':
            if (is_array($ids)) $ids = $ids[0];
            $old_id = $ids;
            $ids = sp_clone_row((int)$ids);
            if ($ids === false) {
                stock_plugin_notice_helper("ERROR: Unable to clone shortcode {$old_id}. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
                return;
            }
            stock_plugin_notice_helper("Cloned {$old_id} to {$ids}");
        case 'edit':
            if (is_array($ids)) $ids = $ids[0];
            stock_widget_admin_page((int)$ids);
            break;

        case 'delete': //fall through to display the list as normal
            if (! isset($_GET['shortcode'])) {
                stock_plugin_notice_helper("ERROR: No shortcodes selected for deletion.", 'error');
            }
            else {
                $ids = $_GET['shortcode'];
                if (!is_array($ids)) {
                    $ids = (array)$ids; //make it an array
                }
                sp_delete_rows($ids); //NOTE: no error checking needed, handled inside
            }
        default:
            $current_version = SP_CURRENT_VERSION;
            
            $version_txt = get_option('stock_widget_version_text', '') . " v{$current_version}";
            update_option('stock_widget_version_text', ''); //clear the option after we display it once
        
            $list_table->prepare_items();
            
            //$thescreen = get_current_screen();
            
            echo <<<HEREDOC
            <div id="sp-options-page">
                <h1>Custom Stock Widget</h1><sub>{$version_txt}</sub>
                <p>The Custom Stock Widget plugin allows you to create and run your own custom stock table widgets.</p>
                <p>To configure a widget, click the edit button below that widget's name. Or add a new widget using the link below.</p>
                <p>To place a widget onto your site, copy a shortcode from the table below, or use the default shortcode of <code>[stock-widget]</code>, and paste it into a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
                Alternatively, you can use <code>&lt;?php echo do_shortcode('[stock-widget]'); ?&gt;</code> inside your theme files or a <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.</p>
            </div>
                <div id='sp-list-table-page' class='wrap'>
HEREDOC;
            echo "<h2>Available Stock Widgets <a href='" . esc_url( menu_page_url( 'stock_widget_addnew', false ) ) . "' class='add-new-h2'>" . esc_html( 'Add New' ) . "</a>";

            if ( ! empty( $_REQUEST['s'] ) ) {
                echo sprintf( '<span class="subtitle">Search results for &#8220;%s&#8221;</span>', esc_html( $_REQUEST['s'] ) );
            }
            echo "</h2>";
          
            echo "<form method='get' action=''>"; //this arrangement of display within the form, is copied from contactform7
                echo "<input type='hidden' name='page' value='" . esc_attr( $_REQUEST['page'] ) . "' />";
                $list_table->search_box( 'Search Stock Widgets', 'stock-widget' ); 
                $list_table->display();  //this actually renders the table itself
            echo "</form></div>";
            
            break;
    }
}


/** Used for edit widgets. & after copy/add **/
function stock_widget_admin_page($id = '') {

    if ($id === '') {
        stock_plugin_notice_helper("ERROR: No shortcode ID found", 'error'); return; //This should never happen
    }
    
    $ds_flag = false; //flag used for handling specifics of default settings
    if ($id === 1) {
        $ds_flag = true;
    }

    if (isset($_POST['save_changes'])) {
        if ($ds_flag) stock_plugin_update_per_category_stock_lists();
        stock_widget_update_options($id); //pass in the unchanged settings
        stock_plugin_notice_helper("Changes saved (Random: ".rand().")"); //Remove after tracker #23768
    } 
    elseif (isset($_POST['reset_options'])) { //just reload the page if from non Default Settings
        if ($ds_flag)
            stock_widget_reset_options();
        else
            stock_plugin_notice_helper("Reverted all changes");
    }
    
    $shortcode_settings = sp_get_row($id, 'id'); //NOTE: have to retrieve AFTER update
    if ($shortcode_settings === null) {
        stock_plugin_notice_helper("ERROR: No shortcode ID '{$id}' exists. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
        return;
    }

    $the_action = '';
    if (!isset($_GET['action']) || $_GET['action'] != 'edit') {
        $the_action = '?page=stock_widget_list&action=edit&shortcode=' . $id; //for turning copy -> edit
    }
    
    $reset_btn    = "Revert Changes";
    $reset_notice = "<a class='submitdelete' href='?page=stock_widget_list&action=delete&shortcode={$id}' onclick='return showNotice.warn()'>Delete Permenantly</a>";
    if ($ds_flag) {
        $reset_btn    = "Reset to Defaults";
        $reset_notice = "<sup>*</sup><br /><sup>* NOTE: 'Reset to Defaults' also clears all default stock lists.</sup>";
    }
    
    echo <<<HEREDOC
<div id="sp-options-page">
    <h1>Edit Custom Stock Widget</h1>
    <p>Choose your stocks and display settings below.</p>
    <form action='{$the_action}' method='POST'>
HEREDOC;
    
    echo "<div id='sp-form-div' class='postbox-container sp-options'>
            <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                <div id='referrers' class='postbox'>";
    if (!$ds_flag) {
        echo      "<div class='inside'>";
        stock_widget_create_name_field($shortcode_settings);
    }
    else {
        echo "     <h3 class='hndle'>Default Shortcode Settings</h3>
                    <div class='inside'>";
    }
                        stock_widget_create_template_field();
                        
    echo "              <div class='sp-options-subsection'>
                            <h4>Widget Config</h4>";
                                stock_plugin_cookie_helper(1);
                                stock_widget_create_widget_config_section($shortcode_settings);
    echo "                  </div>
                        </div>
                        <div class='sp-options-subsection'>
                            <h4>Text Config</h4>";
                                stock_plugin_cookie_helper(2);
                                stock_widget_create_text_config($shortcode_settings);
    echo "                  </div>
                        </div>
                        <div class='sp-options-subsection'>
                            <h4>Stock Display Config</h4>";
                                stock_plugin_cookie_helper(3);
                                stock_widget_create_display_options($shortcode_settings);
    echo "                  </div>
                        </div>
                       <div class='sp-options-subsection'>
                            <h4>Advanced Styling</h4>";
                                stock_plugin_cookie_helper(4);
                                stock_widget_create_style_field($shortcode_settings);
    echo "                  </div>
                        </div>
                       <div class='sp-options-subsection'>
                            <h4>URL Link</h4>";
                                stock_plugin_cookie_helper(5);
                                stock_widget_create_url_field($shortcode_settings);
    echo "                  </div>
                </div>
            </div>
                </div><!--end referrers -->
            </div>
            <div id='publishing-actions'>
                <input type='submit' name='save_changes'  value='Save Changes' class='button-primary' />
                <input type='submit' name='reset_options' value='{$reset_btn}' class='button-primary' />
                {$reset_notice}
            </div>
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
                            if ($ds_flag) stock_plugin_create_per_category_stock_lists();
                            else          stock_plugin_create_stock_list_section($shortcode_settings);
    echo "              </div>
                    </div>
                </div>
        </div>";

    $the_name = '';
    if (!$ds_flag) $the_name = " name='{$shortcode_settings['name']}'";
    echo <<<HEREDOC
        </form>
        <div id="sp-preview" class="postbox-container sp-options">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                <div id="referrers" class="postbox">
                    <h3 class="hndle"><span>Preview</span></h3>
                    <div class="inside">
                       <p>Based on the last saved settings, this is what the shortcode <code>[stock-widget{$the_name}]</code> will generate:</p>
HEREDOC;

    echo do_shortcode("[stock-widget{$the_name}]");
    echo <<<HEREDOC
                           <p>To preview your latest changes you must first save changes.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
</div><!-- end options page -->
HEREDOC;
}


//NOTE: not moved to the top as a define, because if we have to call json_decode anyways whats the point
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
            'name'                  => 'Minimal (black on white)', 
            'font_family'           => 'Arial', 
            'font_color'            => '#000000', 
            'bg_color1'             => '#FFFFFF', 
            'bg_color2'             => '#FFFFFF', 
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
  
    echo "<label for='input_template'>Template: </label>
          <select id='input_template' name='template' style='width:250px;'>
             <option selected> ------- </option>";

            foreach($all_templates as $key=>$template){
                echo "<option value='{$key}'>{$template['name']}</option>";
            }

    echo "</select>
        <input type='submit' name='save_changes'  value='Apply' class='button-primary' />&nbsp;<sup>*</sup>
        <br/>
        <sup>* NOTE: Not all options are over-written by template</sup>";
}

function stock_widget_update_options($id) {

    $unchanged = sp_get_row($id, 'id');
    $validation_params = (array)json_decode(SP_VALIDATION_PARAMS);
    
    $selected_template = $_POST['template'];  //NOTE: if this doesn't exist it'll be NULL
    $all_templates     = stock_widget_templates();
    
    $template_settings = array(); 
    if(array_key_exists($selected_template, $all_templates)) {
        $template_settings = $all_templates[$selected_template];
        unset($template_settings['name']); //throw out the name or we'll end up overwriting this shortcode's name
    }

    $settings_new = array();
    
    $new_display_options = array( //these are checkboxes
            0, //market
            1, //stock symbol
            (array_key_exists('last_value',     $_POST) ? 1 : 0),
            (array_key_exists('change_value',   $_POST) ? 1 : 0),
            (array_key_exists('change_percent', $_POST) ? 1 : 0),
            0  //last trade
    );
    $settings_new['data_display']          = $new_display_options;
    $settings_new['draw_vertical_lines']   = (array_key_exists('vertical_dash',   $_POST) ? 1 : 0);
    $settings_new['draw_horizontal_lines'] = (array_key_exists('horizontal_dash', $_POST) ? 1 : 0);
    $settings_new['show_header']           = (array_key_exists('show_header',     $_POST) ? 1 : 0);
    
    $settings_new['display_order'] = $_POST['display_type']; //these are dropdowns so no validation necessary -- unless someone deliberately tries to post garbage to us
    $settings_new['change_style']  = $_POST['change_style'];
    
    $tmp = relevad_plugin_validate_integer($_POST['max_display'],  $validation_params['max_display'][0],  $validation_params['max_display'][1],  false);
    if ($tmp) {
    $settings_new['display_number'] = $tmp;
    }
    
    $settings_new['width']  = relevad_plugin_validate_integer($_POST['width'],  $validation_params['width'][0],  $validation_params['width'][1],  $unchanged['width']);
    $settings_new['height'] = relevad_plugin_validate_integer($_POST['height'], $validation_params['height'][0], $validation_params['height'][1], $unchanged['height']);

    $settings_new['font_size']   = relevad_plugin_validate_integer(    $_POST['font_size'],   $validation_params['font_size'][0],  $validation_params['font_size'][1],  $unchanged['font_size']);
    $settings_new['font_family'] = relevad_plugin_validate_font_family($_POST['font_family'], $unchanged['font_family']);

    $settings_new['font_color'] = relevad_plugin_validate_color($_POST['text_color'],        $unchanged['font_color']);
    $settings_new['bg_color1']  = relevad_plugin_validate_color($_POST['background_color1'], $unchanged['bg_color1']);
    $settings_new['bg_color2']  = relevad_plugin_validate_color($_POST['background_color2'], $unchanged['bg_color2']);
    
    $settings_new['stock_page_url'] = $_POST['stock_page_url'];
    
    $tmp = trim($_POST['widget_advanced_style']); //strip spaces
    if ($tmp != '' && substr($tmp, -1) != ';') { $tmp .= ';'; } //poormans making of a css rule
    $settings_new['advanced_style'] = $tmp;
    
    //****** fix scaling *******
    //NOTE: we would have to increase the overall width to compensate.
    //NOTE: Header overlap is the biggest problem
    //this section is to fix the width/height attributes so that incase the ticker would have had overlapping text, it fixes itself to a minimum acceptable level
    $minimum_width = $settings_new['font_size'] * 4 * array_sum($new_display_options);  //point font * 4 characters * X elements ~ aproximate
    if ($minimum_width > $settings_new['width']) {
        stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> Chosen font size of {$settings_new['font_size']} when used with width of {$settings_new['width']} could cause overlap of text.", 'error');
    }
    //****** end fix scaling ******* 
    
    //last handle this shortcode's stock list and name if either exist
    if (isset($_POST['stocks_for_shortcode'])) {
        $settings_new['stock_list'] = stock_plugin_validate_stock_list($_POST['stocks_for_shortcode']);
    }
    
    if (isset($_POST['shortcode_name']) && $_POST['shortcode_name'] !== $unchanged['name']) {
        //check if other than - and _  if the name is alphanumerics
        if (! ctype_alnum(str_replace(array(' ', '-', '_'), '', $_POST['shortcode_name'])) ) {
            stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> Allowed only alphanumerics and - _ in shortcode name.<br/>Name reverted!", 'error');
        }
        elseif (sp_name_used($_POST['shortcode_name'])) {
            stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> Name '{$_POST['shortcode_name']}' is already in use by another shortcode<br/>Name reverted!", 'error');
        }
        else {
            $settings_new['name'] = $_POST['shortcode_name'];
        }
        //NOTE: 50 chars limit but this will be auto truncated by mysql, and enforced by html already
    }
    
    //now merge template settings > post changes > old unchanged settings in that order
    sp_update_row(array_replace($unchanged, $settings_new, $template_settings), array('id' => $id));
}

function stock_widget_create_name_field($shortcode_settings) {
    echo "<label for='input_shortcode_name'>Shortcode Name:</label> <sub>(limit 50 chars) (alphanumeric and - and _ only)</sub><br/>
    <input id='input_shortcode_name' name='shortcode_name' type='text' maxlength='50' value='{$shortcode_settings['name']}' class='shortcode_name'/>";
}

function stock_widget_create_widget_config_section($shortcode_settings) {
    echo <<< HEREDOC
        <label for="input_width">Width: </label>
        <input  id="input_width"  name="width"  type="text" value="{$shortcode_settings['width']}"  class="itxt"/>
        <label for="input_height">Height: </label>
        <input  id="input_height" name="height" type="text" value="{$shortcode_settings['height']}" class="itxt"/>
        <br />
        <label for="input_max_display">Maximum number of stocks displayed: </label>
        <input  id="input_max_display" name="max_display" type="text" value="{$shortcode_settings['display_number']}" class="itxt" style="width:40px;" />
        <br />
        <label for="input_background_color1">Odd Row Background Color:</label>
        <input  id="input_background_color1" name="background_color1" type="text" value="{$shortcode_settings['bg_color1']}" class="itxt color_input" style="width:99px;" />
        <sup id="background_color_picker_help1"><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
        <script>enhanceTypeColor("input_background_color1", "background_color_picker_help1");</script>
        <br />
        <label for="input_background_color2">Even Row Background Color:</label>
        <input  id="input_background_color2" name="background_color2" type="text" value="{$shortcode_settings['bg_color2']}" class="itxt color_input" style="width:99px;" />
        <sup id="background_color_picker_help2"><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
        <script>enhanceTypeColor("input_background_color2", "background_color_picker_help2");</script>
HEREDOC;
}


function stock_widget_create_text_config($shortcode_settings) {
    $default_fonts  = array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times");  //maybe extract this list into utils

    echo <<< HEREDOC
        <label for="input_text_color">Color: </label>
        <input  id="input_text_color" name="text_color" type="text" value="{$shortcode_settings['font_color']}" class="itxt color_input" style="width:100px;" />
        <sup id="text_color_picker_help"><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
        <script>enhanceTypeColor("input_text_color", "text_color_picker_help");</script>
        
        <label for="input_font_size">Size: </label>
        <input  id="input_font_size" name="font_size" type="text" value="{$shortcode_settings['font_size']}" class="itxt" style="width:40px;"/>
        
        <br />
        <label for="input_font_family">Font-Family: </label>
        <input  id="input_font_family" name="font_family" list="font_family" value="{$shortcode_settings['font_family']}" autocomplete="on" style="width:125px;"/>
        <datalist id="font_family">
HEREDOC;

    foreach($default_fonts as $font){
        echo "<option value='{$font}'></option>";
    }

    echo "</datalist>";

}

function stock_widget_create_display_options($shortcode_settings) {
    $validation_params = (array)json_decode(SP_VALIDATION_PARAMS);
    
    $all_orders      = array('Preset', 'A-Z', 'Z-A', 'Random');
    //NOTE for data_display: options 0 and 1 are "market" and the "stock symbol" itself
    //      option 5 is the "last trade"
    ?>
    
    <label for='input_show_header'>Show Headers</label>
    <input  id='input_show_header'    name='show_header'    type='checkbox' <?php checked($shortcode_settings['show_header']); ?>>
    <br />
    <label for='input_last_value'>Last Value</label>
    <input  id='input_last_value'     name='last_value'     type='checkbox' <?php checked(1, $shortcode_settings['data_display'][2]);?>>
    <br />
    <label for='input_change_value'>Change Value</label>
    <input  id='input_change_value'   name='change_value'   type='checkbox' <?php checked(1, $shortcode_settings['data_display'][3]);?>>
    <br />
    <label for='input_change_percent'>Change Percent</label>
    <input  id='input_change_percent' name='change_percent' type='checkbox' <?php checked(1, $shortcode_settings['data_display'][4]);?>>
    <br />
    <label for='input_vertical_dash'>Vertical Dash</label>
    <input  id='input_vertical_dash'  name='vertical_dash'  type='checkbox' <?php checked($shortcode_settings['draw_vertical_lines']);?>>
    <br />
    <label for='input_horizontal_dash'>Horizontal Dash</label>
    <input  id='input_horizontal_dash'name='horizontal_dash'type='checkbox' <?php checked($shortcode_settings['draw_horizontal_lines']);?>>
    <br />
    <br />
    
    <label for="input_display_type">Order: </label>
    <select id="input_display_type" name="display_type"  style="width: 100px;">
    <?php 
        foreach($all_orders as $order) {
            echo "<option " . selected($order, $shortcode_settings['display_order']) . ">{$order}</option>";
        }
    ?>
    </select>
    <br />
    <?php
    $all_change_styles = $validation_params['change_styles'];
    ?>
    <label for="input_change_style">Price Change Style: </label>
    <select id="input_change_style" name="change_style"  style="width: 130px;">
    <?php 
        foreach($all_change_styles as $style) {
            echo "<option " . selected($style, $shortcode_settings['change_style']) . ">{$style}</option>";
        }
    ?>
    </select>
    <br />
    <?php
}




function stock_widget_create_style_field($shortcode_settings) {
    echo "
        <p>
            If you have additional CSS rules you want to apply to the
            entire widget (such as alignment or borders) you can add them below.
        </p>
        <p>
            Example: <code>margin:auto; border:1px solid #000000;</code>
        </p>
        <input id='input_widget_advanced_style' name='widget_advanced_style' type='text' value='{$shortcode_settings['advanced_style']}' class='itxt' style='width:90%; text-align:left;' />";
}

function stock_widget_create_url_field($shortcode_settings) {
    echo "<p>Url that clicking on a stock will link to.  __STOCK__ will be replaced with the stock symbol.</p>
          <p>Example/Default: https://www.google.com/finance?q=__STOCK__</p>
          <input id='stock_page_url' name='stock_page_url' type='text' value='{$shortcode_settings['stock_page_url']}' class='itxt' style='width:90%; text-align:left;' />";
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
