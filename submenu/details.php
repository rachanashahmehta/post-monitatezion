<?php
 $post_view_data = get_option('custom_post_view_data', array());
 $monetization_data = get_option('custom_post_monetization_all_data', array());
 $post_id = null; // Define $post_id initially as null

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
             $total_viewers = count($view_data);
             
             // Fetch post view rate from the custom_post_monetization_all_data option
             $post_view_rate = isset($monetization_data['post_view_rate']) ? $monetization_data['post_view_rate'] : 0;

             // Calculate earnings
             $earnings = $total_viewers * $post_view_rate;

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
             echo "<td>$total_viewers</td>";
             echo "<td>$post_view_rate</td>"; 
             echo "<td>$stored_earnings</td>"; // Display calculated earnings
             echo "<td><a href='?page=custom-sub-menu&tab=" . $post_id . "' class='show-log' data-log-content='" . esc_attr($log_content) . "'>View Log</a></td>";
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

         echo '<div class="wrap" id="monetization-table">';
         echo '<h1>Log Details</h1>';
         echo '<table class="widefat fixed" cellspacing="0" id="log-details-table">';
       
         foreach ($log_lines as $line) {
             echo '<tr><td>' . $line . '</td></tr>';
         }
         echo '</table>';
         echo '<a href="?page=custom-sub-menu" class="back-to-monetization button-primary" style="margin-top: 20px;">Back</a>';

     }else {
         echo 'No log data available for this post.';
     }  
 }

?>