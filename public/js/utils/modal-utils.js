/**
 * Modal Utilities for Investor CMS
 * Handles outside click closing, ESC key, and dirty form confirmation.
 */

window.ModalUtils = (function () {
    const registeredModals = new Map();
    let scrollLockCount = 0;

    /**
     * Lock body scroll to prevent background scrolling when modal is active.
     */
    function lockBodyScroll() {
        scrollLockCount++;
        if (scrollLockCount === 1) {
            const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
            if (scrollBarWidth > 0) {
                document.body.style.paddingRight = `${scrollBarWidth}px`;
            }
            document.body.classList.add('modal-open');
        }
    }

    /**
     * Unlock body scroll when all modals are closed.
     */
    function unlockBodyScroll() {
        scrollLockCount = Math.max(0, scrollLockCount - 1);
        if (scrollLockCount === 0) {
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        }
    }

    /**
     * Force reset body scroll if needed
     */
    function resetBodyScroll() {
        scrollLockCount = 0;
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
    }

    /**
     * Check if a container/form has dirty (user-entered) input values.
     */
    function hasFormChanges(container) {
        const inputs = container.querySelectorAll('input, textarea, select');
        for (const input of inputs) {
            // Ignore hidden inputs, search filter boxes, buttons, submit
            if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button') continue;
            if (input.dataset.noDirtyCheck !== undefined) continue;
            if (input.id && (input.id.includes('Search') || input.id.includes('Filter'))) continue;

            if (input.type === 'checkbox' || input.type === 'radio') {
                if (input.checked !== input.defaultChecked) return true;
            } else if (input.tagName === 'SELECT') {
                // If value changed from initial or selectedIndex > 0 (if default was 0)
                if (input.value && input.value !== (input.defaultValue || '')) return true;
            } else {
                // text, email, password, textarea, number, etc.
                if (input.value && input.value.trim() !== (input.defaultValue || '').trim()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Register a modal with backdrop click, dirty check confirmation, and body scroll locking.
     * @param {string|HTMLElement} modalTarget - Modal ID string or HTMLElement
     * @param {Object} options
     * @param {Function} options.onClose - Function called to close the modal
     * @param {string} [options.dialogSelector] - Selector for dialog card container (default: first child or '.surface-card')
     * @param {string} [options.confirmMessage] - Message when user tries to close with dirty form
     */
    function register(modalTarget, options = {}) {
        const modalEl = typeof modalTarget === 'string' ? document.getElementById(modalTarget) : modalTarget;
        if (!modalEl) return;

        const originalOnClose = options.onClose || function () {
            modalEl.style.display = 'none';
            modalEl.classList.add('hidden');
        };

        const onClose = function () {
            originalOnClose();
            unlockBodyScroll();
        };

        const confirmMsg = options.confirmMessage || 'Formulir memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin menutup?';

        function handleBackdropClick(e) {
            // Target must be the backdrop itself, not its children
            if (e.target === modalEl) {
                attemptClose();
            }
        }

        function attemptClose() {
            if (hasFormChanges(modalEl)) {
                if (window.confirm(confirmMsg)) {
                    onClose();
                }
            } else {
                onClose();
            }
        }

        // Attach backdrop click
        modalEl.addEventListener('click', handleBackdropClick);

        registeredModals.set(modalEl, {
            attemptClose,
            onClose,
            cleanup: () => {
                modalEl.removeEventListener('click', handleBackdropClick);
                registeredModals.delete(modalEl);
            }
        });

        return { attemptClose };
    }

    /**
     * Observer to automatically lock/unlock body scroll for ANY modal in the DOM
     * based on inline style (display) or Tailwind 'hidden' class changes.
     */
    function initGlobalModalObserver() {
        const observer = new MutationObserver((mutations) => {
            let anyModalOpen = false;

            // Check all elements that act as full-screen modal overlays
            const allModals = document.querySelectorAll(
                '[id*="Modal"], [id*="Dialog"], [data-modal], .modal-backdrop, [style*="position: fixed"][style*="inset: 0"]'
            );

            allModals.forEach((el) => {
                if (el.id === 'mobileDrawerOverlay' || el.id === 'toast-container' || el.classList.contains('pointer-events-none')) return;

                const isInlineVisible = el.style.display && el.style.display !== 'none';
                const isTailwindVisible = !el.classList.contains('hidden') && el.classList.contains('fixed');

                if (isInlineVisible || (isTailwindVisible && getComputedStyle(el).display !== 'none')) {
                    anyModalOpen = true;
                }
            });

            if (anyModalOpen) {
                if (!document.body.classList.contains('modal-open')) {
                    const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
                    if (scrollBarWidth > 0) {
                        document.body.style.paddingRight = `${scrollBarWidth}px`;
                    }
                    document.body.classList.add('modal-open');
                }
            } else {
                if (document.body.classList.contains('modal-open')) {
                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';
                    scrollLockCount = 0;
                }
            }
        });

        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['style', 'class'],
            subtree: true
        });
    }

    /**
     * Handle global Escape key to close topmost active modal
     */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' || e.key === 'Esc') {
            for (const [modalEl, config] of registeredModals.entries()) {
                const isVisible = (modalEl.style.display && modalEl.style.display !== 'none') ||
                                  (!modalEl.classList.contains('hidden') && getComputedStyle(modalEl).display !== 'none');
                if (isVisible) {
                    config.attemptClose();
                    break;
                }
            }
        }
    });

    /**
     * Auto-init all modals marked with data-modal-backdrop="auto"
     */
    function initAutoModals() {
        document.querySelectorAll('[data-modal-backdrop="auto"]').forEach((modalEl) => {
            if (!registeredModals.has(modalEl)) {
                const closeFnName = modalEl.dataset.closeFn;
                const closeFn = closeFnName && typeof window[closeFnName] === 'function' ? window[closeFnName] : null;
                register(modalEl, { onClose: closeFn });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initAutoModals();
        initGlobalModalObserver();
    });

    return {
        register,
        hasFormChanges,
        lockBodyScroll,
        unlockBodyScroll,
        resetBodyScroll,
        initAutoModals
    };
})();

