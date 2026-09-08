/**
 * Modal Utilities for Investor CMS
 * Handles outside click closing, ESC key, and dirty form confirmation.
 */

window.ModalUtils = (function () {
    const registeredModals = new Map();

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
     * Register a modal with backdrop click and dirty check confirmation.
     * @param {string|HTMLElement} modalTarget - Modal ID string or HTMLElement
     * @param {Object} options
     * @param {Function} options.onClose - Function called to close the modal
     * @param {string} [options.dialogSelector] - Selector for dialog card container (default: first child or '.surface-card')
     * @param {string} [options.confirmMessage] - Message when user tries to close with dirty form
     */
    function register(modalTarget, options = {}) {
        const modalEl = typeof modalTarget === 'string' ? document.getElementById(modalTarget) : modalTarget;
        if (!modalEl) return;

        const onClose = options.onClose || function () {
            modalEl.style.display = 'none';
            modalEl.classList.add('hidden');
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

    document.addEventListener('DOMContentLoaded', initAutoModals);

    return {
        register,
        hasFormChanges,
        initAutoModals
    };
})();
