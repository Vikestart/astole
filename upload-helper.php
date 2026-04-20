<?php
function processTicketAttachment($file_array, $upload_dir) {
    if (!isset($file_array) || $file_array['error'] !== UPLOAD_ERR_OK) {
        return null; // No file uploaded or upload error
    }

    $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf', 'txt'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $file_tmp = $file_array['tmp_name'];
    $file_size = $file_array['size'];
    $file_name = $file_array['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Verify File Size
    if ($file_size > $max_size) { return ["error" => "File exceeds the 5MB limit."]; }

    // Verify Extension
    if (!in_array($file_ext, $allowed_exts)) { return ["error" => "Invalid file extension."]; }

    // Verify True MIME Type (Prevents renaming a .php file to .jpg)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_mimes)) { return ["error" => "Invalid file format detected."]; }

    // Generate safe, unique filename
    $new_filename = uniqid('att_') . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    if (move_uploaded_file($file_tmp, $upload_dir . '/' . $new_filename)) {
        return $new_filename;
    }

    return ["error" => "Failed to move uploaded file to destination."];
}
?>