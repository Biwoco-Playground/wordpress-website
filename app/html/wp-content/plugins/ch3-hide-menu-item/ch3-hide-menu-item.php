<?php

/*
Plugin Name: Chapter 3 - Hide Menu Items
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

define('ch3hmi', 1);
if (is_admin()) {
    require plugin_dir_path(__FILE__) .
        'ch3-hide-menu-item-admin-functions.php';
}
