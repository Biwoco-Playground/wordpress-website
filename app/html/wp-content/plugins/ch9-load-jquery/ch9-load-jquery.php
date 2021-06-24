<?php

/*
Plugin Name: Chapter 9 - Load jQuery
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

add_action('wp_enqueue_scripts', 'ch9lj_front_facing_pages');

function ch9lj_front_facing_pages()
{
    wp_enqueue_script('jquery');
}
