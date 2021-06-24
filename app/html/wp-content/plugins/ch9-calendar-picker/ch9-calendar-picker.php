<?php

/*
Plugin Name: Chapter 9 - Calendar Picker
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

add_action('admin_enqueue_scripts', 'ch9cp_admin_scripts');
add_action('add_meta_boxes', 'ch9cp_register_meta_box');



function ch9cp_admin_scripts()
{
    $screen = get_current_screen();
    if (
        'post' == $screen->base &&
        'post' == $screen->post_type
    ) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style(
            'datepickercss',
            plugins_url(
                'css/jquery-ui.min.css',
                __FILE__
            ),
            array(),
            '1.12.1'
        );
        
    }
}

function ch9cp_register_meta_box()
{
    add_meta_box(
        'ch9cp_datepicker_box',
        'Assign Date',
        'ch9cp_date_meta_box',
        'post',
        'normal'
    );
}

function ch9cp_date_meta_box($post)
{ ?>
    <input type="text" id="ch9cp_date" name="ch9cp_date" />
    <!-- JavaScript function to display calendar button -->
    <!-- and associate date selection with field -->
    <script type='text/javascript'>
        jQuery(document).ready(function() {
            jQuery('#ch9cp_date').datepicker({
                minDate: '+0',
                dateFormat: 'yy-mm-dd',
                showOn: 'both',
                constrainInput: true
            });
        });
    </script>
<?php }
