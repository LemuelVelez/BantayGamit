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

    let customSelectSequence = 0;
    const customSelects = new Set();

    const closeCustomSelect = (wrapper, restoreFocus = false) => {
        if (!wrapper) return;
        const trigger = wrapper.querySelector('.custom-select-trigger');
        const menu = wrapper.querySelector('.custom-select-menu');
        wrapper.classList.remove('is-open');
        menu?.setAttribute('hidden', '');
        trigger?.setAttribute('aria-expanded', 'false');
        if (restoreFocus) trigger?.focus({ preventScroll: true });
    };

    const closeOtherCustomSelects = (except = null) => {
        customSelects.forEach((wrapper) => {
            if (wrapper !== except) closeCustomSelect(wrapper);
        });
    };

    const getSelectFieldLabel = (select) => {
        const explicit = select.getAttribute('aria-label');
        if (explicit) return explicit;
        const label = select.closest('label');
        if (!label) return select.name || 'Select option';
        const text = [...label.childNodes]
            .filter((node) => node.nodeType === Node.TEXT_NODE)
            .map((node) => node.textContent.trim())
            .filter(Boolean)
            .join(' ');
        return text || select.name || 'Select option';
    };

    const enhanceSelect = (select) => {
        if (!(select instanceof HTMLSelectElement) || select.multiple || select.size > 1 || select.dataset.customSelect === 'ready') return null;

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select';
        const menuId = `custom-select-menu-${++customSelectSequence}`;
        const fieldLabel = getSelectFieldLabel(select);

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('custom-select-native');
        select.dataset.customSelect = 'ready';
        select.tabIndex = -1;

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', menuId);

        const value = document.createElement('span');
        value.className = 'custom-select-value';
        const chevron = document.createElement('span');
        chevron.className = 'custom-select-chevron';
        chevron.setAttribute('aria-hidden', 'true');
        trigger.append(value, chevron);

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.id = menuId;
        menu.setAttribute('role', 'listbox');
        menu.setAttribute('hidden', '');
        wrapper.append(trigger, menu);
        customSelects.add(wrapper);

        const optionButtons = [];
        [...select.options].forEach((option, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'custom-select-option';
            button.setAttribute('role', 'option');
            button.dataset.optionIndex = String(index);
            button.textContent = option.textContent;
            button.disabled = option.disabled;
            button.hidden = option.hidden;
            menu.appendChild(button);
            optionButtons.push(button);

            button.addEventListener('click', () => {
                if (button.disabled) return;
                select.selectedIndex = index;
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeCustomSelect(wrapper, true);
            });
        });

        const sync = () => {
            const selectedIndex = select.selectedIndex >= 0 ? select.selectedIndex : 0;
            const selected = select.options[selectedIndex];
            value.textContent = selected?.textContent || 'Select an option';
            trigger.disabled = select.disabled;
            trigger.setAttribute('aria-label', `${fieldLabel}: ${value.textContent}`);
            wrapper.classList.toggle('is-disabled', select.disabled);
            wrapper.classList.toggle('is-invalid', select.getAttribute('aria-invalid') === 'true');
            optionButtons.forEach((button, index) => {
                button.setAttribute('aria-selected', index === select.selectedIndex ? 'true' : 'false');
            });
        };

        const open = (focusSelected = false) => {
            if (select.disabled) return;
            closeOtherCustomSelects(wrapper);
            wrapper.classList.add('is-open');
            menu.removeAttribute('hidden');
            trigger.setAttribute('aria-expanded', 'true');
            if (focusSelected) {
                requestAnimationFrame(() => {
                    const selectedButton = optionButtons[select.selectedIndex] || optionButtons.find((button) => !button.disabled && !button.hidden);
                    selectedButton?.focus({ preventScroll: true });
                    selectedButton?.scrollIntoView({ block: 'nearest' });
                });
            }
        };

        trigger.addEventListener('click', () => {
            if (wrapper.classList.contains('is-open')) closeCustomSelect(wrapper);
            else open(false);
        });

        trigger.addEventListener('keydown', (event) => {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                open(true);
            }
        });

        menu.addEventListener('keydown', (event) => {
            const enabled = optionButtons.filter((button) => !button.disabled && !button.hidden);
            if (!enabled.length) return;
            const current = enabled.indexOf(document.activeElement);
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                const next = current < 0 ? 0 : (current + direction + enabled.length) % enabled.length;
                enabled[next].focus({ preventScroll: true });
                enabled[next].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                const target = event.key === 'Home' ? enabled[0] : enabled[enabled.length - 1];
                target.focus({ preventScroll: true });
                target.scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Escape' || event.key === 'Tab') {
                closeCustomSelect(wrapper, event.key === 'Escape');
            }
        });

        select.addEventListener('change', sync);
        select.addEventListener('invalid', () => {
            wrapper.classList.add('is-invalid');
            requestAnimationFrame(() => trigger.focus({ preventScroll: false }));
        });
        select.form?.addEventListener('reset', () => requestAnimationFrame(sync));
        sync();
        return wrapper;
    };

    document.querySelectorAll('select').forEach(enhanceSelect);

    document.addEventListener('click', (event) => {
        customSelects.forEach((wrapper) => {
            if (!wrapper.contains(event.target)) closeCustomSelect(wrapper);
        });
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
        const customSelect = select ? enhanceSelect(select) : null;
        customSelect?.querySelector('.custom-select-trigger')?.focus({ preventScroll: true });
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
            closeOtherCustomSelects();
        }
    });

    const firstInvalid = document.querySelector('[aria-invalid="true"]');
    if (firstInvalid) {
        requestAnimationFrame(() => firstInvalid.focus({ preventScroll: false }));
    }
})();
