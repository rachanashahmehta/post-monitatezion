<?php
/* 
Plugin Name: CustomPostMonetization
Plugin URI: http://post-monitization.local/
Description: Create Custom Post Monetization plugin. Display the number of viewers for each post.
Version: 1.0
Author: RSM
Author URI: http://post-monitization.local/
Text Domain: custompostmonetization
*/
if( ! defined( 'MNOTIZATION_DETAILS_PAGE_URL' ) ){
    define( 'MNOTIZATION_DETAILS_PAGE_URL', 'custom-post-monetization-details' );
}

// Hook into the activation of the plugin
register_activation_hook(__FILE__, 'custom_plugin_activation');

function custom_plugin_activation() {
    custom_store_post_data_on_view();
    require_once 'submenu/createfolder.php';
    
}
// Hook into the wp action when a single post is viewed
add_action('wp', 'custom_store_post_data_on_view');

function custom_store_post_data_on_view() {
   // require_once 'submenu/singlepostview.php';

   if (is_single() && get_post_status() === 'publish') { // Check if it's a single post view
    $post_id = get_the_ID();
    $total_viewers_count = get_post_meta($post_id, 'total_viewers_count', true);
    $post_url = get_permalink();
    $post_title = get_the_title();
    $author_id = get_post_field('post_author', $post_id); // Get the author ID
    $current_time = current_time('mysql');
    $user_ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check if the post has been viewed before
    if (empty($total_viewers_count)) {
        // If not, initialize the viewer count to 0
        update_post_meta($post_id, 'total_viewers_count', 0);
     }

     // Increment the viewer count when the post is viewed
     update_post_meta($post_id, 'total_viewers_count', $total_viewers_count + 1);

    // Retrieve existing post view data array from the option
    $post_view_data = get_option('custom_post_view_data', array());
    $monetization_data = get_option('custom_post_monetization_all_data', array());

    if( "" === $post_view_data ){
        $post_view_data = array();
    }

    // Add new post view data to the array
    $post_view_data[$post_id][] = array(
        'post_id' => $post_id,
        'post_url' => $post_url,
        'post_title' => $post_title,
        'author_id' => $author_id,
        'viewed_time' => $current_time,
        'user_ip' => $user_ip_address
    );

    if( ! isset( $post_view_data[$post_id]['earnings'] ) ) {
        $post_view_data[$post_id]['earnings'] = 0;
    }

    $post_view_data[$post_id]['earnings'] += $monetization_data['post_view_rate'];
    // Store the updated array in the wp_options table
    update_option('custom_post_view_data', $post_view_data);

    // Get the log folder path
    $log_folder_path = wp_upload_dir()['basedir'] . '/post-monitization-logs';

    // Create the log folder if it doesn't exist
    if (!file_exists($log_folder_path)) {
        wp_mkdir_p($log_folder_path);
    }

    // Create the log file path
    $log_file_path = $log_folder_path . '/' . $post_id . '.log';

    // Get the current user's IP address
    $user_ip_address = $_SERVER['REMOTE_ADDR'];

    // Log the post view in the log file
    $log_content = "[$current_time]". " Post Viewed IP:$user_ip_address\n\n";

    // Append the log content to the log file
    file_put_contents($log_file_path, $log_content, FILE_APPEND);
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
    $total_viewers_count = get_post_meta($post_id, 'total_viewers_count', true);

    if ($total_viewers_count !== '') {
        echo "Total viewers: $total_viewers_count";
    } else {
        echo "Total viewers: 0<br>No viewers";
    }

    if (isset($post_view_data[$post_id])) {
        $view_data = $post_view_data[$post_id];
        //$total_viewers = count($view_data); // Total number of viewers
        //echo "Total viewers: $total_viewers";

        $last_viewer = end($view_data); // Get the last viewer's data

        if (is_array($last_viewer) && isset($last_viewer['viewed_time'])) {
            $viewer_time = $last_viewer['viewed_time'];
            echo "<br>Last viewed: $viewer_time";
        } else {
            echo '<br>No viewers';
        }
    } else {
        echo '<br>No viewers';
    }
}
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
        'custom-post-monetization-details',
        'monetization_details_page'
    );
}

// Function to display content on the custom admin page
function custom_post_monetization_page() {
  // require_once 'submenu/custom_post_monetization_main_page.php';
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
    update_option('custom_post_monetization_all_data', $new_data);
}

$monetization_data = get_option('custom_post_monetization_all_data', array());

// Define default values
$default_show_checkbox = isset($monetization_data['Enable']) ? $monetization_data['Enable'] : 'on';
$default_post_view_rate = isset($monetization_data['post_view_rate']) ? $monetization_data['post_view_rate'] : '';
$default_selected_roles = isset($monetization_data['selected_roles']) ? $monetization_data['selected_roles'] : array();
$default_selected_option = isset($monetization_data['selected_option']) ? $monetization_data['selected_option'] : 'USD';

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
function monetization_details_page() {
    //require_once 'submenu/monetization_details_page.php';   
     $post_view_data = get_option('custom_post_view_data', array());
 $monetization_data = get_option('custom_post_monetization_all_data', array());
 $post_id = get_the_ID();  // Define $post_id initially as null

 if( ! isset($_GET['tab']) || ! is_numeric($_GET['tab']) ){
    // $log_id = $_GET['tab'];
     $log_folder_path = wp_upload_dir()['basedir'] . '/post-monitization-logs';
     $log_file_path = $log_folder_path . '/' . $post_id . '.log';

     echo '<div class="wrap" id="monetization-table">';
     echo '<h1>Monetization Details</h1>';

     echo '<table class="widefat fixed" cellspacing="0">';
     echo '<thead>';
     echo '<tr>';
     echo '<th>Post ID</th>';
     echo '<th>Post Title</th>';
     echo '<th>Views</th>';
     echo '<th>Post View Rate</th>';
     echo '<th>Earnings</th>'; 
     echo '<th>Log Content</th>'; // New column for log content
     echo '</tr>';
     echo '</thead>';
     echo '<tbody>';

     foreach ($post_view_data as $post_id => $view_data) {
         $post = get_post($post_id);
         if ($post) {
             $post_title = $post->post_title;
             $total_viewers_count = get_post_meta($post_id, 'total_viewers_count', true);
             
             // Fetch post view rate from the custom_post_monetization_all_data option
             $post_view_rate = isset($monetization_data['post_view_rate']) ? $monetization_data['post_view_rate'] : 0;

             // Calculate earnings
             $earnings = $total_viewers_count * $post_view_rate;

             // Fetch stored earnings for this post
             if (isset($post_view_data[$post_id]['earnings'])) {
                 $stored_earnings = $post_view_data[$post_id]['earnings'];
             } else {
                 $stored_earnings = 0;
             }

             // Get the log file path
             $log_folder_path = wp_upload_dir()['basedir'] . '/post-monitization-logs';
             $log_file_path = $log_folder_path . '/' . $post_id . '.log';
     
             //Check if the log file exists before reading it
             if(file_exists($log_file_path)){

                 // Read log content from the file
                 $log_content = file_get_contents($log_file_path);

             }else{

                 //Display a message indicating no log file is available
                 $log_content = 'No Log file Available';
             }

             echo '<tr>';
             echo "<td>$post_id</td>";
             echo "<td>$post_title</td>";
             echo "<td>$total_viewers_count</td>";
             echo "<td>$post_view_rate</td>"; 
             echo "<td>$stored_earnings</td>"; // Display calculated earnings
             echo "<td><a href='?page=" . MNOTIZATION_DETAILS_PAGE_URL . "&tab=" . $post_id . "' class='show-log' data-log-content='" . esc_attr($log_content) . "'>View Log</a></td>";
             echo '</tr>';
         }
     }

         echo '</tbody>';
         echo '</table>';
         echo '</div>';
         echo '</div>';
     echo '</div>';
 }
 else{
     $post_id = isset($_GET['tab']) ? intval($_GET['tab']) : 0;
     
     $log_folder_path = wp_upload_dir()['basedir'] . '/post-monitization-logs';
     $log_file_path = $log_folder_path . '/' . $post_id . '.log';

     //Check if the log file exists before reading it
     if(file_exists($log_file_path)){

        // Read log content from the file
        $log_content = file_get_contents($log_file_path);

        // Split the log content by line breaks and display it in a table
         $log_lines = explode("\n", $log_content);

          // Reverse the order of log lines to display the latest entry first
            $log_lines = array_reverse($log_lines);

         echo '<div class="wrap" id="monetization-table">';
         echo '<h1>Log Details</h1>';

          // Add a container with a fixed height and scrolling
        echo '<div style="max-height: 400px; overflow-y: scroll;">';
         echo '<table class="widefat fixed" cellspacing="0" id="log-details-table">';
       
         foreach ($log_lines as $line) {
             echo '<tr><td>' . $line . '</td></tr>';
         }
         echo '</table>';
         echo '</div>'; // Close the scrolling container
         echo '<a href="?page=' . MNOTIZATION_DETAILS_PAGE_URL . '" class="back-to-monetization button-primary" style="margin-top: 20px;">Back</a>';

     }else {
         echo 'No log data available for this post.';
     }  
 }  
}

// Hook into the deactivation of the plugin
register_deactivation_hook(__FILE__, 'custom_plugin_deactivation');

function custom_plugin_deactivation() {

    //  delete options, or files.
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