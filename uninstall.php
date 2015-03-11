<?php 
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	echo "ACCESS FORBIDDEN";
    exit();
}
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "stock_widgets" );
delete_option('stock_widget_per_category_stock_lists');
delete_option('stock_widget_version');
delete_option('stock_widget_version_text');
?>
