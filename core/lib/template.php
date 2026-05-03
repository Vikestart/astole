<?php
// /core/lib/template.php
namespace Core\Lib;

class Template {
    private string $themePath;
    private string $themeUrl;

    public function __construct() {
        // Fallback to 'default' if not defined in config.php
        $theme = defined('ACTIVE_THEME') ? ACTIVE_THEME : 'default';
        
        // Physical server path for requiring PHP files
        $this->themePath = __DIR__ . '/../../site/themes/' . $theme . '/';
        
        // Web URL path for linking CSS, JS, and Images
        $this->themeUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/site/themes/' . $theme . '/' : '/site/themes/' . $theme . '/';
    }

    /**
     * Loads a main page view (e.g., ticket.php, home.php)
     * Returns true if loaded, false if missing.
     */
    public function render(string $view, array $data = []): bool {
        extract($data);
        $viewFile = $this->themePath . 'views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
            return true;
        }
        
        return false; // Tell the router the view is missing
    }

    /**
     * Loads a reusable UI snippet (e.g., header.php, footer.php)
     */
    public function component(string $name, array $data = []): void {
        extract($data);
        $componentFile = $this->themePath . 'components/' . $name . '.php';
        
        if (file_exists($componentFile)) {
            require $componentFile;
        }
    }
    
    /**
     * Generates an absolute URL to a theme asset
     */
    public function asset(string $path): string {
        return $this->themeUrl . 'assets/' . ltrim($path, '/');
    }
}