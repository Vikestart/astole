document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.getElementById('global-csrf').value;
    const msgBox = document.getElementById('ajax-msgbox');
    const tableBody = document.getElementById('tickets-table-body');
    const viewList = document.getElementById('view-list');
    const viewTicketPanel = document.getElementById('view-ticket');
    
    // UI Elements
    const threadContainer = document.getElementById('ticket-thread-container');
    const replyForm = document.getElementById('ajax-reply-form');
    const assignForm = document.getElementById('ajax-assign-form');
    const assignSelect = document.getElementById('assign-select');

    function showMsg(type, msg) {
        msgBox.className = `msgbox msgbox-${type}`;
        msgBox.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'times-circle'}"></i> ${msg}`;
        msgBox.style.display = 'flex';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { msgBox.style.display = 'none'; }, 4000);
    }

    let currentPage = 1;

    // 1. Core Functions
    function loadList(page = 1) {
        currentPage = page;
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center p-20"><i class="fa-solid fa-spinner fa-spin mr-10"></i> Loading tickets...</td></tr>';
        
        const formData = new FormData();
        formData.append('action', 'get_list');
        formData.append('page', currentPage);
        
        // Grab values if the filter elements exist
        const searchVal = document.getElementById('filter-search')?.value || '';
        const statusVal = document.getElementById('filter-status')?.value || 'All';
        formData.append('search', searchVal);
        formData.append('filter_status', statusVal);
        
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-tickets.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { 
            if (data.status === 'success') {
                tableBody.innerHTML = data.html; 
                document.getElementById('tickets-pagination').innerHTML = data.pagination || '';
            }
        });
    }
    window.loadList = loadList;

    function showList() {
        viewTicketPanel.style.display = 'none';
        viewList.style.display = 'block';
        window.history.pushState({}, '', '/adm/tickets');
        loadList();
    }
    window.showList = showList;

    function viewTicket(id) {
        viewList.style.display = 'none';
        viewTicketPanel.style.display = 'block';
        document.getElementById('header-delete-ticket-btn').onclick = () => window.deleteTicket(id);
        window.history.pushState({}, '', `/adm/tickets?action=view&id=${id}`);
        loadTicketData(id);
    }
    window.viewTicket = viewTicket;

    function loadTicketData(id, silent = false) {
        if (!silent) threadContainer.innerHTML = '<div class="text-center p-20"><i class="fa-solid fa-spinner fa-spin mr-10"></i> Loading thread...</div>';
        
        const formData = new FormData();
        formData.append('action', 'get_ticket');
        formData.append('ticket_id', id);
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-tickets.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const t = data.ticket;
                document.getElementById('ticket-header-title').innerHTML = `<i class="fa-solid fa-ticket-alt mr-10"></i> Ticket: ${t.tracking_id}`;
                
                // Update Client Data & Avatar using the new CSS class
                document.getElementById('detail-subject').innerText = t.subject;
                const avatarLetter = t.client_name ? t.client_name.charAt(0).toUpperCase() : '?';
                document.getElementById('detail-avatar').innerText = avatarLetter;
                document.getElementById('detail-client').innerHTML = `<strong class="ticket-client-name">${t.client_name}</strong><br>${t.client_email}`;
                
                const badge = document.getElementById('detail-status-badge');
                badge.className = 'badge ticket-status-badge-lg ' + (t.status === 'Answered' ? 'badge-green' : (t.status === 'Closed' ? 'badge-gray' : 'badge-blue'));
                badge.innerText = t.status;

                document.getElementById('reply-ticket-id').value = t.id;
                document.getElementById('assign-ticket-id').value = t.id;
                document.getElementById('reply-status-select').value = (t.status === 'Closed') ? 'Closed' : 'Answered';
                
                assignSelect.innerHTML = data.staff_opts;
                threadContainer.innerHTML = data.thread_html;
                
                if (!silent) threadContainer.scrollTop = threadContainer.scrollHeight;
            }
        });
    }
    window.loadTicketData = loadTicketData;

    // 2. Form Submissions
    if (replyForm) {
        replyForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = document.getElementById('reply-submit-btn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            btn.disabled = true;

            const formData = new FormData(replyForm);
            formData.append('action', 'admin_reply');
            formData.append('csrf_token', csrfToken);

            fetch('/core/actions/adm/ajax-tickets.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                showMsg(data.status, data.message);
                if (data.status === 'success') {
                    replyForm.reset();
                    document.getElementById('file-preview').innerText = 'Max size: 5MB per file. Formats: JPG, PNG, WEBP, PDF, TXT.';
                    loadTicketData(document.getElementById('reply-ticket-id').value, true);
                }
            });
        });

        document.getElementById('file-input').addEventListener('change', function() {
            const preview = document.getElementById('file-preview');
            if (this.files.length > 0) {
                preview.innerHTML = `<strong>${this.files.length} file(s) selected</strong>`;
            } else {
                preview.innerText = 'Max size: 5MB per file. Formats: JPG, PNG, WEBP, PDF, TXT.';
            }
        });
    }

    if (assignSelect) {
        assignSelect.addEventListener('change', () => {
            const formData = new FormData(assignForm);
            formData.append('action', 'assign_ticket');
            formData.append('csrf_token', csrfToken);

            fetch('/core/actions/adm/ajax-tickets.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showMsg(data.status, data.message);
                if (data.status === 'success') {
                    loadTicketData(document.getElementById('assign-ticket-id').value, true);
                }
            });
        });
    }

    // 3. Delete Logic
    let idToDelete = null;
    const deleteModal = document.getElementById('delete-modal');

    window.deleteTicket = function(id) {
        idToDelete = id;
        deleteModal.style.display = 'flex';
    };
    
    window.closeDeleteModal = function() {
        idToDelete = null;
        deleteModal.style.display = 'none';
    };

    document.getElementById('confirm-delete-btn').addEventListener('click', function() {
        if (!idToDelete) return;
        
        // FIX: Cache the ID locally so it survives the modal closing!
        const currentId = idToDelete;
        
        closeDeleteModal(); 

        const formData = new FormData();
        formData.append('action', 'delete_ticket');
        formData.append('ticket_id', currentId); // Use the cached ID!
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-tickets.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            showMsg(data.status, data.message);
            if (data.status === 'success') showList(); 
        });
    });

    // 4. Initial Router
    const urlParams = new URLSearchParams(window.location.search);
    const routeAction = urlParams.get('action');
    const routeId = urlParams.get('id');

    if (routeAction === 'view' && routeId) {
        viewTicket(routeId);
    } else {
        loadList();
    }
});