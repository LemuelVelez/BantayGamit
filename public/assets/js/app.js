(() => {
    const body = document.body;
    const navToggle = document.querySelector('[data-nav-toggle]');

    const closeNav = () => {
        body.classList.remove('nav-open');
        navToggle?.setAttribute('aria-expanded', 'false');
    };

    navToggle?.addEventListener('click', () => {
        const open = !body.classList.contains('nav-open');
        body.classList.toggle('nav-open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.querySelectorAll('[data-nav-close]').forEach((element) => {
        element.addEventListener('click', closeNav);
    });

    document.querySelectorAll('.sidebar a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 900px)').matches) closeNav();
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) closeNav();
    });

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

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            pendingForm = form;
            if (confirmMessage) confirmMessage.textContent = form.dataset.confirm || 'Are you sure?';
            confirmDialog?.showModal();
        });
    });

    confirmCancel?.addEventListener('click', () => {
        confirmDialog?.close();
        pendingForm = null;
    });

    confirmProceed?.addEventListener('click', () => {
        if (!pendingForm) return;

        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = '1';
        confirmDialog?.close();
        form.requestSubmit();
    });

    confirmDialog?.addEventListener('cancel', () => {
        pendingForm = null;
    });

    const requestItems = document.querySelector('[data-items]');
    const itemTemplate = document.querySelector('[data-item-template]');

    document.querySelector('[data-add-item]')?.addEventListener('click', () => {
        if (requestItems && itemTemplate) requestItems.append(itemTemplate.content.cloneNode(true));
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
        button.addEventListener('click', () => document.getElementById(button.dataset.dialogOpen)?.showModal());
    });

    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelectorAll('dialog.form-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;

            const submit = event.submitter || form.querySelector('button[type="submit"],input[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.setAttribute('aria-disabled', 'true');
            }
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeNav();
            closeAccount();
        }
    });
})();
