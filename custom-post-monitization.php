\<?php
/* 
Plugin Name: CustomPostMonetization
Plugin URI: http://post-monitization.local/
Description: Create Custom Post Monetization plugin. Display the number of viewers for each post.
Version: 1.0
Author: RSM
Author URI: http://post-monitization.local/
Text Domain: custompostmonetization
*/

// Hook into the wp action when a single post is viewed
add_action('wp', 'custom_store_post_data_on_view');

function custom_store_post_data_on_view() {
    if (is_single()) { // Check if it's a single post view
        $post_id = get_the_ID();
        $post_url = get_permalink();
        $post_title = get_the_title();
        $author_id = get_post_field('post_author', $post_id); // Get the author ID
        $current_time = current_time('mysql');
        $user_ip_address = $_SERVER['REMOTE_ADDR'];

        // Retrieve existing post view data array from the option
        $post_view_data = get_option('custom_post_view_data', array());

        // Add new post view data to the array
        $post_view_data[$post_id][] = array(
            'post_id' => $post_id,
            'post_url' => $post_url,
            'post_title' => $post_title,
            'author_id' => $author_id,
            'viewed_time' => $current_time,
            'user_ip' => $user_ip_address
        );

        // Store the updated array in the wp_options table
        update_option('custom_post_view_data', $post_view_data);
    }
}

// Hook into the manage_posts_columns hook to add a new column
add_filter('manage_posts_columns', 'custom_add_monetization_column');

function custom_add_monetization_column($columns) {
    $columns['post_monetization'] = 'Post Monetization';
    return $columns;
}

// Hook into the manage_posts_custom_column hook to display data in the new column
add_action('manage_posts_custom_column', 'custom_display_monetization_column_data', 10, 2);


function custom_display_monetization_column_data($column_name, $post_id) {
    if ($column_name === 'post_monetization') {
        // Retrieve the post view data array
        $post_view_data = get_option('custom_post_view_data', array());

        if (isset($post_view_data[$post_id])) {
            $view_data = $post_view_data[$post_id];
            $total_viewers = count($view_data); // Total number of viewers

            echo "Total viewers: $total_viewers";

            $last_viewer = end($view_data); // Get the last viewer's data

            if (is_array($last_viewer) && isset($last_viewer['viewed_time'])) {
                $viewer_time = $last_viewer['viewed_time'];
                echo "<br>Last viewed: $viewer_time";
            } else {
                echo '<br>No viewers';
            }
        } else {
            echo 'No viewers';
        }
    }
    elseif ($column_name === 'post_earnings') {
        // Retrieve the post view data array and monetization data
        $post_view_data = get_option('custom_post_view_data', array());
        $monetization_data = get_option('custom_post_monetization_all_data', array());

        $earnings = 0;

        // Calculate earnings for this post
        if (isset($post_view_data[$post_id]) && !empty($monetization_data)) {
            $view_data = $post_view_data[$post_id];
            $total_viewers = count($view_data);

            foreach ($monetization_data as $data) {
                $post_view_rate = isset($data['post_view_rate']) ? $data['post_view_rate'] : 0;
                $earnings += $total_viewers * $post_view_rate;
            }
        }

        echo "Earnings: $earnings"; // Display calculated earnings
    }
}
// Register the admin menu
add_action('admin_menu', 'custom_post_monetization_menu');

function custom_post_monetization_menu() {
    $parent_slug = 'custom-post-monetization';

    add_menu_page(
        'Post Monetization',
        'Post Monetization',
        'manage_options',
        $parent_slug,
        'custom_post_monetization_page',
        'dashicons-chart-line', // Use appropriate dashicon class
        6 // Position in the menu
    );

    add_submenu_page(
        $parent_slug,
        'Sub Menu Page Title',
        'Monetization Details',
        'manage_options',
        'custom-sub-menu',
        'custom_sub_menu_page'
    );
}

// Function to display content on the custom admin page
function custom_post_monetization_page() {
    $all_data = get_option('custom_post_monetization_all_data', array());

// Extract the last submitted data for pre-population
$last_submitted_data = end($all_data);

// Define default values
$default_show_checkbox = isset($last_submitted_data['Enable']) ? $last_submitted_data['Enable'] : 'on';
$default_post_view_rate = isset($last_submitted_data['post_view_rate']) ? $last_submitted_data['post_view_rate'] : '';
$default_selected_roles = isset($last_submitted_data['selected_roles']) ? $last_submitted_data['selected_roles'] : array();
$default_selected_option = isset($last_submitted_data['selected_option']) ? $last_submitted_data['selected_option'] : 'USD';

    // Handle form submission
    if (isset($_POST['custom_post_monetization_submit'])) {
        $show_checkbox = isset($_POST['Enable']) ? 'on' : 'off';
        $post_view_rate = intval($_POST['post_view_rate']); // Convert to integer
        $selected_roles = array_map('sanitize_text_field', $_POST['selected_role']); // Sanitize user roles
        $selected_option = sanitize_text_field($_POST['selected_option']);
    
        // Add new data to the existing array
        $new_data = array(
            'Enable' => $show_checkbox,
            'post_view_rate' => $post_view_rate,
            'selected_roles' => $selected_roles,
            'selected_option' => $selected_option
        );
    
        // Add the new data to the array and update the option 
        $all_data[] = $new_data;
        update_option('custom_post_monetization_all_data', $all_data);
         // Calculate earnings for this post and store it
    if (isset($post_view_data[$post_id])) {
        $view_data = $post_view_data[$post_id];
        $total_viewers = count($view_data);
        $earnings = $total_viewers * $post_view_rate;

        // Store earnings data for this post
        $earnings_data = get_option('custom_post_earnings_data', array());
        $earnings_data[$post_id] = $earnings;
        update_option('custom_post_earnings_data', $earnings_data);
    }
    }
    ?>
    <div class="post-monitization-div">
    <h1>Settings</h1>
    <form method="post" action="">
        <br>
        <label class="post-monitization-label">
        Enable: <input type="checkbox" name="show_checkbox" <?php if ($default_show_checkbox === 'on') echo 'checked'; ?> />
        </label>
        <br>
        <label for="post_view_rate" class="post-monitization-label">Post View Rate: </label>
        <input type="number" name="post_view_rate" class="post-monitization-select" value="<?php echo $default_post_view_rate; ?>">
        <br>
        <label for="selected_role" class="post-monitization-label">Allowed User Roles: </label>
        <select name="selected_role[]" class="post-monitization-select" multiple>
    <?php
    $wp_roles = wp_roles();
    foreach ($wp_roles->roles as $role_slug => $role_info) {
        $selected = in_array($role_slug, $default_selected_roles) ? 'selected' : '';
        echo '<option value="' . $role_slug . '" ' . $selected . '>' . $role_info['name'] . '</option>';
    }
    ?>
</select>
<br>
        <label for="selected_option" class="post-monitization-label">Post Monetization Currency: </label>
        <select name="selected_option" class="post-monitization-select">
    <option value="USD" <?php if ($default_selected_option === 'USD') echo 'selected'; ?>>USD</option>
    <option value="INR" <?php if ($default_selected_option === 'INR') echo 'selected'; ?>>INR</option>
    <option value="GBP" <?php if ($default_selected_option === 'GBP') echo 'selected'; ?>>GBP</option>
</select>

        <br><br>
        <input type="submit" name="custom_post_monetization_submit" class="button-primary" value="Save">
    </form>
</div>
    <?php
}
// Function to display content on the custom sub-menu page
function custom_sub_menu_page() {
    $post_view_data = get_option('custom_post_view_data', array());
    $monetization_data = get_option('custom_post_monetization_all_data', array());

    echo '<div class="wrap">';
    echo '<h1>Monetization Details</h1>';

    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Post ID</th>';
    echo '<th>Post Title</th>';
    echo '<th>Views</th>';
    echo '<th>Last Post View Rate</th>';
    echo '<th>Earnings</th>'; // Add new column
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($post_view_data as $post_id => $view_data) {
        $post = get_post($post_id);
        if ($post) {
            $post_title = $post->post_title;
            $total_viewers = count($view_data);

            $last_monetization_data = end($monetization_data); // Get the last monetization data
            $post_view_rate = isset($last_monetization_data['post_view_rate']) ? $last_monetization_data['post_view_rate'] : 0;

            $earnings = $total_viewers * $post_view_rate; // Calculate earnings

            echo '<tr>';
            echo "<td>$post_id</td>";
            echo "<td>$post_title</td>";
            echo "<td>$total_viewers</td>";
            echo "<td>$post_view_rate</td>"; // Display last post_view_rate
            echo "<td>$earnings</td>"; // Display calculated earnings
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';

    echo '</div>';
}
?>
<style>
    .post-monitization-div {
        max-width: 400px;
        margin-top: 35px;
        padding: 20px;
        border: 1px solid #ccc;
        background-color: #f7f7f7;
    }
    .post-monitization-label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
    }
    .post-monitization-select {
        width: 100%;
        margin-bottom: 10px;
    }
</style>
