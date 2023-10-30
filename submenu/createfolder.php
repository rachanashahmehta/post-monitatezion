<?php
// Create a folder in the wp-content/uploads directory
$upload_dir = wp_upload_dir();
$new_folder_path = $upload_dir['basedir'] . '/post-monitization-logs';

// Create the folder if it doesn't exist
if (!file_exists($new_folder_path)) {
    wp_mkdir_p($new_folder_path);
}
?>