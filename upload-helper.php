<?php
function processMultipleAttachments($file_array, $upload_dir) {
    if (!isset($file_array['name'][0]) || empty($file_array['name'][0])) { return null; }

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt'];
    $max_size = 5 * 1024 * 1024; // 5MB per file
    $uploaded_files = [];

    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    $file_count = count($file_array['name']);
    for ($i = 0; $i < $file_count; $i++) {
        if ($file_array['error'][$i] !== UPLOAD_ERR_OK) { continue; }

        $file_tmp = $file_array['tmp_name'][$i];
        $file_name = $file_array['name'][$i];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_array['size'][$i] > $max_size) { continue; }
        if (!in_array($file_ext, $allowed_exts)) { continue; }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_mimes)) { continue; }

        $new_filename = uniqid('att_') . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        if (move_uploaded_file($file_tmp, $upload_dir . '/' . $new_filename)) {
            $uploaded_files[] = $new_filename;
        }
    }
    
    return !empty($uploaded_files) ? json_encode($uploaded_files) : null;
}
?>