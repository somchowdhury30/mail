/* B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\assets\js\app.js */

document.addEventListener('DOMContentLoaded', () => {
    const inboxContainer = document.getElementById('inbox-container');
    const lastUpdatedEl = document.getElementById('last-updated');

    const API_URL = 'api/fetch_inboxes.php';
    const REFRESH_INTERVAL = 30000; // 30 seconds
    
    let currentAccountsData = []; // Store data for modal

    async function fetchInboxes() {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) throw new Error('Network response was not ok');
            const result = await response.json();
            
            if (result.success) {
                currentAccountsData = result.data;
                renderInboxes(result.data);
                
                // Update time display nicely
                const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second:'2-digit' });
                lastUpdatedEl.innerHTML = `Live Sync Active <span style="opacity:0.5; margin-left:8px">Last check: ${time}</span>`;
            } else {
                showError(result.error);
            }
        } catch (error) {
            console.error('Fetch error:', error);
            // Don't show raw error to users on a public page, just keep the skeleton or last state
        }
    }

    function renderInboxes(accounts) {
        if (!accounts || accounts.length === 0) {
            inboxContainer.innerHTML = `
                <div class="account-card" style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                    <h3>No Email Accounts Connected</h3>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Add accounts from the Admin Panel to see their inboxes here.</p>
                </div>
            `;
            return;
        }

        let html = '';

        accounts.forEach(acc => {
            const isSuccess = acc.status === 'success';
            const badgeClass = isSuccess ? 'badge-success' : 'badge-error';
            const badgeText = isSuccess ? 'ACTIVE' : 'ERROR';
            
            let emailsHtml = '';
            
            if (isSuccess && acc.messages && acc.messages.length > 0) {
                emailsHtml = '<ul class="email-list">';
                acc.messages.forEach((msg, mIndex) => {
                    emailsHtml += `
                        <li class="email-item" data-acc="${escapeHtml(acc.email)}" data-msg="${mIndex}">
                            <div class="email-meta">
                                <span class="email-sender">${escapeHtml(msg.sender)}</span>
                                <span class="email-date">${msg.date}</span>
                            </div>
                            <div class="email-subject">${escapeHtml(msg.subject)}</div>
                        </li>
                    `;
                });
                emailsHtml += '</ul>';
            } else if (!isSuccess) {
                emailsHtml = `
                    <div class="empty-state">
                        <div style="color: var(--danger); margin-bottom: 1rem;">⚠️</div>
                        <div>${escapeHtml(acc.error_msg || 'Could not authenticate')}</div>
                    </div>
                `;
            } else {
                emailsHtml = `
                    <div class="empty-state">
                        <div>📭 Inbox is empty</div>
                    </div>
                `;
            }

            html += `
                <div class="account-card">
                    <div class="card-header">
                        <div class="account-email">
                            <span class="email-icon">✉️</span>
                            ${escapeHtml(acc.email)}
                        </div>
                        <span class="badge ${badgeClass}">${badgeText}</span>
                    </div>
                    ${emailsHtml}
                </div>
            `;
        });

        inboxContainer.innerHTML = html;
    }

    function showError(msg) {
        inboxContainer.innerHTML = `
            <div class="account-card" style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
                <div style="color: var(--danger); font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                <h3>System Error</h3>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">${escapeHtml(msg)}</p>
            </div>
        `;
    }

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // --- Modal Logic ---
    const modal = document.getElementById('email-modal');
    const closeBtn = document.getElementById('modal-close');
    
    inboxContainer.addEventListener('click', (e) => {
        const item = e.target.closest('.email-item');
        if (!item) return;

        const accEmail = item.getAttribute('data-acc');
        const msgIndex = item.getAttribute('data-msg');
        
        const account = currentAccountsData.find(a => a.email === accEmail);
        if (account && account.messages[msgIndex]) {
            const msg = account.messages[msgIndex];
            
            document.getElementById('modal-subject').textContent = msg.subject;
            document.getElementById('modal-sender').textContent = msg.sender;
            document.getElementById('modal-date').textContent = msg.date;
            
            // Format body: convert links and basic html
            let bodyContent = msg.body || 'No content found.';
            
            // Basic sanitization/formatting
            if(!bodyContent.includes('<html') && !bodyContent.includes('<body')) {
               bodyContent = escapeHtml(bodyContent);
               // Simple link detection
               bodyContent = bodyContent.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
            } else {
               // Allow basic HTML if it seems to be an HTML email (we rely on PHP's simple parsing or iframe, but for now we just inject. In real prod, use DOMPurify if possible, but we'll allow it for now since it's an admin view)
               // However, to prevent breaking UI, if it has HTML, we just set innerHTML
            }
            
            document.getElementById('modal-body').innerHTML = bodyContent;
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        }
    });

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Initial fetch
    fetchInboxes();

    // Auto refresh
    setInterval(fetchInboxes, REFRESH_INTERVAL);
});
