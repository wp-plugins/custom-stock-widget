<?php

define('STOCK_PLUGIN_UTILS', true, false); //flag for whether this file was already included anywhere

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
        echo "<h4 style='display:inline-block;'>Customize Categories</h4><div id='category_toggle' class='section_toggle'>+</div>
              <div class='section-options-display'>
              
                <p><b>Optional:</b><br />
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



if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

//WARNING: WP_List_Table is not intended for use by plugin and theme developers as it is subject to change without warning in any future WordPress release
// Recommended to make a copy of this class as is and include it with the plugin files.

/*http://wpengineer.com/2426/wp_list_table-a-step-by-step-guide/
steps left to do for basic table functionality:
1) Create dummy function to add/reset test data in the database (use more creative names)
2) Setup pagination
3) Setup Search functionality
4) Setup screen options
4b) hide columns would require using get_column_info()  which may or may not work for me... :(

*/
class matt_debug_List_Table extends WP_List_Table {
    /*function __construct($type) {
        $this->type = $type;
        parent::__construct(); //will this break it?
    }*/
    //TODO: What other options might be useful to display on this page? Stock list? width, height, numstocks ?
    // could easily provide sorting for these as well. Add these in later I suppose.
    //Intention: to have a single database field containing all the saved names.
    //then each individual save would be retrieved from there.
    //in order to get sorting and such to work, we would need all of the data to be locally here inside $items.
    //it might be much cleaner to make our own wordpress database table, then when we retreive data we can use order by commands
    //Advantages, cleaner for sorting data.
    //Disadvantages, none actually, it will already be anoying to have to iterate through all of the saved entries to update them should we change the scheme later (remove keys) adding new ones is pretty easy though
    private $example_data = array(// TODO: retreive this data from the DB somehow in roughly this format
        array('ID' => 1, 'name' => 'widget-1'), //shortcode should be created from the name
        array('ID' => 2, 'name' => 'widget-2'),
        array('ID' => 3, 'name' => 'widget-3')
    );
    function get_columns() {
        $columns = array( //NOTE: don't need to declare an ID column ID is reserved and expected
            'cb'        => '<input type="checkbox" />',
            'name'      => 'Named ID',
            'shortcode' => 'Shortcode'
        );
        return $columns;
    }
    function prepare_items() { //this so far looks a lot simpler
      $columns = $this->get_columns();
      $hidden = array();
      $sortable = $this->get_sortable_columns();
      $this->_column_headers = array($columns, $hidden, $sortable);
      usort( $this->example_data, array( &$this, 'usort_reorder' ) );
      $this->items = $this->example_data;;
    }
    
    //NOTE: Need one function per column name (uses column_default if specific function not found)
    function column_default( $item, $column_name ) {
      switch( $column_name ) { 
        /*case 'booktitle':
        case 'author':
        case 'isbn':
          return $item[ $column_name ];*/
        default:
          return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
      }
    }
    function column_name($item) {
      $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&book=%s">Edit</a>',  $_REQUEST['page'],'edit',  $item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&book=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
        );

      return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions) );
    }
    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="shortcode[]" value="%s" />', $item['ID']
        );    
    }
    function column_shortcode($item) {
        return sprintf('<input type="text" onfocus="this.select();" readonly="readonly" value="[stockwidget id=&quot;%s&quot;]" class="shortcode-in-list-table wp-ui-text-highlight code">', $item['name']);
        
    }
    
    function get_sortable_columns() {
      $sortable_columns = array(
        'name'  => array('name',false)
      );
      return $sortable_columns;
    }
    function usort_reorder( $a, $b ) {
      // If no sort, default to title
      $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name';
      // If no order, default to asc
      $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
      // Determine sort order
      $result = strcmp( $a[$orderby], $b[$orderby] );
      // Send final sort direction to usort
      return ( $order === 'asc' ) ? $result : -$result;
    }
    function get_bulk_actions() {
        $actions = array(
                'delete' => 'Delete'
        );

        return $actions;
    }
}

class stock_shortcode_List_Table extends WP_List_Table {
    private $type;  //TODO: does this make this usable for both widget and ticker?

    public static function define_columns() {
            $columns = array(
                    'cb'        => '<input type="checkbox" />',
                    'id'        => 'ID',
                    'shortcode' => 'Shortcode');//,
                    //'author' => __( 'Author', 'contact-form-7' ),
                    //'date' => __( 'Date', 'contact-form-7' ) );

            return $columns;
    }

    function __construct($type, $screen) { //type is either widget or ticker
            $this->type = $type;
            //$this->screen = $screen;
            parent::__construct( array( //TODO: I guess this fires the constructor of the WP_List_Table ?
                    'singular' => 'post',
                    'plural'   => 'posts',
                    'ajax'     => false,
                    'screen'   => $screen ) );  // where is the screen setup? The parent expects to have the screen chosen
                                                //NOTE: This is different from ctf7
    }

    function prepare_items() { //NOTE: required to be overriden by subclass
            //$current_screen = get_current_screen(); //unused?
            //print_r($current_screen);
            //print_r($this->screen);
            /*
            echo "<pre>DEBUG:\n";
            $columns = get_column_headers_debug( $this->screen ); //empty
            print_r($columns);
            $hidden = get_hidden_columns( $this->screen );  //empty -- but i think this is correct.
            print_r($hidden);
            $sortable_columns = $this->get_sortable_columns(); //id => array (id, 1) //if I had to guess, its say the id column is sortable, and default is ascending
            print_r($sortable_columns);
            echo "</pre>";
            */
            $per_page = $this->get_items_per_page( 'stock_shortcodes_per_page' ); //from the hidden top menu where you can check/uncheck columns and configure how many rows to show
                                                                                  //NOTE: missing or empty?

            //reference http://codex.wordpress.org/Class_Reference/WP_List_Table#Examples
            //echo "<pre>DEBUG:\n";
            //print_r($this->_column_headers); //here results in an error, undefined property
            //echo "</pre>";
            $this->_column_headers = $this->get_column_info();
            /*$columns       = get_column_headers( $this->screen );       // THIS FAILS!
            $columns_debug = get_column_headers_debug( $this->screen ); // THIS succeeds.
            echo "<pre>DEBUG:\n";
            echo "columns: ";
            print_r($columns);
            echo "columns_debug: ";
            print_r($columns_debug);
            //print_r($this->_column_headers);
            echo "</pre>";*/

            //all of these args are unused by my code, so either I need to use them in some way other than a WP_query, or I should delete them NOTE: need some of them
            $args = array(
                    'posts_per_page' => $per_page,
                    'orderby'        => 'id',
                    'order'          => 'ASC',
                    'offset'         => ( $this->get_pagenum() - 1 ) * $per_page );

            if ( ! empty( $_REQUEST['s'] ) )
                    $args['s'] = $_REQUEST['s'];

            if ( ! empty( $_REQUEST['orderby'] ) ) {
                    if ( 'id' == $_REQUEST['orderby'] )
                            $args['orderby'] = 'id';
                    /*elseif ( 'author' == $_REQUEST['orderby'] )
                            $args['orderby'] = 'author';
                    elseif ( 'date' == $_REQUEST['orderby'] )
                            $args['orderby'] = 'date';*/
            }

            if ( ! empty( $_REQUEST['order'] ) ) {
                    if ( 'asc' == strtolower( $_REQUEST['order'] ) )
                            $args['order'] = 'ASC';
                    elseif ( 'desc' == strtolower( $_REQUEST['order'] ) )
                            $args['order'] = 'DESC';
            }         
            
            //if $this->items === false then we get the "no items found" message
            $this->items = get_option("stock_{$this->type}_sc_ids");
            //$this->items = array('test1','test2','boris');  //so, this test data, does not work
            //TODO: debugging purposes

            //TODO: does this->items have to be of any particular type? If it has to be of type "post" then we might be screwed
            $total_items = count($this->items);

            $total_pages = ceil( $total_items / $per_page );

            $this->set_pagination_args( array(
                    'total_items' => $total_items,
                    'total_pages' => $total_pages,
                    'per_page'    => $per_page ) );
                    
            //echo "<span>This is a test if this is working at all. <br/>{$total_items} <br/>{$total_pages} <br/>{$per_page}</span><br/>" . print_r($this->items, true); //TODO: debug
    }
    
    function get_columns() { //NOTE: required to be overriden by subclass
            return get_column_headers( get_current_screen() );
    }

    function get_sortable_columns() {
            $columns = array(
                    /*'title' => array( 'title', true ),
                    'author' => array( 'author', false ),
                    'date' => array( 'date', false ) );*/
                    'id' => array('id', true));

            return $columns;
    }

    function get_bulk_actions() {
            $actions = array(
                    'delete' => 'Delete'
            );

            return $actions;
    }

    function column_default( $item, $column_name ) {
            return '';
    }

    function column_cb( $item ) {
            return sprintf(
                        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                        $this->_args['singular'],
                        $item
                    );
    }
    
    //function column_title( $item ) {
    function column_id( $item ) {
            $url       = admin_url( "admin.php?page=stock_{$this->type}_admin&id=" . $item  );
            $edit_link = add_query_arg( array( 'action' => 'edit' ), $url );
            $copy_link = wp_nonce_url( add_query_arg( array( 'action' => 'copy' ), $url ), "stock-{$this->type}-copy_$item"); //TODO: use check_admin_referer(); to verify the nonce on the other end

            $actions = array(
                    'edit' => '<a href="' . $edit_link . '">Edit</a>',
                    'copy' => '<a href="' . $copy_link . '">Copy</a>'
            );

            $a = sprintf( '<a class="row-title" href="%1$s" title="%2$s">%3$s</a>',
                    $edit_link,
                    esc_attr( sprintf('Edit &#8220;%s&#8221;', $item ) ), //TODO: does this work?
                    esc_html( $item ) 
                );

            return '<strong>' . $a . '</strong> ' . $this->row_actions( $actions ); //this is the clickable name of the shortcode that takes us to the edit page (same as edit button)
    }

    /*function column_author( $item ) {
            $post = get_post( $item->id() );

            if ( ! $post )
                    return;

            $author = get_userdata( $post->post_author );

            return esc_html( $author->display_name );
    }*/
    
    function column_shortcode( $item ) { //these are functions executed by parent class single_row_columns()
            $shortcode = "[stock_{$this->type}" . ' id="' . $item . '"]';

            return '<input type="text" onfocus="this.select();" readonly="readonly" value="' 
                    . esc_attr( $shortcode ) . '" class="shortcode-in-list-table wp-ui-text-highlight code" />';
        }

        /*function column_date( $item ) {
            $post = get_post( $item->id() );

            if ( ! $post )
                    return;

            $t_time = mysql2date( __( 'Y/m/d g:i:s A', 'contact-form-7' ), $post->post_date, true );
            $m_time = $post->post_date;
            $time = mysql2date( 'G', $post->post_date ) - get_option( 'gmt_offset' ) * 3600;

            $time_diff = time() - $time;

            if ( $time_diff > 0 && $time_diff < 24*60*60 )
                    $h_time = sprintf( __( '%s ago', 'contact-form-7' ), human_time_diff( $time ) );
            else
                    $h_time = mysql2date( __( 'Y/m/d', 'contact-form-7' ), $m_time );

            return '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
    }*/
}


?>
