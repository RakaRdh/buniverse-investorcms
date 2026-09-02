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

    if (window.lucide && sidebar) {
        window.lucide.createIcons({
            root: sidebar
        });
    }
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
    if (window.lucide) {
        window.lucide.createIcons({
            root: toast
        });
    }

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.2s ease';
        setTimeout(() => toast.remove(), 200);
    }, 3500);
}

// Sidebar Dropdown Toggle (Accordion behavior: only 1 dropdown open at a time)
function toggleSidebarDropdown(buttonEl) {
    const parent = buttonEl.closest('.sidebar-dropdown-wrapper');
    if (!parent) return;

    const currentMenu = parent.querySelector('.dropdown-menu');
    const currentArrow = parent.querySelector('.dropdown-arrow');
    const isCurrentlyHidden = currentMenu ? currentMenu.classList.contains('hidden') : true;

    // Close all other dropdowns in sidebar
    document.querySelectorAll('.sidebar-dropdown-wrapper').forEach(wrapper => {
        const menu = wrapper.querySelector('.dropdown-menu');
        const arrow = wrapper.querySelector('.dropdown-arrow');
        if (menu) menu.classList.add('hidden');
        if (arrow) arrow.classList.remove('rotate-180');
    });

    // Toggle clicked dropdown
    if (currentMenu && isCurrentlyHidden) {
        currentMenu.classList.remove('hidden');
        if (currentArrow) currentArrow.classList.add('rotate-180');
    }
}

// Theme Toggle Engine (Dark / Light Mode with Lucide Icon Sync)
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    const theme = isDark ? 'dark' : 'light';
    localStorage.setItem('investor_theme', theme);

    if (window.lucide) {
        window.lucide.createIcons();
    }

    // Trigger theme change event for third-party charts if present
    window.dispatchEvent(new CustomEvent('newsroom_theme_change', { detail: { theme } }));
}

// Back to Top Button Engine
function initBackToTop() {
    const btn = document.getElementById('backToTopBtn');
    if (!btn) return;

    function checkScroll() {
        const scrolled = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        if (scrolled > 150) {
            btn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
            btn.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
        } else {
            btn.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            btn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
        }
    }

    window.addEventListener('scroll', checkScroll, { passive: true });
    document.addEventListener('scroll', checkScroll, { passive: true });

    btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        document.documentElement.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Check once immediately
    checkScroll();
}

document.addEventListener('DOMContentLoaded', () => {
    initBackToTop();
});

