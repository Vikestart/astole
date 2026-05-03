<?php
// /core/init.php

// 1. Load the User's Configuration
$configFile = __DIR__ . '/../site/config/config.php';

if (!file_exists($configFile)) {
    // Future-proofing for the Installer: If no config exists, halt or redirect to installer.
    die('System Error: Configuration file missing. Please run the setup.');
}
require_once $configFile;

// 2. Register the Core Autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'Core\\';
    
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php. We use strtolower to keep file paths strictly lowercase (e.g., Core\Lib\Database -> /core/lib/database.php)
    $file = $base_dir . str_replace('\\', '/', strtolower($relative_class)) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});