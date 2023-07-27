<?php
/*
Plugin Name: Custom Testimonials
Description: Allows users to add and manage customer testimonials on their WordPress websites.
Version: 1.0
Author: Sameer Zahid
*/

// Custom Post Type: Testimonials
function custom_testimonials_post_type() {
    $labels = array(
        'name'               => 'Testimonials',
        'singular_name'      => 'Testimonial',
        'menu_name'          => 'Testimonials',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Testimonial',
        'edit_item'          => 'Edit Testimonial',
        'new_item'           => 'New Testimonial',
        'view_item'          => 'View Testimonial',
        'search_items'       => 'Search Testimonials',
        'not_found'          => 'No testimonials found',
        'not_found_in_trash' => 'No testimonials found in Trash',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'menu_icon'           => 'dashicons-format-quote',
        'supports'            => array('title', 'editor'),
    );

    register_post_type('custom_testimonials', $args);
}
add_action('init', 'custom_testimonials_post_type');



// Custom Testimonial Submission Form Shortcode
function custom_testimonials_submission_form_shortcode() {
    ob_start();
    ?>
    <form id="custom-testimonials-form" method="post">
        <div class="form-group">
            <label for="customer_name">Name:</label>
            <input type="text" name="customer_name" id="customer_name" required>
        </div>
        <div class="form-group">
            <label for="customer_email">Email:</label>
            <input type="email" name="customer_email" id="customer_email" required>
        </div>
        <div class="form-group">
            <label for="company_name">Company Name:</label>
            <input type="text" name="company_name" id="company_name">
        </div>
        <div class="form-group">
            <label for="testimonial_message">Testimonial:</label>
            <textarea name="testimonial_message" id="testimonial_message" rows="5" required></textarea>
        </div>
        <input type="submit" name="submit_testimonial" value="Submit Testimonial">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_testimonials_submission_form', 'custom_testimonials_submission_form_shortcode');


// Admin Settings Page
function custom_testimonials_settings_page() {
    add_options_page(
        'Custom Testimonials Settings',
        'Custom Testimonials',
        'manage_options',
        'custom_testimonials_settings',
        'custom_testimonials_render_settings_page'
    );
}
add_action('admin_menu', 'custom_testimonials_settings_page');

// Render Admin Settings Page
function custom_testimonials_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('custom_testimonials_options');
            do_settings_sections('custom_testimonials_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register and Render Settings Fields
function custom_testimonials_register_settings() {
    add_settings_section(
        'custom_testimonials_general_section',
        'General Settings',
        'custom_testimonials_general_section_cb',
        'custom_testimonials_settings'
    );

    add_settings_field(
        'testimonial_character_limit',
        'Testimonial Character Limit',
        'testimonial_character_limit_cb',
        'custom_testimonials_settings',
        'custom_testimonials_general_section'
    );

    add_settings_field(
        'enable_email_notification',
        'Enable Email Notification',
        'enable_email_notification_cb',
        'custom_testimonials_settings',
        'custom_testimonials_general_section'
    );

    add_settings_field(
        'testimonials_per_page',
        'Number of Testimonials per Page',
        'testimonials_per_page_cb',
        'custom_testimonials_settings',
        'custom_testimonials_general_section'
    );

    register_setting('custom_testimonials_options', 'custom_testimonials_options');
}
add_action('admin_init', 'custom_testimonials_register_settings');

// Callbacks for Settings Fields
function custom_testimonials_general_section_cb() {
    echo '<p>Configure the general settings for the Custom Testimonials plugin.</p>';
}

function testimonial_character_limit_cb() {
    $options = get_option('custom_testimonials_options');
    $character_limit = isset($options['testimonial_character_limit']) ? $options['testimonial_character_limit'] : 200;
    echo '<input type="number" name="custom_testimonials_options[testimonial_character_limit]" value="' . esc_attr($character_limit) . '">';
}

function enable_email_notification_cb() {
    $options = get_option('custom_testimonials_options');
    $enable_notification = isset($options['enable_email_notification']) ? $options['enable_email_notification'] : false;
    echo '<input type="checkbox" name="custom_testimonials_options[enable_email_notification]" value="1" ' . checked(1, $enable_notification, false) . '>';
}

function testimonials_per_page_cb() {
    $options = get_option('custom_testimonials_options');
    $testimonials_per_page = isset($options['testimonials_per_page']) ? $options['testimonials_per_page'] : 5;
    ?>
    <select name="custom_testimonials_options[testimonials_per_page]">
        <?php
        $options = array(5, 10, 15, 20);
        foreach ($options as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($testimonials_per_page, $option, false) . '>' . esc_html($option) . '</option>';
        }
        ?>
    </select>
    <?php
}



// Form Data Validation and Submission
function custom_testimonials_handle_submission() {
    if (isset($_POST['submit_testimonial'])) {
        $name = sanitize_text_field($_POST['customer_name']);
        $email = sanitize_email($_POST['customer_email']);
        $company = sanitize_text_field($_POST['company_name']);
        $message = sanitize_textarea_field($_POST['testimonial_message']);

        // Additional validation and error handling can be added here.

        $post_data = array(
            'post_title' => $name . ' Testimonial',
            'post_content' => $message,
            'post_type' => 'custom_testimonials',
            'post_status' => 'draft', // Change this to 'pending' if you want admin approval.
            'meta_input' => array(
                'customer_name' => $name,
                'customer_email' => $email,
                'company_name' => $company,
            ),
        );

        $post_id = wp_insert_post($post_data);

        // Optionally, you can send an email notification here if enabled in the settings.
    }
}
add_action('init', 'custom_testimonials_handle_submission');


// Shortcode to Display Testimonials
function custom_testimonials_shortcode($atts) {
    $atts = shortcode_atts(array(
        'count' => 5, // Default number of testimonials to display
    ), $atts);

    $testimonials_args = array(
        'post_type' => 'custom_testimonials',
        'posts_per_page' => (int)$atts['count'],
        'orderby' => 'rand', // Display random testimonials
        'post_status' => 'publish',
    );

    $testimonials_query = new WP_Query($testimonials_args);

    ob_start();
    if ($testimonials_query->have_posts()) {
        echo '<div class="custom-testimonials">';
        while ($testimonials_query->have_posts()) {
            $testimonials_query->the_post();
            $name = get_post_meta(get_the_ID(), 'customer_name', true);
            $company = get_post_meta(get_the_ID(), 'company_name', true);
            $message = get_the_content();
            ?>
            <div class="testimonial">
                <p class="testimonial-content"><?php echo wp_trim_words($message, 20); ?></p>
                <p class="testimonial-author"><?php echo esc_html($name); ?> - <?php echo esc_html($company); ?></p>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p>No testimonials found.</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('custom_testimonials', 'custom_testimonials_shortcode');

// Add CSS for frontend display
function custom_testimonials_frontend_styles() {
    wp_enqueue_style('custom-testimonials-styles', plugin_dir_url(__FILE__) . 'css/custom-testimonials.css');
}
add_action('wp_enqueue_scripts', 'custom_testimonials_frontend_styles');

// Custom Testimonials Widget
class Custom_Testimonials_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'custom_testimonials_widget',
            'Custom Testimonials Widget',
            array('description' => 'Display the latest testimonials in the sidebar.')
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $count = isset($instance['count']) ? absint($instance['count']) : 5;

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $testimonials_args = array(
            'post_type' => 'custom_testimonials',
            'posts_per_page' => $count,
            'orderby' => 'date', // Display latest testimonials
            'post_status' => 'publish',
        );

        $testimonials_query = new WP_Query($testimonials_args);
        if ($testimonials_query->have_posts()) {
            echo '<ul class="custom-testimonials-widget">';
            while ($testimonials_query->have_posts()) {
                $testimonials_query->the_post();
                $name = get_post_meta(get_the_ID(), 'customer_name', true);
                $company = get_post_meta(get_the_ID(), 'company_name', true);
                $message = get_the_content();
                ?>
                <li class="testimonial-item">
                    <p class="testimonial-content"><?php echo wp_trim_words($message, 15); ?></p>
                    <p class="testimonial-author"><?php echo esc_html($name); ?> - <?php echo esc_html($company); ?></p>
                </li>
                <?php
            }
            echo '</ul>';
        } else {
            echo '<p>No testimonials found.</p>';
        }

        wp_reset_postdata();

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $count = isset($instance['count']) ? absint($instance['count']) : 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('count'); ?>">Number of Testimonials:</label>
            <input class="widefat" type="number" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" value="<?php echo $count; ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
        $instance['count'] = !empty($new_instance['count']) ? absint($new_instance['count']) : 5;
        return $instance;
    }
}

// Register the widget
function custom_testimonials_register_widget() {
    register_widget('Custom_Testimonials_Widget');
}
add_action('widgets_init', 'custom_testimonials_register_widget');
