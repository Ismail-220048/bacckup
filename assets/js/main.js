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

/* ---------- Init on DOM Ready ---------- */
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initImagePreview();
    initModals();
});
