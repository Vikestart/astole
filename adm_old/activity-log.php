<?php
    $site_title = "Activity Log";
    require "inc-adm-head.php";
    require "inc-adm-nav.php";

    if ($userdata->row['user_role'] != 1) {
        header("Location: index.php");
        die();
    }

    $db = new DBConn();
    // Only load the first 20 initially!
    $stmt = $db->conn->prepare("SELECT a.*, u.user_uid, u.user_mail FROM activity_log a LEFT JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 20");
    $stmt->execute();
    $res = $stmt->get_result();
    $logs = [];
    while ($r = $res->fetch_assoc()) { $logs[] = $r; }
    $stmt->close();
?>

<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin: 0; font-size: 24px; color: var(--color-heading);"><i class="fa-solid fa-clipboard-list"></i> System Activity Log</h1>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--bg-body); text-align: left; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; color: var(--text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase;">Date & Time</th>
                        <th style="padding: 15px 20px; color: var(--text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase;">User</th>
                        <th style="padding: 15px 20px; color: var(--text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase;">Category</th>
                        <th style="padding: 15px 20px; color: var(--text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase;">Action Details</th>
                        <th style="padding: 15px 20px; color: var(--text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase;">IP Address</th>
                    </tr>
                </thead>
                <tbody id="log-table-body">
                    <?php if (empty($logs)) { ?>
                        <tr><td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">No activity recorded yet.</td></tr>
                    <?php } else { ?>
                        <?php foreach ($logs as $log) { 
                            $badge_class = 'badge-gray';
                            if ($log['action_type'] === 'User') $badge_class = 'badge-blue';
                            if ($log['action_type'] === 'Page') $badge_class = 'badge-green';
                            if ($log['action_type'] === 'Settings') $badge_class = 'badge-red';
                            if ($log['action_type'] === 'Security') $badge_class = 'badge-yellow';
                            if ($log['action_type'] === 'Ticket') $badge_class = 'badge-blue';
                        ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 15px 20px; font-size: 14px; color: var(--text-muted); white-space: nowrap;"><?php echo date('M d, Y - H:i', strtotime($log['created_at'])); ?></td>
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 600; color: var(--color-heading); font-size: 14px;"><?php echo htmlspecialchars($log['user_uid'] ?? 'System / Deleted User'); ?></div>
                                </td>
                                <td style="padding: 15px 20px;"><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($log['action_type']); ?></span></td>
                                <td style="padding: 15px 20px; font-size: 14px; color: var(--text-main);"><?php echo htmlspecialchars($log['action_desc']); ?></td>
                                <td style="padding: 15px 20px; font-size: 13px; color: var(--text-muted); font-family: monospace;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($logs) === 20) { ?>
            <div style="padding: 20px; text-align: center; background: var(--bg-surface); border-top: 1px solid var(--border);">
                <button id="load-more-btn" class="btn btn-secondary" data-offset="20"><i class="fa-solid fa-rotate"></i> Load Older Activity</button>
            </div>
        <?php } ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loadBtn = document.getElementById('load-more-btn');
    const tableBody = document.getElementById('log-table-body');

    if (loadBtn) {
        loadBtn.addEventListener('click', function() {
            const offset = parseInt(this.getAttribute('data-offset'));
            const originalText = this.innerHTML;
            
            // Show loading state
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
            this.disabled = true;

            fetch(`ajax-activity-log.php?offset=${offset}`)
                .then(response => response.text())
                .then(html => {
                    if (html.trim() === '') {
                        // No more records to load
                        this.style.display = 'none';
                    } else {
                        // Append the new HTML rows
                        tableBody.insertAdjacentHTML('beforeend', html);
                        
                        // Increase the offset by 20 for the next click
                        this.setAttribute('data-offset', offset + 20);
                        
                        // Restore button
                        this.innerHTML = originalText;
                        this.disabled = false;

                        // If less than 20 rows were returned, we hit the end
                        const rowCount = (html.match(/<tr/g) || []).length;
                        if (rowCount < 20) {
                            this.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading logs:', error);
                    this.innerHTML = 'Error loading. Try again.';
                    this.disabled = false;
                });
        });
    }
});
</script>

<?php require "inc-adm-foot.php"; ?>