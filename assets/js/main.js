/**
 * CivicTrack — Client-Side JavaScript
 */

/* ---------- Toast Notifications ---------- */
function showToast(message, type = 'info', duration = 3500) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    toast.innerHTML = `<span>${icons[type] || ''}</span><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(60px)';
        toast.style.transition = '0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/* ---------- Sidebar Toggle (Mobile) ---------- */
function initSidebar() {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
                sidebar.classList.remove('open');
            }
        });
    }
}

/* ---------- Image Preview ---------- */
function initImagePreview() {
    const input = document.getElementById('complaint-image');
    const preview = document.getElementById('image-preview');
    if (input && preview) {
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                    showToast('Please upload a valid image file (JPG, PNG, GIF, WebP)', 'error');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Image size must be under 5MB', 'error');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.src = ev.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    }
}

/* ---------- Form Validation ---------- */
function validateRegistrationForm(form) {
    const name = form.querySelector('#name');
    const email = form.querySelector('#email');
    const phone = form.querySelector('#phone');
    const password = form.querySelector('#password');
    const confirmPassword = form.querySelector('#confirm_password');

    if (!name.value.trim()) { showToast('Full Name is required', 'error'); name.focus(); return false; }
    if (!email.value.trim()) { showToast('Email is required', 'error'); email.focus(); return false; }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) { showToast('Enter a valid email address', 'error'); email.focus(); return false; }

    if (!phone.value.trim()) { showToast('Phone number is required', 'error'); phone.focus(); return false; }
    if (password.value.length < 6) { showToast('Password must be at least 6 characters', 'error'); password.focus(); return false; }
    if (password.value !== confirmPassword.value) { showToast('Passwords do not match', 'error'); confirmPassword.focus(); return false; }

    return true;
}

function validateLoginForm(form) {
    const email = form.querySelector('#email');
    const password = form.querySelector('#password');

    if (!email.value.trim()) { showToast('Email is required', 'error'); email.focus(); return false; }
    if (!password.value.trim()) { showToast('Password is required', 'error'); password.focus(); return false; }
    return true;
}

function validateComplaintForm(form) {
    const title = form.querySelector('#title');
    const category = form.querySelector('#category');
    const description = form.querySelector('#description');
    const location = form.querySelector('#location');

    if (!title.value.trim()) { showToast('Title is required', 'error'); title.focus(); return false; }
    if (!category.value) { showToast('Please select a category', 'error'); category.focus(); return false; }
    if (!description.value.trim()) { showToast('Description is required', 'error'); description.focus(); return false; }
    if (!location.value.trim()) { showToast('Location is required', 'error'); location.focus(); return false; }
    return true;
}

/* ---------- AJAX Helpers ---------- */
async function postForm(url, formData) {
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        return await res.json();
    } catch (err) {
        return { success: false, message: 'Network error — please try again.' };
    }
}

async function postJSON(url, data) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await res.json();
    } catch (err) {
        return { success: false, message: 'Network error — please try again.' };
    }
}

/* ---------- Table Search / Filter ---------- */
function initTableSearch(searchInputId, tableId) {
    const input = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', () => {
        const query = input.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}

function initStatusFilter(selectId, tableId) {
    const select = document.getElementById(selectId);
    const table = document.getElementById(tableId);
    if (!select || !table) return;

    select.addEventListener('change', () => {
        const status = select.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (!status) { row.style.display = ''; return; }
            const badge = row.querySelector('.badge');
            if (badge) {
                const rowStatus = badge.textContent.toLowerCase().trim();
                row.style.display = rowStatus.includes(status) ? '' : 'none';
            }
        });
    });
}

/* ---------- Modal ---------- */
function openModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.add('active');
}

function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.remove('active');
}

function initModals() {
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('active');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
}

/* ---------- Delete Confirmation ---------- */
function confirmDelete(message = 'Are you sure you want to delete this?') {
    return confirm(message);
}

/* ---------- Notifications System ---------- */
function getApiBase() {
    // Determine the API base relative to the current page
    const path = window.location.pathname;
    // If we're in a sub-directory like /admin/ or /user/ or /officer/
    if (path.includes('/admin/') || path.includes('/user/') || path.includes('/officer/')) {
        return '../api';
    }
    return 'api';
}

async function fetchNotifications() {
    try {
        const res = await fetch(getApiBase() + '/get_notifications.php');
        if (!res.ok) return null;
        return await res.json();
    } catch(e) { return null; }
}

async function markNotificationsRead() {
    try {
        await postJSON(getApiBase() + '/read_notifications.php', { mark_all: true });
    } catch(e) {}
}

function initNotifications() {
    // Only run on dashboard pages (those with a .page-header .user-info)
    const userInfo = document.querySelector('.page-header .user-info');
    if (!userInfo) return;

    // Create Bell Container
    const bellContainer = document.createElement('div');
    bellContainer.className = 'notification-bell';
    bellContainer.innerHTML = `
        <span style="font-size: 1.4rem;">🔔</span>
        <span class="notification-badge" style="display: none;">0</span>
        <div class="notification-dropdown">
            <div class="notification-header">
                <h4 style="margin: 0; font-size: 0.95rem;">Notifications</h4>
                <button id="mark-read-btn" style="background: none; border: none; font-size: 0.8rem; color: var(--primary); cursor: pointer; display: none;">Mark all read</button>
            </div>
            <div class="notification-list" id="notification-list">
                <div style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">Loading...</div>
            </div>
        </div>
    `;
    
    // Insert before the span that holds the username
    userInfo.insertBefore(bellContainer, userInfo.firstChild);

    const badge = bellContainer.querySelector('.notification-badge');
    const dropdown = bellContainer.querySelector('.notification-dropdown');
    const list = bellContainer.querySelector('#notification-list');
    const markReadBtn = bellContainer.querySelector('#mark-read-btn');

    // Persist shown notification IDs across page navigations using sessionStorage
    // This prevents repeated toast pop-ups every time the user switches dashboard sections
    function getShownIds() {
        try {
            return new Set(JSON.parse(sessionStorage.getItem('ct_shown_notif_ids') || '[]'));
        } catch(e) { return new Set(); }
    }
    function markShown(id) {
        const ids = getShownIds();
        ids.add(id);
        try { sessionStorage.setItem('ct_shown_notif_ids', JSON.stringify([...ids])); } catch(e) {}
    }
    function isAlreadyShown(id) {
        return getShownIds().has(id);
    }

    // Track whether this is the very first page load in this session
    const isFirstLoad = !sessionStorage.getItem('ct_notif_initialized');
    sessionStorage.setItem('ct_notif_initialized', '1');

    let isDropdownOpen = false;

    // Toggle dropdown
    bellContainer.addEventListener('click', async (e) => {
        if (e.target.closest('#mark-read-btn')) return; // ignore mark read click
        isDropdownOpen = !isDropdownOpen;
        dropdown.classList.toggle('active', isDropdownOpen);
        
        if (isDropdownOpen) {
            badge.style.display = 'none'; // hide badge when opened
            await markNotificationsRead(); // mark backend as read immediately
        }
    });
    
    markReadBtn.addEventListener('click', async () => {
        await markNotificationsRead();
        updateNotificationsList(true); // force visual read
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!bellContainer.contains(e.target) && isDropdownOpen) {
            isDropdownOpen = false;
            dropdown.classList.remove('active');
        }
    });

    // Polling function
    async function poll() {
        const data = await fetchNotifications();
        if (data && data.success) {
            const notifs = data.notifications;
            let unreadCount = data.unread_count;
            
            // Render list
            if (notifs.length === 0) {
                list.innerHTML = `<div style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No recent notifications.</div>`;
            } else {
                list.innerHTML = notifs.map(n => `
                    <div class="notification-item ${n.is_read ? 'read' : 'unread'}">
                        <div style="font-size: 0.85rem; color: var(--text-primary); margin-bottom: 0.25rem;">${n.message}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">${n.created_at}</div>
                    </div>
                `).join('');
                
                if (unreadCount > 0) {
                    markReadBtn.style.display = 'inline-block';
                }
            }

            // Only show toasts for notifications NOT already shown this session
            // AND only show them on the first page load OR when they come in via polling (not on page navigation)
            notifs.forEach(n => {
                if (!n.is_read && !isAlreadyShown(n.id)) {
                    if (isFirstLoad) {
                        // On first load only: show toasts for existing unread ones
                        showToast(n.message, 'info', 5000);
                    }
                    markShown(n.id);
                }
            });

            // During interval polling: show toast only for brand-new notifications
            // (those not in sessionStorage yet) — handled next poll cycle

            // Update badge if not currently opened
            if (!isDropdownOpen && unreadCount > 0) {
                badge.style.display = 'flex';
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            } else if (unreadCount === 0 || isDropdownOpen) {
                badge.style.display = 'none';
                markReadBtn.style.display = 'none';
            }
        }
    }

    function updateNotificationsList(forceRead) {
        if (forceRead) {
            list.querySelectorAll('.notification-item').forEach(el => {
                el.classList.remove('unread');
                el.classList.add('read');
            });
            markReadBtn.style.display = 'none';
            badge.style.display = 'none';
        }
    }

    // Initial poll (on page load)
    poll();
    
    // Subsequent polling — show toasts for genuinely NEW notifications only
    setInterval(async () => {
        const data = await fetchNotifications();
        if (data && data.success) {
            const notifs = data.notifications;
            let unreadCount = data.unread_count;

            // Re-render list
            if (notifs.length === 0) {
                list.innerHTML = `<div style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No recent notifications.</div>`;
            } else {
                list.innerHTML = notifs.map(n => `
                    <div class="notification-item ${n.is_read ? 'read' : 'unread'}">
                        <div style="font-size: 0.85rem; color: var(--text-primary); margin-bottom: 0.25rem;">${n.message}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">${n.created_at}</div>
                    </div>
                `).join('');
                if (unreadCount > 0) markReadBtn.style.display = 'inline-block';
            }

            // Show toast ONLY for notifications not yet seen this session (real new arrivals)
            notifs.forEach(n => {
                if (!n.is_read && !isAlreadyShown(n.id)) {
                    showToast(n.message, 'info', 5000); // Real new arrival — show toast
                    markShown(n.id);
                }
            });

            // Update badge
            if (!isDropdownOpen && unreadCount > 0) {
                badge.style.display = 'flex';
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            } else if (unreadCount === 0 || isDropdownOpen) {
                badge.style.display = 'none';
                markReadBtn.style.display = 'none';
            }
        }
    }, 15000); // Poll every 15 seconds
}

/* ---------- Init on DOM Ready ---------- */
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initImagePreview();
    initModals();
    initNotifications(); // Global Notifications init
    initAIRobot();      // AI Robot Mode init (Admin Only)
});

/* ---------- AI Robot Mode (Admin Only) ---------- */
function initAIRobot() {
    const roleEl = document.querySelector('.sidebar-user-role');
    if (!roleEl || !roleEl.textContent.includes('Administrator')) return;

    const apiBase = getApiBase();
    
    // Create Robot UI
    const robot = document.createElement('div');
    robot.className = 'ai-robot-float';
    robot.id = 'ai-robot-toggle';
    robot.innerHTML = `
        <img src="../assets/images/ai_robot.png" alt="AI Agent">
        <div class="ai-status-indicator"></div>
        <div class="ai-robot-tooltip"></div>
    `;
    
    const logContainer = document.createElement('div');
    logContainer.id = 'ai-logs-container';
    logContainer.innerHTML = `<div style="border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 0.5rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: bold; color: #06b6d4;">🤖 AI AGENT LOGS</span>
        <span id="close-ai-logs" style="cursor: pointer; opacity: 0.6;">&times;</span>
    </div><div id="ai-logs-list" style="flex: 1; overflow-y: auto;"></div>`;

    document.body.appendChild(robot);
    document.body.appendChild(logContainer);

    // Initial Position Check
    const savedPos = JSON.parse(localStorage.getItem('ai_robot_pos'));
    if (savedPos) {
        Object.assign(robot.style, {
            right: 'auto', bottom: 'auto',
            left: savedPos.x + 'px', top: savedPos.y + 'px'
        });
    }

    let aiModeActive = false;
    let loopInterval = null;
    let isDragging = false;
    let dragStartX, dragStartY, initialX, initialY;

    // Draggable Logic
    robot.addEventListener('mousedown', (e) => {
        if (e.target.closest('#close-ai-logs')) return;
        isDragging = false;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        const rect = robot.getBoundingClientRect();
        initialX = rect.left;
        initialY = rect.top;

        const onMouseMove = (moveEv) => {
            const dx = moveEv.clientX - dragStartX;
            const dy = moveEv.clientY - dragStartY;
            if (!isDragging && (Math.abs(dx) > 3 || Math.abs(dy) > 3)) {
                isDragging = true;
                robot.classList.add('dragging');
                setBubbleMessage("Wheee! Let's go!");
            }
            if (isDragging) {
                Object.assign(robot.style, {
                    right: 'auto', bottom: 'auto',
                    left: (initialX + dx) + 'px',
                    top: (initialY + dy) + 'px'
                });
            }
        };

        const onMouseUp = () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            if (isDragging) {
                const rect = robot.getBoundingClientRect();
                localStorage.setItem('ai_robot_pos', JSON.stringify({ x: rect.left, y: rect.top }));
                robot.classList.remove('dragging');
                setTimeout(() => { isDragging = false; }, 50);
                setBubbleMessage("I'll stay right here!");
            }
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    const setBubbleMessage = (msg, duration = 3000) => {
        const tooltip = robot.querySelector('.ai-robot-tooltip');
        tooltip.textContent = msg;
        tooltip.style.opacity = '1';
        tooltip.style.visibility = 'visible';
        tooltip.style.transform = 'translateX(-50%) translateY(0)';
        
        if (duration) {
            clearTimeout(robot._msgTimeout);
            robot._msgTimeout = setTimeout(() => {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
                tooltip.style.transform = 'translateX(-50%) translateY(10px)';
            }, duration);
        }
    };

    const addLog = (msg) => {
        const list = document.getElementById('ai-logs-list');
        const entry = document.createElement('div');
        entry.className = 'ai-log-entry';
        const time = new Date().toLocaleTimeString();
        entry.innerHTML = `<span class="ai-log-time">[${time}]</span> ${msg}`;
        list.prepend(entry);
        if (list.children.length > 20) list.lastChild.remove();
        if (!logContainer.classList.contains('visible')) logContainer.classList.add('visible');
    };

    const runAILoop = async () => {
        if (!aiModeActive) return;
        try {
            const res = await fetch(apiBase + '/ai_run_tasks.php');
            const data = await res.json();
            if (data.success && data.actions_taken > 0) {
                data.logs.forEach(log => {
                    addLog(log);
                    setBubbleMessage("Task Done: " + log);
                });
                if (window.location.pathname.includes('manage_')) {
                    setTimeout(() => location.reload(), 2000);
                }
            }
        } catch (e) { console.error("AI Loop Error", e); }
    };

    const setAIMode = (active) => {
        aiModeActive = active;
        if (active) {
            robot.classList.add('active');
            setBubbleMessage("AI Mode Active! I'm watching everything.");
            addLog("AI System Initialized...");
            loopInterval = setInterval(runAILoop, 5000);
            runAILoop();
        } else {
            robot.classList.remove('active');
            setBubbleMessage("AI Mode is OFF. Ready for manual control.");
            if (loopInterval) clearInterval(loopInterval);
            addLog("AI System Powered Down.");
        }
    };

    robot.addEventListener('click', async (e) => {
        if (isDragging) return;
        if (e.target.closest('#close-ai-logs')) return;
        
        const res = await postJSON(apiBase + '/ai_mode_control.php', { action: 'toggle' });
        if (res.success) {
            setAIMode(res.ai_mode);
        }
    });

    logContainer.querySelector('#close-ai-logs').addEventListener('click', () => {
        logContainer.classList.remove('visible');
    });

    // Welcome Greeting
    setTimeout(() => {
        setBubbleMessage("Welcome back, Chief! Need some AI help today?");
    }, 1000);

    // Initial state
    fetch(apiBase + '/ai_mode_control.php')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.ai_mode) setAIMode(true);
        });
}
