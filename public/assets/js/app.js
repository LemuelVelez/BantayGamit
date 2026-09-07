(() => {
    const body = document.body;
    const navToggle = document.querySelector('[data-nav-toggle]');
    const desktopNav = window.matchMedia('(min-width: 901px)');
    const sidebarPreferenceKey = 'bantaygamit.sidebar.collapsed';

    const setToggleState = (expanded, label) => {
        navToggle?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        if (label) navToggle?.setAttribute('aria-label', label);
    };

    const applyDesktopSidebarPreference = () => {
        if (!desktopNav.matches) {
            body.classList.remove('sidebar-collapsed');
            return;
        }
        let collapsed = false;
        try {
            collapsed = localStorage.getItem(sidebarPreferenceKey) === '1';
        } catch (_) {}
        body.classList.toggle('sidebar-collapsed', collapsed);
        setToggleState(!collapsed, collapsed ? 'Expand navigation' : 'Collapse navigation');
    };

    const closeNav = () => {
        body.classList.remove('nav-open');
        if (!desktopNav.matches) setToggleState(false, 'Open navigation');
    };

    applyDesktopSidebarPreference();

    navToggle?.addEventListener('click', () => {
        if (desktopNav.matches) {
            const collapsed = !body.classList.contains('sidebar-collapsed');
            body.classList.toggle('sidebar-collapsed', collapsed);
            setToggleState(!collapsed, collapsed ? 'Expand navigation' : 'Collapse navigation');
            try {
                localStorage.setItem(sidebarPreferenceKey, collapsed ? '1' : '0');
            } catch (_) {}
            return;
        }

        const open = !body.classList.contains('nav-open');
        body.classList.toggle('nav-open', open);
        setToggleState(open, open ? 'Close navigation' : 'Open navigation');
    });

    const syncNavigationMode = () => {
        body.classList.remove('nav-open');
        if (desktopNav.matches) applyDesktopSidebarPreference();
        else {
            body.classList.remove('sidebar-collapsed');
            setToggleState(false, 'Open navigation');
        }
    };

    if (desktopNav.addEventListener) desktopNav.addEventListener('change', syncNavigationMode);
    else desktopNav.addListener(syncNavigationMode);

    const accountMenu = document.querySelector('[data-account-menu]');
    const accountToggle = accountMenu?.querySelector('[data-account-toggle]');
    const accountDropdown = accountMenu?.querySelector('[data-account-dropdown]');

    const closeAccount = () => {
        accountDropdown?.setAttribute('hidden', '');
        accountToggle?.setAttribute('aria-expanded', 'false');
    };

    accountToggle?.addEventListener('click', () => {
        const open = accountDropdown?.hasAttribute('hidden') ?? false;
        if (open) accountDropdown?.removeAttribute('hidden');
        else accountDropdown?.setAttribute('hidden', '');
        accountToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', (event) => {
        if (accountMenu && !accountMenu.contains(event.target)) closeAccount();
    });

    const updatePasswordToggle = (button, input, visible) => {
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        button.title = visible ? 'Hide password' : 'Show password';
        const use = button.querySelector('use');
        if (use) {
            const href = use.getAttribute('href') || use.getAttribute('xlink:href') || '';
            const sprite = href.split('#')[0];
            use.setAttribute('href', `${sprite}#${visible ? 'eye-off' : 'eye'}`);
        }
    };

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        updatePasswordToggle(button, input, input.type !== 'password');
        button.addEventListener('click', () => {
            updatePasswordToggle(button, input, input.type === 'password');
            input.focus({ preventScroll: true });
        });
    });

    const confirmDialog = document.querySelector('[data-confirm-dialog]');
    const confirmMessage = confirmDialog?.querySelector('[data-confirm-message]');
    const confirmCancel = confirmDialog?.querySelector('[data-confirm-cancel]');
    const confirmProceed = confirmDialog?.querySelector('[data-confirm-proceed]');
    let pendingForm = null;
    let pendingSubmitter = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }
            event.preventDefault();
            pendingForm = form;
            pendingSubmitter = event.submitter || form.querySelector('button[type="submit"],input[type="submit"]');
            if (confirmMessage) confirmMessage.textContent = form.dataset.confirm || 'Confirm this action?';
            if (confirmDialog && !confirmDialog.open) {
                confirmDialog.showModal();
                confirmCancel?.focus({ preventScroll: true });
            }
        });
    });

    const clearPendingConfirmation = () => {
        pendingForm = null;
        pendingSubmitter = null;
    };

    confirmCancel?.addEventListener('click', () => {
        confirmDialog?.close();
        clearPendingConfirmation();
    });

    confirmProceed?.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        const submitter = pendingSubmitter;
        clearPendingConfirmation();
        form.dataset.confirmed = '1';
        confirmDialog?.close();
        if (submitter instanceof HTMLElement && submitter.isConnected) form.requestSubmit(submitter);
        else form.requestSubmit();
    });

    confirmDialog?.addEventListener('cancel', clearPendingConfirmation);
    confirmDialog?.addEventListener('close', () => {
        if (confirmDialog.returnValue !== 'proceed') clearPendingConfirmation();
    });

    const requestItems = document.querySelector('[data-items]');
    const itemTemplate = document.querySelector('[data-item-template]');

    document.querySelector('[data-add-item]')?.addEventListener('click', () => {
        if (!requestItems || !itemTemplate) return;
        const fragment = itemTemplate.content.cloneNode(true);
        const select = fragment.querySelector('select[name="equipment_id[]"]');
        requestItems.append(fragment);
        select?.focus({ preventScroll: true });
    });

    requestItems?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-item]');
        if (button && requestItems.querySelectorAll('.request-item').length > 1) {
            button.closest('.request-item')?.remove();
        }
    });

    const syncRequestQuantity = (select) => {
        const row = select.closest('.request-item');
        const quantity = row?.querySelector('input[name="quantity[]"]');
        const selected = select.selectedOptions?.[0];
        const available = Number.parseInt(selected?.dataset.available || '', 10);
        if (!quantity) return;
        if (Number.isFinite(available) && available > 0) {
            quantity.max = String(available);
            if (Number.parseInt(quantity.value || '0', 10) > available) quantity.value = String(available);
        } else {
            quantity.removeAttribute('max');
        }
    };

    requestItems?.addEventListener('change', (event) => {
        if (event.target.matches('select[name="equipment_id[]"]')) syncRequestQuantity(event.target);
    });
    requestItems?.querySelectorAll('select[name="equipment_id[]"]').forEach(syncRequestQuantity);

    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.dialogOpen);
            if (dialog && !dialog.open) dialog.showModal();
        });
    });

    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelectorAll('dialog.form-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    const autoDialog = document.querySelector('dialog[data-dialog-auto-open]');
    if (autoDialog && !autoDialog.open) autoDialog.showModal();

    const tableWraps = [...document.querySelectorAll('.table-wrap')];
    const updateTableAffordance = (wrap) => {
        const scrollable = wrap.scrollWidth > wrap.clientWidth + 2;
        wrap.classList.toggle('is-scrollable', scrollable);
        wrap.classList.toggle('at-scroll-end', !scrollable || wrap.scrollLeft + wrap.clientWidth >= wrap.scrollWidth - 2);
        if (scrollable) wrap.setAttribute('aria-description', 'Scrollable table. Swipe or use Shift plus mouse wheel to view more columns.');
        else wrap.removeAttribute('aria-description');
    };
    tableWraps.forEach((wrap) => {
        updateTableAffordance(wrap);
        wrap.addEventListener('scroll', () => updateTableAffordance(wrap), { passive: true });
    });
    window.addEventListener('resize', () => tableWraps.forEach(updateTableAffordance));

    const enableSubmit = (submit) => {
        if (!submit) return;
        submit.disabled = false;
        submit.removeAttribute('aria-disabled');
        submit.removeAttribute('data-submit-disabled');
    };

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;
            const submit = event.submitter || form.querySelector('button[type="submit"],input[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.setAttribute('aria-disabled', 'true');
                submit.setAttribute('data-submit-disabled', '1');
            }
        });
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-submit-disabled]').forEach(enableSubmit);
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeNav();
            closeAccount();
        }
    });

    const firstInvalid = document.querySelector('[aria-invalid="true"]');
    if (firstInvalid) {
        requestAnimationFrame(() => firstInvalid.focus({ preventScroll: false }));
    }
})();
