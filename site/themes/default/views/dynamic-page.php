<?php 
$this->component('header', ['page_title' => $page['page_title'] ?? '']); 

$isHome = ($pageSlug === 'home');
$pageType = $page['page_type'] ?? 'standard';

// Only use the restrictive "pulled up" panel for Home or the Ticket Portal
$panelClass = ($pageType === 'ticket_portal') ? 'glass-panel ticket-main-panel' : 'glass-panel';
?>

<main class="page-container">
    
    <?php if ($isHome): ?>
        <div class="hero-section">
            <div class="hero-badge">
                <i class="fa-solid fa-chart-line mr-5"></i> Technical Consultant & Developer
            </div>
            <h1 class="hero-title">Bridging Business Strategy<br>with <span>Modern Technology</span>.</h1>
            <p class="hero-subtitle">Specializing in ERP solutions, business controlling, and scalable web experiences.</p>
        </div>
    <?php else: ?>
        <div class="hero-section ticket-hero">
            <div class="hero-badge">
                <i class="fa-solid fa-chart-line mr-5"></i> Technical Consultant & Developer
            </div>
        </div>
    <?php endif; ?>

    <section class="<?php echo $panelClass; ?>">
        
        <div class="panel-header">
            <h2 class="panel-title"><?php echo htmlspecialchars($page['page_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>

        <div class="page-content">
            <?php 
            if ($pageType === 'ticket_portal') {
                
                if (!empty(trim(strip_tags($page['page_contents'])))) {
                    echo '<div class="mb-20">' . $page['page_contents'] . '</div>';
                }
                
                // Load the refactored ticket form
                $this->component('ticket-form', ['route' => $pageSlug]);
                
            } else {
                echo $page['page_contents']; 
            }
            ?>
        </div>
    </section>
</main>

<?php $this->component('footer'); ?>