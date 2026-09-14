/**
 * Investor.id Newsroom CMS Global Client Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Icons
    if (window.lucide) {
        window.lucide.createIcons();
    }

    // 2. Remove preload class after first render cycle to re-enable transitions cleanly
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.body.classList.remove('preload');
        });
    });

    // 3. Restore Desktop Sidebar Preference
    const isCollapsed = localStorage.getItem('investor_sidebar_collapsed') === '1';
    if (isCollapsed && window.innerWidth >= 1024) {
        applySidebarState(true);
    }

    // 4. Sidebar Scroll Position Preservation
    initSidebarScrollPreservation();
});

function initSidebarScrollPreservation() {
    const sidebarNav = document.getElementById('sidebarMainNavigation') || document.querySelector('nav[aria-label="Main navigation"]');
    if (!sidebarNav) return;

    // Restore saved scroll position or scroll active link into view
    const savedScroll = sessionStorage.getItem('investor_sidebar_scroll');
    if (savedScroll !== null) {
        sidebarNav.scrollTop = parseInt(savedScroll, 10);
    } else {
        const activeNav = sidebarNav.querySelector('.bg-sidebar-accent, .font-bold.text-sidebar-primary');
        if (activeNav) {
            activeNav.scrollIntoView({ block: 'nearest', behavior: 'instant' });
        }
    }

    // Save scroll position on scroll (passive)
    sidebarNav.addEventListener('scroll', () => {
        sessionStorage.setItem('investor_sidebar_scroll', sidebarNav.scrollTop);
    }, { passive: true });

    // Ensure scroll is preserved before clicking any sidebar link
    sidebarNav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            sessionStorage.setItem('investor_sidebar_scroll', sidebarNav.scrollTop);
        });
    });
}

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

    // Remove pre-render lock class
    document.documentElement.classList.remove('sidebar-pre-collapsed');

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
    toggleMobileNav(false);
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

// Global AI Loading Modal Helpers (Matching PortalBerita/BackendCMS)
function showAiLoading(message = 'Sedang memproses...') {
    const modal = document.getElementById('aiLoadingModal');
    const msgEl = document.getElementById('aiLoadingMessage');

    if (msgEl) msgEl.textContent = message;

    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function hideAiLoading() {
    const modal = document.getElementById('aiLoadingModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// Global AI Error / Limit Alert Modal Helpers
function showAiErrorAlert(errorMessage) {
    const modal = document.getElementById('aiErrorModal');
    const titleEl = document.getElementById('aiErrorModalTitle');
    const msgEl = document.getElementById('aiErrorModalMessage');

    const isRateLimit = /429|RESOURCE_EXHAUSTED|quota|rate limit|too many requests/i.test(errorMessage);

    if (titleEl) {
        titleEl.textContent = isRateLimit ? 'Batas Penggunaan AI Tercapai' : 'Pemberitahuan AI';
    }

    if (msgEl) {
        if (isRateLimit) {
            msgEl.innerHTML = `<strong>Layanan AI sedang sibuk atau batas kuota token telah tercapai.</strong><br><br>Sistem telah mencoba beberapa model fallback namun seluruh kuota request per menit saat ini sedang penuh.<br><br>Silakan tunggu 10–30 detik sebelum mencoba kembali, atau isi kolom editorial ini secara manual.`;
        } else {
            const cleanMsg = errorMessage.replace(/^Gagal (menghasilkan|merangkum|mengekstrak|melakukan):?\s*/i, '').trim();
            msgEl.textContent = cleanMsg || errorMessage;
        }
    }

    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    if (window.lucide) {
        window.lucide.createIcons({ root: modal });
    }
}

function closeAiErrorModal() {
    const modal = document.getElementById('aiErrorModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
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

// Sidebar Dropdown Toggle (Smooth Accordion behavior: only 1 dropdown open at a time)
function toggleSidebarDropdown(buttonEl) {
    const parent = buttonEl.closest('.sidebar-dropdown-wrapper');
    if (!parent) return;

    const currentMenu = parent.querySelector('.dropdown-menu');
    const currentArrow = parent.querySelector('.dropdown-arrow');
    const isCurrentlyOpen = currentMenu ? currentMenu.classList.contains('dropdown-open') : false;

    // Close all other dropdowns in this sidebar context smoothly
    const container = parent.closest('#sidebarMainNavigation') || document;
    container.querySelectorAll('.sidebar-dropdown-wrapper').forEach(wrapper => {
        if (wrapper !== parent) {
            const menu = wrapper.querySelector('.dropdown-menu');
            const arrow = wrapper.querySelector('.dropdown-arrow');
            if (menu) menu.classList.remove('dropdown-open');
            if (arrow) arrow.classList.remove('rotate-180');
        }
    });

    // Toggle clicked dropdown
    if (currentMenu) {
        if (isCurrentlyOpen) {
            currentMenu.classList.remove('dropdown-open');
            if (currentArrow) currentArrow.classList.remove('rotate-180');
        } else {
            currentMenu.classList.add('dropdown-open');
            if (currentArrow) currentArrow.classList.add('rotate-180');
        }
    }
}

// Theme Toggle Engine (Dark / Light Mode with Lucide Icon Sync)
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    const theme = isDark ? 'dark' : 'light';
    localStorage.setItem('investor_theme', theme);

    const btn = document.getElementById('globalThemeToggleBtn');
    if (btn) {
        btn.setAttribute('title', isDark ? 'Beralih ke Tema Terang (Light Mode)' : 'Beralih ke Tema Gelap (Dark Mode)');
        btn.setAttribute('aria-label', isDark ? 'Switch to Light Theme' : 'Switch to Dark Theme');
    }

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

