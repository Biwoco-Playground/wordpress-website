<?php

/*
Plugin Name: Chapter 2 - Plugin Header
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

add_shortcode( 'tl', 'ch2ts_twitter_link_shortcode' );

function ch2ts_twitter_link_shortcode( $atts ) {
    $output = '<a href="https://twitter.com/ylefebvre">';
    $output .= 'Twitter Feed</a>';
    return $output;
    }

    ?>