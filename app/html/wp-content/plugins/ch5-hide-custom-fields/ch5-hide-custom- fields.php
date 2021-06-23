<?php


/*
Plugin Name: Chapter 5 - Hide Custom Fields
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

add_action(
    'add_meta_boxes',
    'ch5_hcf_remove_custom_fields_metabox'
);

function ch5_hcf_remove_custom_fields_metabox()
{
    remove_meta_box('postcustom', 'post', 'normal');
    remove_meta_box('postcustom', 'page', 'normal');
}
