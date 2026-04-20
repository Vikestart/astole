<?php
function processMultipleAttachments($file_array, $upload_dir) {
    if (!isset($file_array['name'])) { return null; }

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt'];
    $max_size = 5 * 1024 * 1024; // 5MB per file
    $uploaded_files = [];

    if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0755, true); }

    // Normalize input so it safely handles both single and multiple file arrays
    $names = is_array($file_array['name']) ? $file_array['name'] : [$file_array['name']];
    $tmp_names = is_array($file_array['tmp_name']) ? $file_array['tmp_name'] : [$file_array['tmp_name']];
    $sizes = is_array($file_array['size']) ? $file_array['size'] : [$file_array['size']];
    $errors = is_array($file_array['error']) ? $file_array['error'] : [$file_array['error']];

    $file_count = count($names);
    for ($i = 0; $i < $file_count; $i++) {
        if ($errors[$i] !== UPLOAD_ERR_OK || empty($names[$i])) { continue; }

        $file_tmp = $tmp_names[$i];
        $file_name = $names[$i];
        $file_size = $sizes[$i];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_size > $max_size) { continue; }
        if (!in_array($file_ext, $allowed_exts)) { continue; }

        // Only check MIME if the server supports finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            if (!in_array($mime_type, $allowed_mimes)) { continue; }
        }

        $new_filename = uniqid('att_') . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        if (move_uploaded_file($file_tmp, $upload_dir . '/' . $new_filename)) {
            $uploaded_files[] = $new_filename;
        }
    }
    
    return !empty($uploaded_files) ? json_encode($uploaded_files) : null;
}
?>