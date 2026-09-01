/**
 * Investor.id Newsroom CMS Global Client Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Icons
    if (window.lucide) {
        window.lucide.createIcons();
    }

    // 2. Restore Desktop Sidebar Preference
    const isCollapsed = localStorage.getItem('investor_sidebar_collapsed') === '1';
    if (isCollapsed && window.innerWidth >= 1024) {
        applySidebarState(true);
    }
});

function toggleDesktopSidebar() {
    const sidebar = document.getElementById('desktopSidebar');
    const isCurrentlyCollapsed = sidebar.classList.contains('sidebar-collapsed');
    const newState = !isCurrentlyCollapsed;
    applySidebarState(newState);
    localStorage.setItem('investor_sidebar_collapsed', newState ? '1' : '0');
}

function applySidebarState(collapsed) {
    const sidebar = document.getElementById('desktopSidebar');
    const wrapper = document.getElementById('mainWorkspaceWrapper');
    const icon = document.getElementById('sidebarCollapseIcon');
    const label = document.getElementById('sidebarCollapseLabel');

    if (!sidebar || !wrapper) return;

    if (collapsed) {
        sidebar.classList.add('sidebar-collapsed');
        wrapper.classList.remove('lg:pl-[264px]');
        wrapper.classList.add('lg:pl-[76px]');
        if (icon) icon.setAttribute('data-lucide', 'panel-left-open');
        if (label) label.textContent = 'Expand';
    } else {
        sidebar.classList.remove('sidebar-collapsed');
        wrapper.classList.remove('lg:pl-[76px]');
        wrapper.classList.add('lg:pl-[264px]');
        if (icon) icon.setAttribute('data-lucide', 'panel-left-close');
        if (label) label.textContent = 'Collapse';
    }

    if (window.lucide) window.lucide.createIcons();
}

// Mobile Navigation Toggle
function toggleMobileNav(open) {
    const overlay = document.getElementById('mobileDrawerOverlay');
    const sheet = document.getElementById('mobileDrawerSheet');

    if (!overlay || !sheet) return;

    if (open) {
        overlay.classList.remove('hidden');
        sheet.classList.remove('-translate-x-full');
    } else {
        overlay.classList.add('hidden');
        sheet.classList.add('-translate-x-full');
    }
}

// User Profile Dropdown Toggle
function toggleUserDropdown() {
    const menu = document.getElementById('userDropdownMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

document.addEventListener('click', (e) => {
    const menu = document.getElementById('userDropdownMenu');
    if (menu && !menu.classList.contains('hidden')) {
        if (!e.target.closest('#userDropdownMenu') && !e.target.closest('button[onclick*="toggleUserDropdown"]')) {
            menu.classList.add('hidden');
        }
    }
});

// Logout Dialog
function openLogoutModal() {
    const dialog = document.getElementById('logoutAlertDialog');
    if (dialog) {
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
    }
}

function closeLogoutModal() {
    const dialog = document.getElementById('logoutAlertDialog');
    if (dialog) {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
    }
}

// Global Toast notification engine (Replica of Sonner)
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-item';
    
    let iconName = 'info';
    let iconColor = 'text-primary';
    if (type === 'success') {
        iconName = 'check-circle';
        iconColor = 'text-success';
    } else if (type === 'error') {
        iconName = 'alert-triangle';
        iconColor = 'text-destructive';
    }

    toast.innerHTML = `
        <i data-lucide="${iconName}" class="size-4 shrink-0 ${iconColor}"></i>
        <span class="font-medium text-foreground">${message}</span>
    `;

    container.appendChild(toast);
    if (window.lucide) window.lucide.createIcons();

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.2s ease';
        setTimeout(() => toast.remove(), 200);
    }, 3500);
}
