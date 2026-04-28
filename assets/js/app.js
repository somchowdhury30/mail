/* B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\assets\js\app.js */

document.addEventListener('DOMContentLoaded', () => {
    const inboxContainer = document.getElementById('inbox-container');
    const lastUpdatedEl = document.getElementById('last-updated');

    const API_URL = 'api/fetch_inboxes.php';
    const REFRESH_INTERVAL = 30000; // 30 seconds
    
    let currentAccountsData = []; // Store data for modal

    async function fetchInboxes() {
        try {
            const aliasInput = document.getElementById('alias-input');
            const toEmail = aliasInput ? aliasInput.value.trim() : '';
            const queryUrl = toEmail ? `${API_URL}?to_email=${encodeURIComponent(toEmail)}` : API_URL;

            const response = await fetch(queryUrl);
            if (!response.ok) throw new Error('Network response was not ok');
            const result = await response.json();
            
            if (result.success) {
                currentAccountsData = result.data;
                renderInboxes(result.data);
                
                // Update time display nicely
                const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second:'2-digit' });
                lastUpdatedEl.innerHTML = `Live Sync Active <span class="hide-on-mobile" style="opacity:0.5; margin-left:8px">Last check: ${time}</span>`;
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
            
            // Format body: Extract clean minimal text
            let rawBody = msg.body || 'No content found.';
            let cleanText = rawBody;

            if (rawBody.includes('<html') || rawBody.includes('<body') || rawBody.includes('<table') || rawBody.includes('<div')) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(rawBody, 'text/html');
                
                // Remove scripts, styles, and head elements
                const injectables = doc.querySelectorAll('script, style, head, meta, title');
                injectables.forEach(el => el.remove());
                
                cleanText = doc.body.innerText || doc.body.textContent || '';
                cleanText = cleanText.split('\n').map(line => line.trim()).filter(line => line.length > 0).join('\n\n');
            }

            cleanText = escapeHtml(cleanText);
            // Simple link detection
            cleanText = cleanText.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');

            document.getElementById('modal-body').innerHTML = cleanText;
            
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

    // Temp mail Check button logic
    const fetchBtn = document.getElementById('fetch-btn');
    if (fetchBtn) {
        fetchBtn.addEventListener('click', () => {
            inboxContainer.innerHTML = `
                <div class="skeleton-card"><div class="skeleton-header"></div><div class="skeleton-line"></div><div class="skeleton-line"></div></div>
            `;
            fetchInboxes();
        });
    }

    // Generate random alias logic
    const generateBtn = document.getElementById('generate-btn');
    const aliasInput = document.getElementById('alias-input');
    
    if (generateBtn && aliasInput) {
        generateBtn.addEventListener('click', () => {
            if (currentAccountsData && currentAccountsData.length > 0) {
                // Pick the first account as root
                const rootEmail = currentAccountsData[0].email;
                if (rootEmail && rootEmail.includes('@')) {
                    const parts = rootEmail.split('@');
                    
                    const words = ['koala', 'tiger', 'cheetah', 'lion', 'bear', 'panda', 'fox', 'wolf', 'hawk'];
                    const randomWord = words[Math.floor(Math.random() * words.length)];
                    const randomNum = Math.floor(Math.random() * 100).toString().padStart(2, '0');
                    
                    const generatedAlias = `${parts[0]}+${randomWord}${randomNum}@${parts[1]}`;
                    aliasInput.value = generatedAlias;
                    
                    // Optional: automatically fetch
                    // fetchBtn.click();
                }
            } else {
                alert("Please wait for accounts to load from backend first!");
            }
        });
    }

    // Allow enter key on input
    if (aliasInput) {
        aliasInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                fetchBtn.click();
            }
        });
    }

    // Initial fetch
    fetchInboxes();

    // Auto refresh
    setInterval(fetchInboxes, REFRESH_INTERVAL);
});
