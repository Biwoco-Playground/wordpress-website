<?php

/*
Plugin Name: Chapter 3 - Individual Options plugin
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

register_activation_hook( __FILE__, 'ch3io_set_default_options' );

function ch3io_set_default_options() {
    if ( false === get_option( 'ch3io_ga_account_name' ) ) {
        add_option( 'ch3io_ga_account_name', 'UA-0000000-0' );
    }
}