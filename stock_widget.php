<?php
/*
    Plugin Name: Custom Stock Widget
    Plugin URI: http://relevad.com/wp-plugins/
    Description: Create customizable stock data table widgets that can be placed anywhere on a site using shortcodes.
    Author: Relevad
    Version: 2.1.1b
    Author URI: http://relevad.com/

*/

/*  Copyright 2015 Relevad Corporation (email: stock-widget@relevad.com) 
 
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

$php_bad_version = version_compare( PHP_VERSION, '5.3.0', '<' );
if ($php_bad_version) {
    add_action( 'admin_init',    'rp_deactivate' );
    add_action( 'admin_notices', 'rp_deactivation_notice' );
    if (!function_exists('rp_deactivate')) {
        function rp_deactivate() {
                deactivate_plugins( plugin_basename( __FILE__ ) );
        }
    }
    if (!function_exists()) {
        function rp_deactivation_notice() {
                $plugin_dir = basename(plugin_dir_path(__FILE__)); //example FILE=>/var/www/docs/foobar.com/wp-content/plugins/myplugin/myfile.php => myplugin
                echo '<div class="error"><p>Sorry, the <strong>'.ucwords(str_replace('-',' ',$plugin_dir)).'</strong> plugin requires PHP version 5.3.0 or greater to use.';
                echo '<br/>Your PHP version is '.PHP_VERSION.'. <strong>Activation blocked!</strong></p></div>';
                if ( isset( $_GET['activate'] ) )
                     unset( $_GET['activate'] ); //prevent plugin activated admin notice
        }
    }
} else {
    $main_plugin_file = __FILE__;
    require(plugin_dir_path(__FILE__) . str_replace('.php', '_admin.php', basename(__FILE__)) );
}
