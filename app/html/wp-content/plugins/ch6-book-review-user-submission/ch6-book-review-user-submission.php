<?php

/*
Plugin Name: Chapter 6 - Book Review User Submission
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

add_shortcode('submit-book-review', 'ch6_brus_book_review_form');
add_action(
    'template_redirect',
    'ch6_brus_match_new_book_reviews'
);
add_action('wp_insert_post', 'ch6_brus_send_email', 10, 2);
add_action('wp_enqueue_scripts', 'ch6_brus_recaptcha_script');

function ch6_brus_book_review_form()
{
    // make sure user is logged in
    if (!is_user_logged_in()) {
        echo '<p>You need to be a website member to be able to ';
        echo 'submit book reviews. Sign up to gain access!</p>';
        return;
    }
?>
    <form method="post" id="add_book_review" action="">
        <!-- Nonce fields to verify visitor provenance -->
        <?php wp_nonce_field('add_review_form', 'br_user_form'); ?>
        <?php if (
            isset($_GET['add_review_message'])
            && $_GET['add_review_message'] == 1
        ) { ?>
            <div style="margin: 8px;border: 1px solid #ddd;
background-color: #ff0;">
                Thank for your submission!
            </div>
        <?php } ?>
        <!-- Post variable to indicate user-submitted items -->
        <input type="hidden" name="ch6_brus_user_book_review" value="1" />
        <table>
            <tr>
                <td>Book Title</td>
                <td><input type="text" name="book_title" /></td>
            </tr>
            <tr>
                <td>Book Author</td>
                <td><input type="text" name="book_author" /></td>
            </tr>
            <tr>
                <td>Review</td>
                <td><textarea name="book_review_text"></textarea></td>
            </tr>
            <tr>
                <td>Rating</td>
                <td><select name="book_review_rating">
                        <?php
                        // Generate all rating items in drop-down list
                        for ($rating = 5; $rating >= 1; $rating--) { ?>
                            <option value="<?php echo $rating; ?>">
                                <?php echo $rating; ?> stars
                            <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Book Type</td>
                <td>
                    <?php
                    // Retrieve array of all book types in system
                    $book_types = get_terms(
                        'book_reviews_book_type',
                        array(
                            'orderby' => 'name',
                            'hide_empty' => 0
                        )
                    );
                    // Check if book types were found
                    if (
                        !is_wp_error($book_types) &&
                        !empty($book_types)
                    ) {
                        echo '<select name="book_review_book_type">';
                        // Display all book types
                        foreach ($book_types as $book_type) {
                            echo '<option value="' . $book_type->term_id;
                            echo '">' . $book_type->name . '</option>';
                        }
                        echo '</select>';
                    } ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="g-recaptcha" data-sitekey="6Lflx08bAAAAAKB-eWVPKmUeX7IV-R108zAXQUZE"></div>
                </td>
            </tr>
        </table>
        <input type="submit" name="submit" value="Submit Review" />
    </form>
<?php }


function ch6_brus_match_new_book_reviews($template)
{
    if (!empty($_POST['ch6_brus_user_book_review'])) {
        ch6_brus_process_user_book_reviews();
    } else {
        return $template;
    }
}


function ch6_brus_process_user_book_reviews()
{
    require_once plugin_dir_path(__FILE__) .
        '/recaptcha/autoload.php';
    $recaptcha = new \ReCaptcha\ReCaptcha('6Lflx08bAAAAAEbrVaxf4sMG0zFD-IiWDQKAFWLL');
    $resp = $recaptcha->verify(
        $_POST['g-recaptcha-response'],
        $_SERVER['REMOTE_ADDR']
    );
    if (!$resp->isSuccess()) {
        // Display message if any required fields are missing
        $abort_message = 'Missing or incorrect captcha.<br />';
        $abort_message .= 'Please go back and try again.';
        wp_die($abort_message);
        exit;
    } else {


        // Check that all required fields are present and non-empty
        if (
            wp_verify_nonce(
                $_POST['br_user_form'],
                'add_review_form'
            ) &&
            !empty($_POST['book_title']) &&
            !empty($_POST['book_author']) &&
            !empty($_POST['book_review_text']) && !empty($_POST['book_review_book_type']) &&
            !empty($_POST['book_review_rating'])
        ) {
            // Create array with received data
            $new_book_review_data = array(
                'post_status' => 'publish',
                'post_title' => $_POST['book_title'],
                'post_type' => 'book_reviews',
                'post_content' => $_POST['book_review_text']
            );
            // Insert new post in website database
            // Store new post ID from return value in variable
            $new_book_review_id =
                wp_insert_post($new_book_review_data);
            // Store book author and rating
            add_post_meta(
                $new_book_review_id,
                'book_author',
                wp_kses($_POST['book_author'], array())
            );
            add_post_meta(
                $new_book_review_id,
                'book_rating',
                (int) $_POST['book_review_rating']
            );
            // Set book type on post
            if (term_exists(
                $_POST['book_review_book_type'],
                'book_reviews_book_type'
            )) {
                wp_set_post_terms(
                    $new_book_review_id,
                    $_POST['book_review_book_type'],
                    'book_reviews_book_type'
                );
            }
            // Redirect browser to book review submission page
            $redirect_address =
                (empty($_POST['_wp_http_referer']) ? site_url() :
                    $_POST['_wp_http_referer']);
            wp_redirect(add_query_arg(
                'add_review_message',
                '1',
                $redirect_address
            ));
            exit;
        } else {
            // Display message if any required fields are missing
            $abort_message = 'Some fields were left empty. Please ';
            $abort_message .= 'go back and complete the form.';
            wp_die($abort_message);
            exit;
        }
    }
}

function ch6_brus_send_email($post_id, $post)
{
    // Only send emails for user-submitted book reviews
    if (
        isset($_POST['ch6_brus_user_book_review']) &&
        'book_reviews' == $post->post_type
    ) {
        $admin_mail = get_option('admin_email');
        $headers = 'Content-type: text/html';
        $message = 'A user submitted a new book review to your ';
        $message .= 'WordPress website database.<br /><br />';
        $message .= 'Book Title: ' . $post->post_title;
        $message .= '<br /><a href="';
        $message .= add_query_arg(
            array(
                'post_status' => 'draft',
                'post_type' => 'book_reviews'
            ),
            admin_url('edit.php')
        );
        $message .= '">Moderate new book reviews</a>';
        $email_title = htmlspecialchars_decode(
            get_bloginfo(),
            ENT_QUOTES
        ) . " - New Book Review Added: " .
            htmlspecialchars($_POST['book_title']);
        // Send email
        wp_mail($admin_mail, $email_title, $message, $headers);
    }
}


function ch6_brus_recaptcha_script()
{
    wp_enqueue_script(
        'google_recaptcha',
        'https://www.google.com/recaptcha/api.js',
        array(),
        false,
        true
    );
}
