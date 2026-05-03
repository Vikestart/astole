<?php
    $site_title = "Tickets";
    require "inc-adm-head.php";
    require "inc-adm-nav.php";

    if ($userdata->row['user_role'] == 3) { header("Location: index.php"); die(); }

    if (isset($_SESSION['Sessionmsg'])) {
        $msgorigin = $_SESSION['Sessionmsg']['origin']; $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
        unset($_SESSION['Sessionmsg']);
    }

    $db = new DBConn();
    // Fetch tickets with assigned staff name
    $tickets = $db->conn->query("SELECT t.*, u.user_uid AS assigned_name FROM tickets t LEFT JOIN users u ON t.assigned_to = u.user_id ORDER BY FIELD(t.status, 'Open', 'Answered', 'Closed'), t.updated_at DESC");
?>

<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-ticket-alt"></i> Tickets</h1>
    </div>

    <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border);">
                <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Tracking ID</th>
                <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Subject / Client</th>
                <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Status</th>
                <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Staff</th>
                <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Last Updated</th>
                <th style="padding: 15px; text-align: right; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tickets->num_rows > 0) {
                while($row = $tickets->fetch_assoc()) { 
                    $badge_class = 'badge-blue';
                    if ($row['status'] == 'Answered') $badge_class = 'badge-green';
                    if ($row['status'] == 'Closed') $badge_class = 'badge-gray';
            ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 15px;">
                            <a href="view-ticket.php?id=<?php echo $row['id']; ?>" style="color: var(--color-heading); text-decoration: none; transition: color 0.2s;">
                                <strong><?php echo htmlspecialchars($row['tracking_id']); ?></strong>
                            </a>
                        </td>
                        <td style="padding: 15px;">
                            <a href="view-ticket.php?id=<?php echo $row['id']; ?>" style="color: var(--color-heading); font-weight: 600; text-decoration: none; transition: color 0.2s;"><?php echo htmlspecialchars($row['subject']); ?></a>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?php echo htmlspecialchars($row['client_name']) . ' (' . htmlspecialchars($row['client_email']) . ')'; ?></div>
                        </td>
                        <td style="padding: 15px;"><span class="badge <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span></td>
                        <td style="padding: 15px;">
                            <?php if(!empty($row['assigned_name'])) { ?>
                                <span class="badge badge-gray"><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($row['assigned_name']); ?></span>
                            <?php } else { ?>
                                <span style="color: var(--text-muted); font-style: italic; font-size: 13px;">Unassigned</span>
                            <?php } ?>
                        </td>
                        <td style="padding: 15px; color: var(--text-muted); font-size: 14px;"><?php echo date('M d, Y H:i', strtotime($row['updated_at'])); ?></td>
                        <td style="padding: 15px; text-align: right;" class="table-actions">
                            <a href="view-ticket.php?id=<?php echo $row['id']; ?>" title="View"><i class="fa-solid fa-reply"></i></a>
                            
                            <form action="process-ticket.php" method="POST" class="form-delete" style="display:inline-block; margin-left: 15px;" onsubmit="return confirm('Are you sure you want to permanently delete this ticket and all its replies?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="action" value="delete_ticket">
                                <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="delete" title="Delete" style="background:none; border:none; cursor:pointer; font-size:16px; color:var(--text-muted); transition:color 0.2s;"><i class="fa-solid fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
            <?php } } else { ?>
                <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">No support tickets found.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<?php require "inc-adm-foot.php"; ?>