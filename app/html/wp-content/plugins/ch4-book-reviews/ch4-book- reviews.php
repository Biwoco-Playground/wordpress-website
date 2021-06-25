<?php

/*
Plugin Name: Chapter 4 - Book Reviews
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

add_action('init', 'ch4_br_create_book_post_type');
add_action('admin_init', 'ch4_br_admin_init');
add_action('save_post', 'ch4_br_add_book_review_fields', 10, 2);
add_filter('template_include', 'ch4_br_template_include', 1);
add_shortcode('book-review-list', 'ch4_br_book_review_list');
add_action(
    'book_reviews_book_type_edit_form_fields',
    'ch4_br_book_type_new_fields',
    10,
    2
);
add_action(
    'book_reviews_book_type_add_form_fields',
    'ch4_br_book_type_new_fields',
    10,
    2
);

add_action(
    'edited_book_reviews_book_type',
    'ch4_br_save_book_type_new_fields',
    10,
    2
);
add_action(
    'created_book_reviews_book_type',
    'ch4_br_save_book_type_new_fields',
    10,
    2
);

add_filter(
    'manage_edit-book_reviews_columns',
    'ch4_br_add_columns'
);
add_action(
    'manage_posts_custom_column',
    'ch4_br_populate_columns'
);
add_filter(
    'manage_edit-book_reviews_sortable_columns',
    'ch4_br_author_column_sortable'
);
add_filter('request', 'ch4_br_column_ordering');

add_action(
    'restrict_manage_posts',
    'ch4_br_book_type_filter_list'
);

add_filter('parse_query', 'ch4_br_perform_book_type_filtering');

add_action(
    'quick_edit_custom_box',
    'ch4_br_display_custom_quickedit_link',
    10,
    2
);

add_action('admin_footer', 'ch4_br_quick_edit_js');

add_filter('post_row_actions', 'ch4_br_quick_edit_link', 10, 2);

add_action('save_post', 'ch4_br_save_quick_edit_data', 10, 2);

add_filter(
    'document_title_parts',
    'ch4_br_format_book_review_title'
);

function ch4_br_create_book_post_type()
{
    register_post_type(
        'book_reviews',
        array(
            'labels' => array(
                'name' => 'Book Reviews',
                'singular_name' => 'Book Review',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Book Review',
                'edit' => 'Edit',
                'edit_item' => 'Edit Book Review',
                'new_item' => 'New Book Review',
                'view' => 'View',
                'view_item' => 'View Book Review',
                'search_items' => 'Search Book Reviews',
                'not_found' => 'No Book Reviews found',
                'not_found_in_trash' =>
                'No Book Reviews found in Trash',
                'parent' => 'Parent Book Review'
            ),
            'public' => true,
            'menu_position' => 20,
            'supports' => array('title', 'editor', 'comments', 'thumbnail'),
            'taxonomies' => array(''),
            'menu_icon' =>
            plugins_url('book-reviews.png', __FILE__),
            'has_archive' => true,
            'exclude_from_search' => true
        )
    );

    register_taxonomy(
        'book_reviews_book_type',
        'book_reviews',
        array(
            'labels' => array(
                'name' => 'Book Type',
                'add_new_item' => 'Add New Book Type',
                'new_item_name' => 'New Book Type Name'
            ),
            'show_ui' => true,
            'show_tagcloud' => false,
            'meta_box_cb' => false,
            'show_in_quick_edit' => false,
            'hierarchical' => true
        )
    );
}


function ch4_br_admin_init()
{
    add_meta_box(
        'ch4_br_review_details_meta_box',
        'Book Review Details',
        'ch4_br_display_review_details_meta_box',
        'book_reviews',
        'normal',
        'high'
    );
}

function ch4_br_display_review_details_meta_box($book_review)
{
    // Retrieve current author and rating based on review ID
    $book_author =
        esc_html(get_post_meta(
            $book_review->ID,
            'book_author',
            true
        ));
    $book_rating =
        intval(get_post_meta(
            $book_review->ID,
            'book_rating',
            true
        ));
?>
    <table>
        <tr>
            <td style="width: 100%">Book Author</td>
            <td><input type="text" size="80" name="book_review_author_name" value="<?php echo $book_author; ?>" /></td>
        </tr>
        <tr>
            <td style="width: 150px">Book Rating</td>
            <td>
                <select style="width: 100px" name="book_review_rating">
                    <!-- Loop to generate items in dropdown list -->
                    <?php
                    for ($rating = 5; $rating >= 1; $rating--) { ?>
                        <option value="<?php echo $rating; ?>" <?php echo selected($rating, $book_rating); ?>>
                            <?php echo $rating; ?> stars
                        <?php } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Book Type</td>
            <td>
                <?php
                // Retrieve array of types assigned to post
                $assigned_types = wp_get_post_terms(
                    $book_review->ID,
                    'book_reviews_book_type'
                );
                // Retrieve array of all book types in system
                $book_types = get_terms(
                    'book_reviews_book_type',
                    array(
                        'orderby' => 'name',
                        'hide_empty' => 0
                    )
                );
                if ($book_types) {
                    echo '<select name="book_review_book_type"';
                    echo ' style="width: 400px">';
                    foreach ($book_types as $book_type) {
                        echo '<option value="' . $book_type->term_id;
                        echo '" ';
                        if (!empty($assigned_types)) {
                            selected(
                                $assigned_types[0]->term_id,
                                $book_type->term_id
                            );
                        }
                        echo '>' . esc_html($book_type->name);
                        echo '</option>';
                    }
                    echo '</select>';
                } ?>
            </td>
        </tr>
    </table>

<?php }

function ch4_br_add_book_review_fields(
    $book_review_id,
    $book_review
) {
    // Check post type for book reviews
    if ('book_reviews' == $book_review->post_type) {
        // Store data in post meta table if present in post data
        if (isset($_POST['book_review_author_name'])) {
            update_post_meta(
                $book_review_id,
                'book_author',
                sanitize_text_field(
                    $_POST['book_review_author_name']
                )
            );
        }
        if (
            isset($_POST['book_review_rating']) &&
            !empty($_POST['book_review_rating'])
        ) {
            update_post_meta(
                $book_review_id,
                'book_rating',
                intval($_POST['book_review_rating'])
            );
        }
    }

    if (
        isset($_POST['book_review_book_type'])
        && !empty($_POST['book_review_book_type'])
    ) {
        wp_set_post_terms(
            $book_review->ID,
            intval($_POST['book_review_book_type']),
            'book_reviews_book_type'
        );
    }
}

function ch4_br_template_include($template_path)
{
    if ('book_reviews' == get_post_type()) {
        if (is_single()) {
            // checks if the file exists in the theme first,
            // otherwise install content filter
            if ($theme_file = locate_template(array('single-book_reviews.php'))) {
                $template_path = $theme_file;
            } else {
                add_filter(
                    'the_content',
                    'ch4_br_display_single_book_review',
                    20
                );
            }
        }
    }
    return $template_path;
}

function ch4_br_display_single_book_review($content)
{
    if (!empty(get_the_ID())) {
        // Display featured image in right-aligned floating div
        $content = '<div style="float: right; margin: 10px">';
        $content .= get_the_post_thumbnail(
            get_the_ID(),
            'medium'
        );
        $content .= '</div>';
        $content .= '<div class="entry-content">';
        // Display Author Name
        $content .= '<strong>Author: </strong>';
        $content .= esc_html(get_post_meta(
            get_the_ID(),
            'book_author',
            true
        ));
        $content .= '<br />';
        // Display yellow stars based on rating -->
        $content .= '<strong>Rating: </strong>';
        $nb_stars = intval(get_post_meta(
            get_the_ID(),
            'book_rating',
            true
        ));
        for (
            $star_counter = 1;
            $star_counter <= 5;
            $star_counter++
        ) {
            if ($star_counter <= $nb_stars) {
                $content .= '<img src="' .
                    plugins_url(
                        'star-icon.png',
                        __FILE__
                    ) . '"
        />';
            } else {
                $content .= '<img src="' .
                    plugins_url(
                        'star-icon-grey.png',
                        __FILE__
                    )
                    . '" />';
            }
        }
        // Display book review contents
        $content .= '<br /><br />';
        $content .= get_the_content(get_the_ID());
        $content .= '</div>';
        return $content;
    }

    $book_types = wp_get_post_terms(
        get_the_ID(),
        'book_reviews_book_type'
    );
    $content .= '<br /><strong>Type: </strong>';
    if ($book_types) {
        $first_entry = true;
        for ($i = 0; $i < count($book_types); $i++) {
            if (!$first_entry) {
                $content .= ', ';
            }
            $content .= $book_types[$i]->name;
            $first_entry = false;
        }
    } else {
        $content .= 'None Assigned';
    }
}


function ch4_br_book_review_list()
{
    // Preparation of query string to retrieve 5 book reviews
    $query_params = array(
        'post_type' => 'book_reviews',
        'post_status' => 'publish',
        'posts_per_page' => 5
    );
    // Retrieve page query variable, if present
    $page_num = (get_query_var('paged') ?
        get_query_var('paged') : 1);
    // If page number is higher than 1, add to query array
    if ($page_num != 1) {
        $query_params['paged'] = $page_num;
    }

    // Preparation of query array to retrieve 5 book reviews
    $query_params = array(
        'post_type' => 'book_reviews',
        'post_status' => 'publish',
        'posts_per_page' => 5
    );
    // Execution of post query
    $book_review_query = new WP_Query;
    $book_review_query->query($query_params);
    // Check if any posts were returned by the query
    if ($book_review_query->have_posts()) {
        // Display posts in table layout
        $output = '<table>';
        $output .= '<tr><th style="width: 350px"><strong>';
        $output .= 'Title</strong></th>';
        $output .= '<th><strong>Author</strong></th></tr>';
        // Cycle through all items retrieved
        while ($book_review_query->have_posts()) {
            $book_review_query->the_post();
            $output .= '<tr><td><a href="' . get_permalink();
            $output .= '">';
            $output .= get_the_title(get_the_ID()) . '</a></td>';
            $output .= '<td>';
            $output .= esc_html(get_post_meta(
                get_the_ID(),
                'book_author',
                true
            ));
            $output .= '</td></tr>';
        }
        $output .= '</table>';
        // Display page navigation links
        if ($book_review_query->max_num_pages > 1) {
            $output .= '<nav id="nav-below">';
            $output .= '<div class="nav-previous">';
            $output .= get_next_posts_link(
                '<span class="meta-nav">&larr;</span>' .
                    ' Older reviews',
                $book_review_query->max_num_pages
            );
            $output .= '</div>';
            $output .= '<div class="nav-next">';
            $output .= get_previous_posts_link(
                'Newer reviews ' .
                    '<span class="meta-nav">&rarr;</span>',
                $book_review_query->max_num_pages
            );
            $output .= '</div>';
            $output .= '</nav>';
        }
        // Reset post data query
        wp_reset_postdata();
    }
    return $output;
}


function ch4_br_book_type_new_fields($tag)
{
    $mode = 'new';
    if (is_object($tag)) {
        $mode = 'edit';
        $book_cat_color = get_term_meta(
            $tag->term_id,
            'book_type_color',
            true
        );
    }
    $book_cat_color = empty($book_cat_color) ?
        '#' : $book_cat_color;
    if ('edit' == $mode) {
        echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">';
    } elseif ('new' == $mode) {
        echo '<div class="form-field">';
    } ?>
    <label for="tag-category-url">Color</label>
    <?php if ('edit' == $mode) {
        echo '</th><td>';
    } ?>
    <input type="text" id="book_type_color" name="book_type_color" value="<?php echo $book_cat_color; ?>" />
    <p class="description">Color associated with book type
        (e.g. #199C27 or #CCC)</p>
    <?php if ('edit' == $mode) {
        echo '</td></tr>';
    } elseif ('new' == $mode) {
        echo '</div>';
    }
}

function ch4_br_save_book_type_new_fields($term_id, $tt_id)
{
    if (!$term_id) {
        return;
    }
    if (
        isset($_POST['book_type_color'])
        && ('#' == $_POST['book_type_color']
            || preg_match(
                '/#([a-f0-9]{3}){1,2}\b/i',
                $_POST['book_type_color']
            ))
    ) {
        $returnvalue = update_term_meta(
            $term_id,
            'book_type_color',
            $_POST['book_type_color']
        );
    }
}


function ch4_br_add_columns($columns)
{
    $columns['book_reviews_author'] = 'Author';
    $columns['book_reviews_rating'] = 'Rating';
    $columns['book_reviews_type'] = 'Type';
    unset($columns['comments']);
    return $columns;
}

function ch4_br_populate_columns($column)
{
    if ('book_reviews_author' == $column) {
        $book_author = esc_html(get_post_meta(
            get_the_ID(),
            'book_author',
            true
        ));
        echo $book_author;
    } elseif ('book_reviews_rating' == $column) {
        $book_rating = get_post_meta(
            get_the_ID(),
            'book_rating',
            true
        );
        echo $book_rating . ' stars';
    } elseif ('book_reviews_type' == $column) {
        $book_types = wp_get_post_terms(
            get_the_ID(),
            'book_reviews_book_type'
        );
        if ($book_types) {
            $book_cat_color = get_term_meta(
                $book_types[0]->term_id,
                'book_type_color',
                true
            );
            if ('#' != $book_cat_color) {
                echo '<span style="background-color: ';
                echo $book_cat_color . '; ';
                echo 'color: #fff; padding: 6px;">';
                echo $book_types[0]->name . '</span>';
            } else {
                echo $book_types[0]->name;
            }
        } else {
            echo 'None Assigned';
        }
    }
}

function ch4_br_author_column_sortable($columns)
{
    $columns['book_reviews_author'] = 'book_reviews_author';
    $columns['book_reviews_rating'] = 'book_reviews_rating';
    return $columns;
}
function ch4_br_column_ordering($vars)
{
    if (!is_admin()) {
        return $vars;
    }
    if (
        isset($vars['orderby']) &&
        'book_reviews_author' == $vars['orderby']
    ) {
        $vars = array_merge($vars, array(
            'meta_key' => 'book_author',
            'orderby' => 'meta_value'
        ));
    } elseif (
        isset($vars['orderby']) &&
        'book_reviews_rating' == $vars['orderby']
    ) {
        $vars = array_merge($vars, array(
            'meta_key' => 'book_rating',
            'orderby' => 'meta_value_num'
        ));
    }
    return $vars;
}


function ch4_br_book_type_filter_list()
{
    $screen = get_current_screen();
    global $wp_query;
    if ('book_reviews' == $screen->post_type) {
        wp_dropdown_categories(array(
            'show_option_all' => 'Show All Book Types',
            'taxonomy'
            => 'book_reviews_book_type',
            'name'
            => 'book_reviews_book_type',
            'orderby'
            => 'name',
            'selected'
            => (isset($wp_query->query['book_reviews_book_type'])
                ? $wp_query->query['book_reviews_book_type'] : ''),
            'hierarchical'
            => false,
            'depth'
            => 3,
            'show_count'
            => false,
            'hide_empty'
            => true,
        ));
    }
}

function ch4_br_perform_book_type_filtering($query)
{
    $qv = &$query->query_vars;
    if (
        isset($qv['book_reviews_book_type']) &&
        !empty($qv['book_reviews_book_type']) &&
        is_numeric($qv['book_reviews_book_type'])
    ) {
        $term = get_term_by(
            'id',
            $qv['book_reviews_book_type'],
            'book_reviews_book_type'
        );
        $qv['book_reviews_book_type'] = $term->slug;
    }
}

function ch4_br_display_custom_quickedit_link(
    $column_name,
    $post_type
) {
    if ('book_reviews' == $post_type) {
        switch ($column_name) {
            case 'book_reviews_author': ?>
                <fieldset class="inline-edit-col-right">
                    <div class="inline-edit-col">
                        <label><span class="title">Author</span></label>
                        <input type="text" name='book_reviews_author_input' id='book_reviews_author_input' value="">
                    </div>
                <?php break;
            case 'book_reviews_rating': ?>
                    <div class="inline-edit-col">
                        <label>
                            <span class="title">Rating</span>
                        </label>
                        <select name='book_reviews_rating_input' id='book_reviews_rating_input'>
                            <?php // Generate all items of drop-down list
                            for ($rating = 5; $rating >= 1; $rating--) {
                            ?> <option value="<?php echo $rating; ?>">
                                    <?php echo $rating; ?> stars
                                <?php } ?>
                        </select>
                    </div>
                <?php break;
            case 'book_reviews_type': ?>
                    <div class="inline-edit-col">
                        <label><span class="title">Type</span></label>
                        <?php
                        $terms = get_terms(array(
                            'taxonomy' =>
                            'book_reviews_book_type',
                            'hide_empty' => false
                        ));
                        ?>
                        <select name='book_reviews_type_input' id='book_reviews_type_input'>
                            <?php foreach ($terms as $index => $term) {
                                echo '<option ';
                                echo 'class="book_reviews_type-option"';
                                echo 'value="' . $term->term_id . '"';
                                selected(0, $index);
                                echo '>' . $term->name . '</option>';
                            } ?>
                        </select>
                    </div>
        <?php break;
        }
    }
}

function ch4_br_quick_edit_js()
{
    global $current_screen;
    if (('edit-book_reviews' !== $current_screen->id) ||
        ('book_reviews' !== $current_screen->post_type)
    ) {
        return;
    } ?>
        <script type="text/javascript">
            function set_inline_book_reviews(reviewArray) {
                // revert Quick Edit menu so that it refreshes properly
                inlineEditPost.revert();
                var inputBookAuthor =
                    document.getElementById('book_reviews_author_input');
                inputBookAuthor.value = reviewArray[0];
                var inputRating =
                    document.getElementById('book_reviews_rating_input');
                for (i = 0; i < inputRating.options.length; i++) {
                    if (inputRating.options[i].value == reviewArray[1]) {
                        inputRating.options[i].setAttribute('selected',
                            'selected');
                    } else {
                        inputRating.options[i].removeAttribute(
                            'selected');
                    }
                }
                var inputBookType =
                    document.getElementById('book_reviews_type_input');
                for (i = 0; i < inputBookType.options.length; i++) {
                    if (inputBookType.options[i].value ==
                        reviewArray[2]) {
                        inputBookType.options[i].setAttribute('selected',
                            'selected');
                    } else {
                        inputBookType.options[i].removeAttribute(
                            'selected');
                    }
                }
            }
        </script>
    <?php }

function ch4_br_quick_edit_link($act, $post)
{
    global $current_screen;
    $post_id = '';
    if ((isset($current_screen) &&
            $current_screen->id != 'edit-book_reviews' &&
            $current_screen->post_type != 'book_reviews')
        || (isset($_POST['screen']) &&
            $_POST['screen'] != 'edit-book_reviews')
    ) {
        return $act;
    }
    if (!empty($post->ID)) {
        $post_id = $post->ID;
    } elseif (isset($_POST['post_ID'])) {
        $post_id = intval($_POST['post_ID']);
    }
    if (!empty($post_id)) {
        $book_author = esc_html(get_post_meta(
            $post_id,
            'book_author',
            true
        ));
        $book_rating = esc_html(get_post_meta(
            $post_id,
            'book_rating',
            true
        ));
        $book_reviews_types = wp_get_post_terms(
            $post_id,
            'book_reviews_book_type',
            array('fields' => 'all')
        );
        if (empty($book_reviews_types)) {
            $book_reviews_types[0] =
                (object) array('term_id' => 0);
        }
        $idx = 'inline hide-if-no-js';
        $act[$idx] = '<a href="#" class="editinline" ';
        $act[$idx] .= " onclick=\"var reviewArray = new Array('";
        $act[$idx] .= "{$book_author}', '{$book_rating}', ";
        $act[$idx] .= "'{$book_reviews_types[0]->term_id}');";
        $act[$idx] .= "set_inline_book_reviews( reviewArray )\">";
        $act[$idx] .= __('Quick&nbsp;Edit');
        $act[$idx] .= '</a>';
    }
    return $act;
}


function ch4_br_save_quick_edit_data(
    $ID = false,
    $post = false
) {
    // Do not save if auto-saving, not book reviews, no permissions
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        (isset($_POST['post_type'])
            && 'book_reviews' != $_POST['post_type']) ||
        !current_user_can('edit_page', $ID)
    ) {
        return $ID;
    }
    $post = get_post($ID);
    if (!empty($post) && 'revision' != $post->post_type) {
        if (isset($_POST['book_reviews_author_input'])) {
            update_post_meta(
                $ID,
                'book_author',
                sanitize_text_field(
                    $_POST['book_reviews_author_input']
                )
            );
        }
        if (isset($_POST['book_reviews_rating_input'])) {
            update_post_meta(
                $ID,
                'book_rating',
                intval($_POST['book_reviews_rating_input'])
            );
        }
        if (isset($_POST['book_reviews_type_input'])) {
            $term = term_exists(
                intval($_POST['book_reviews_type_input']),
                'book_reviews_book_type'
            );
            if (!empty($term)) {
                wp_set_object_terms(
                    $ID,
                    intval($_POST['book_reviews_type_input']),
                    'book_reviews_book_type'
                );
            }
        }
    }
}

function ch4_br_format_book_review_title($the_title)
{
    if ('book_reviews' == get_post_type() && is_single()) {
        $book_author = esc_html(get_post_meta(
            get_the_ID(),
            'book_author',
            true
        ));
        if (!empty($book_author)) {
            $the_title['title'] .= ' by ' . $book_author;
        }
    }
    return $the_title;
}