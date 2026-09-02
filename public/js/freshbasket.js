(() => {
    'use strict';

    const config = window.FreshBasket;
    const state = {
        token: localStorage.getItem('freshbasket_token'),
        user: null,
        groceries: [],
        adminGroceries: [],
        adminMeta: null,
        adminPage: 1,
        cart: new Map(),
        authMode: 'login',
        pendingAction: null,
        adminDrawerMode: null,
        selectedAdminItem: null,
    };

    const el = (id) => document.getElementById(id);
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    })[char]);
    const money = (cents) => `৳${(Number(cents) / 100).toFixed(2)}`;
    const initials = (name) => name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();

    async function api(path, options = {}) {
        const headers = { Accept: 'application/json', ...(options.headers || {}) };
        if (options.body) headers['Content-Type'] = 'application/json';
        if (state.token) headers.Authorization = `Bearer ${state.token}`;

        const response = await fetch(`${config.apiBase}${path}`, { ...options, headers });
        const payload = response.status === 204 ? null : await response.json().catch(() => null);

        if (response.status === 401 && !path.startsWith('/auth/login') && !path.startsWith('/auth/register')) {
            clearSession();
        }
        if (!response.ok) {
            const validation = payload?.errors ? Object.values(payload.errors).flat().join(' ') : null;
            throw new Error(validation || payload?.message || 'Request failed. Please try again.');
        }

        return payload;
    }

    function showAuth() {
        el('auth-modal').classList.remove('hidden');
        setTimeout(() => el('auth-form').elements.email.focus(), 30);
    }

    function hideAuth() {
        el('auth-modal').classList.add('hidden');
    }

    function closeAuth() {
        state.pendingAction = null;
        hideAuth();
    }

    function clearSession() {
        state.token = null;
        state.user = null;
        localStorage.removeItem('freshbasket_token');
        renderAccount();
        renderWorkspace();
    }

    const isAdmin = () => state.user?.roles?.includes('admin') === true;

    function renderAccount() {
        el('account-button').textContent = state.user ? `${state.user.name} · ${config.text.logout}` : config.text.signIn;
    }

    async function bootAuthenticated() {
        const me = await api('/auth/me');
        state.user = me.data;
        renderAccount();
        renderWorkspace();
        hideAuth();
        if (isAdmin()) await loadAdminCatalog();
        else await Promise.all([loadCatalog(), loadOrders()]);
    }

    function renderWorkspace() {
        const admin = isAdmin();
        el('workspace-navigation').classList.toggle('hidden', !admin);
        el('orders-link').classList.toggle('hidden', admin);
        if (admin) activateWorkspace('admin', false);
        else {
            el('storefront').classList.remove('hidden', 'admin-storefront-preview');
            el('admin-panel').classList.add('hidden');
        }
    }

    function activateWorkspace(workspace, load = true) {
        if (!isAdmin()) return;
        const showAdmin = workspace === 'admin';
        el('admin-panel').classList.toggle('hidden', !showAdmin);
        el('storefront').classList.toggle('hidden', showAdmin);
        el('storefront').classList.toggle('admin-storefront-preview', !showAdmin);
        el('admin-link').classList.toggle('active', showAdmin);
        el('storefront-link').classList.toggle('active', !showAdmin);
        if (load && showAdmin) loadAdminCatalog(el('admin-search').value.trim());
        if (load && !showAdmin) loadCatalog(el('search').value.trim());
    }

    async function loadCatalog(search = '') {
        el('product-list').innerHTML = '<div class="loading-row">…</div>';
        try {
            const payload = await api(`/groceries?per_page=50&search=${encodeURIComponent(search)}`);
            state.groceries = payload.data;
            const availableIds = new Set(state.groceries.map((item) => item.id));
            [...state.cart.keys()].forEach((id) => { if (!availableIds.has(id)) state.cart.delete(id); });
            renderCatalog();
            renderCart();
        } catch (error) {
            el('product-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`;
        }
    }

    function renderCatalog() {
        el('product-list').innerHTML = state.groceries.length ? state.groceries.map((item) => `
            <div class="product-row" role="row">
                <div class="product-identity" role="cell">
                    <span class="product-mark" aria-hidden="true">${escapeHtml(initials(item.name))}</span>
                    <span class="product-name"><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.unit)}</small></span>
                </div>
                <span class="price" role="cell">${money(item.unit_price_cents)}</span>
                <span class="stock" role="cell">${item.stock_quantity} ${escapeHtml(config.text.inStock)}</span>
                <button class="add-button" data-action="add" data-id="${item.id}" type="button">${escapeHtml(config.text.add)}</button>
            </div>`).join('') : '<div class="empty-state">No groceries match your search.</div>';
    }

    function renderCart() {
        const entries = [...state.cart.values()];
        el('cart-items').innerHTML = entries.length ? entries.map(({ item, quantity }) => `
            <div class="cart-item">
                <div><strong>${escapeHtml(item.name)}</strong><div class="qty-control" aria-label="Quantity">
                    <button data-action="decrease" data-id="${item.id}" type="button" aria-label="Decrease">−</button>
                    <span>${quantity}</span>
                    <button data-action="increase" data-id="${item.id}" type="button" aria-label="Increase">+</button>
                </div></div>
                <span class="price">${money(item.unit_price_cents * quantity)}</span>
            </div>`).join('') : `<div class="cart-empty">${escapeHtml(config.text.emptyBasket)}</div>`;
        const total = entries.reduce((sum, row) => sum + row.item.unit_price_cents * row.quantity, 0);
        el('cart-total').textContent = money(total);
        el('checkout').disabled = entries.length === 0;
    }

    function changeQuantity(id, delta) {
        const item = state.groceries.find((row) => row.id === id);
        if (!item) return;
        const current = state.cart.get(id)?.quantity || 0;
        const quantity = Math.min(item.stock_quantity, current + delta);
        if (quantity <= 0) state.cart.delete(id);
        else state.cart.set(id, { item, quantity });
        renderCart();
    }

    async function checkout() {
        if (state.cart.size === 0) return;
        if (!state.token) {
            state.pendingAction = 'checkout';
            showAuth();

            return;
        }

        const button = el('checkout');
        button.disabled = true;
        try {
            await api('/orders', {
                method: 'POST',
                body: JSON.stringify({ items: [...state.cart.values()].map(({ item, quantity }) => ({ grocery_item_id: item.id, quantity })) }),
            });
            state.cart.clear();
            renderCart();
            toast(config.text.orderPlaced);
            await Promise.all([loadCatalog(el('search').value), loadOrders()]);
        } catch (error) {
            if (!state.token) {
                state.pendingAction = 'checkout';
                showAuth();
            }
            toast(error.message);
            button.disabled = false;
        }
    }

    async function loadOrders() {
        if (!state.token) {
            renderGuestOrders();

            return;
        }
        try {
            const payload = await api('/orders?per_page=20');
            const orders = payload.data;
            el('order-count').textContent = orders.length ? `${payload.meta.total}` : '';
            el('order-list').innerHTML = orders.length ? `
                <div class="order-row header"><span>${escapeHtml(config.text.orderId)}</span><span>${escapeHtml(config.text.date)}</span><span>${escapeHtml(config.text.items)}</span><span>${escapeHtml(config.text.total)}</span><span>${escapeHtml(config.text.status)}</span></div>
                ${orders.map((order) => `<div class="order-row"><strong>#FB-${String(order.id).padStart(5, '0')}</strong><span>${new Intl.DateTimeFormat(config.locale === 'bn' ? 'bn-BD' : 'en-BD', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(order.placed_at))}</span><span>${order.items.length}</span><span class="price">${money(order.total_amount_cents)}</span><span class="status">${escapeHtml(order.status)}</span></div>`).join('')}`
                : `<div class="empty-state">${escapeHtml(config.text.emptyOrders)}</div>`;
        } catch (error) {
            el('order-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`;
        }
    }

    function renderGuestOrders() {
        el('order-count').textContent = '';
        el('order-list').innerHTML = `<div class="empty-state">${escapeHtml(config.text.signInForOrders)}</div>`;
    }

    async function loadAdminCatalog(search = '', page = 1) {
        if (!isAdmin()) return;
        el('admin-product-list').innerHTML = '<div class="loading-row">…</div>';
        try {
            const status = el('admin-status-filter').value;
            const statusQuery = status === 'all' ? '' : `&is_active=${status === 'active' ? 1 : 0}`;
            const payload = await api(`/admin/groceries?per_page=15&page=${page}&search=${encodeURIComponent(search)}${statusQuery}`);
            state.adminGroceries = payload.data;
            state.adminMeta = payload.meta;
            state.adminPage = payload.meta.current_page;
            renderAdminCatalog();
        } catch (error) {
            el('admin-product-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`;
            el('admin-table-summary').textContent = '';
        }
    }

    function renderAdminCatalog() {
        const items = state.adminGroceries;
        el('admin-product-list').innerHTML = items.length ? items.map((item) => `
            <div class="admin-row" role="row">
                <span class="admin-product-name" role="cell"><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.description || '—')}</small></span>
                <span role="cell">${escapeHtml(item.unit)}</span>
                <span class="price" role="cell">${money(item.unit_price_cents)}</span>
                <span class="admin-stock-value" role="cell">${item.stock_quantity}</span>
                <span role="cell"><span class="admin-status ${item.is_active ? '' : 'inactive'}">${escapeHtml(item.is_active ? config.text.active : config.text.inactive)}</span></span>
                <span class="admin-actions" role="cell">
                    <button class="admin-action" data-admin-action="edit" data-id="${item.id}" type="button">${escapeHtml(config.text.edit)}</button>
                    <button class="admin-action" data-admin-action="stock" data-id="${item.id}" type="button">${escapeHtml(config.text.stock)}</button>
                    <button class="admin-action danger" data-admin-action="delete" data-id="${item.id}" type="button">${escapeHtml(config.text.delete)}</button>
                </span>
            </div>`).join('') : `<div class="empty-state">${escapeHtml(config.text.noAdminProducts)}</div>`;
        el('admin-table-summary').textContent = config.text.productsShown
            .replace(':shown', items.length)
            .replace(':total', state.adminMeta?.total ?? items.length);
        el('admin-previous-page').disabled = !state.adminMeta || state.adminMeta.current_page <= 1;
        el('admin-next-page').disabled = !state.adminMeta || state.adminMeta.current_page >= state.adminMeta.last_page;
    }

    function openAdminProductDrawer(item = null) {
        state.adminDrawerMode = item ? 'edit' : 'create';
        state.selectedAdminItem = item;
        const form = el('admin-product-form');
        form.reset();
        form.classList.remove('hidden');
        el('admin-stock-form').classList.add('hidden');
        document.querySelector('.create-stock-field').classList.toggle('hidden', Boolean(item));
        document.querySelector('.edit-stock-note').classList.toggle('hidden', !item);
        form.elements.stock_quantity.required = !item;
        el('admin-drawer-title').textContent = item ? config.text.editProduct : config.text.addProduct;
        el('admin-product-submit').textContent = item ? config.text.saveChanges : config.text.createProduct;
        el('admin-product-error').textContent = '';
        if (item) {
            form.elements.name.value = item.name;
            form.elements.description.value = item.description || '';
            form.elements.unit.value = item.unit;
            form.elements.unit_price.value = (item.unit_price_cents / 100).toFixed(2);
            form.elements.is_active.value = item.is_active ? '1' : '0';
        }
        showAdminDrawer();
        setTimeout(() => form.elements.name.focus(), 30);
    }

    function openAdminStockDrawer(item) {
        state.adminDrawerMode = 'stock';
        state.selectedAdminItem = item;
        const form = el('admin-stock-form');
        form.reset();
        form.classList.remove('hidden');
        el('admin-product-form').classList.add('hidden');
        el('admin-drawer-title').textContent = config.text.updateStock;
        el('stock-product-name').innerHTML = `<strong>${escapeHtml(item.name)}</strong><br>${item.stock_quantity} ${escapeHtml(config.text.inStock)}`;
        form.elements.stock_quantity.value = item.stock_quantity;
        el('admin-stock-error').textContent = '';
        showAdminDrawer();
        setTimeout(() => form.elements.stock_quantity.select(), 30);
    }

    function showAdminDrawer() {
        el('admin-drawer-backdrop').classList.remove('hidden');
        el('admin-drawer').classList.remove('hidden');
        document.body.classList.add('drawer-open');
    }

    function closeAdminDrawer() {
        el('admin-drawer-backdrop').classList.add('hidden');
        el('admin-drawer').classList.add('hidden');
        document.body.classList.remove('drawer-open');
        state.adminDrawerMode = null;
        state.selectedAdminItem = null;
    }

    async function submitAdminProduct(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const price = Number(form.elements.unit_price.value);
        const body = {
            name: form.elements.name.value.trim(),
            description: form.elements.description.value.trim() || null,
            unit: form.elements.unit.value.trim(),
            unit_price_cents: Math.round(price * 100),
            is_active: form.elements.is_active.value === '1',
        };
        if (state.adminDrawerMode === 'create') body.stock_quantity = Number(form.elements.stock_quantity.value);
        const editing = state.adminDrawerMode === 'edit';
        const path = editing ? `/admin/groceries/${state.selectedAdminItem.id}` : '/admin/groceries';
        const button = el('admin-product-submit');
        button.disabled = true;
        el('admin-product-error').textContent = '';
        try {
            await api(path, { method: editing ? 'PATCH' : 'POST', body: JSON.stringify(body) });
            closeAdminDrawer();
            toast(editing ? config.text.productUpdated : config.text.productCreated);
            await loadAdminCatalog(el('admin-search').value.trim(), editing ? state.adminPage : 1);
        } catch (error) {
            el('admin-product-error').textContent = error.message;
        } finally {
            button.disabled = false;
        }
    }

    async function submitAdminStock(event) {
        event.preventDefault();
        const button = el('admin-stock-submit');
        button.disabled = true;
        el('admin-stock-error').textContent = '';
        try {
            await api(`/admin/groceries/${state.selectedAdminItem.id}/stock`, {
                method: 'PATCH',
                body: JSON.stringify({ stock_quantity: Number(event.currentTarget.elements.stock_quantity.value) }),
            });
            closeAdminDrawer();
            toast(config.text.stockUpdated);
            await loadAdminCatalog(el('admin-search').value.trim(), state.adminPage);
        } catch (error) {
            el('admin-stock-error').textContent = error.message;
        } finally {
            button.disabled = false;
        }
    }

    async function handleAdminAction(action, id) {
        const item = state.adminGroceries.find((row) => row.id === id);
        if (!item) return;
        if (action === 'edit') return openAdminProductDrawer(item);
        if (action === 'stock') return openAdminStockDrawer(item);
        if (action === 'delete' && window.confirm(config.text.deleteConfirm)) {
            try {
                await api(`/admin/groceries/${id}`, { method: 'DELETE' });
                toast(config.text.productDeleted);
                const page = state.adminGroceries.length === 1 && state.adminPage > 1 ? state.adminPage - 1 : state.adminPage;
                await loadAdminCatalog(el('admin-search').value.trim(), page);
            } catch (error) {
                toast(error.message);
            }
        }
    }

    function switchAuth(mode) {
        state.authMode = mode;
        document.querySelectorAll('[data-auth-tab]').forEach((button) => button.classList.toggle('active', button.dataset.authTab === mode));
        document.querySelectorAll('.register-only').forEach((node) => node.classList.toggle('hidden', mode !== 'register'));
        el('auth-submit').textContent = mode === 'register' ? config.text.register : config.text.signIn;
        el('auth-form').elements.password.autocomplete = mode === 'register' ? 'new-password' : 'current-password';
        el('auth-error').textContent = '';
    }

    async function submitAuth(event) {
        event.preventDefault();
        const formElement = event.currentTarget;
        const form = new FormData(formElement);
        const body = Object.fromEntries(form.entries());
        if (state.authMode === 'login') {
            delete body.name;
            delete body.password_confirmation;
        }
        const button = el('auth-submit');
        button.disabled = true;
        el('auth-error').textContent = '';
        try {
            const payload = await api(`/auth/${state.authMode}`, { method: 'POST', body: JSON.stringify(body) });
            state.token = payload.data.access_token;
            state.user = payload.data.user;
            localStorage.setItem('freshbasket_token', state.token);
            formElement.reset();
            renderAccount();
            renderWorkspace();
            hideAuth();
            const pendingAction = state.pendingAction;
            state.pendingAction = null;
            if (isAdmin()) await loadAdminCatalog();
            else {
                await Promise.all([loadCatalog(), loadOrders()]);
                if (pendingAction === 'checkout') await checkout();
                if (pendingAction === 'orders') el('orders').scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            el('auth-error').textContent = error.message;
        } finally {
            button.disabled = false;
        }
    }

    async function logout() {
        if (!state.token) return showAuth();
        try { await api('/auth/logout', { method: 'POST' }); } catch (_) { /* Token is cleared locally either way. */ }
        clearSession();
        closeAdminDrawer();
        renderCart();
        renderGuestOrders();
        hideAuth();
    }

    let toastTimer;
    function toast(message) {
        const node = el('toast');
        node.textContent = message;
        node.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => node.classList.remove('show'), 3200);
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        if (button && ['add', 'increase', 'decrease'].includes(button.dataset.action)) {
            changeQuantity(Number(button.dataset.id), button.dataset.action === 'decrease' ? -1 : 1);
        }
        if (button?.dataset.action === 'close-admin-drawer') closeAdminDrawer();
        const adminButton = event.target.closest('[data-admin-action]');
        if (adminButton) handleAdminAction(adminButton.dataset.adminAction, Number(adminButton.dataset.id));
    });
    document.querySelectorAll('[data-auth-tab]').forEach((button) => button.addEventListener('click', () => switchAuth(button.dataset.authTab)));
    el('auth-form').addEventListener('submit', submitAuth);
    el('account-button').addEventListener('click', logout);
    el('modal-close').addEventListener('click', closeAuth);
    el('orders-link').addEventListener('click', () => {
        if (!state.token) {
            state.pendingAction = 'orders';
            showAuth();

            return;
        }
        el('orders').scrollIntoView({ behavior: 'smooth' });
    });
    el('checkout').addEventListener('click', checkout);
    el('admin-link').addEventListener('click', () => activateWorkspace('admin'));
    el('storefront-link').addEventListener('click', () => activateWorkspace('storefront'));
    el('admin-add-product').addEventListener('click', () => openAdminProductDrawer());
    el('admin-drawer-close').addEventListener('click', closeAdminDrawer);
    el('admin-drawer-backdrop').addEventListener('click', closeAdminDrawer);
    el('admin-product-form').addEventListener('submit', submitAdminProduct);
    el('admin-stock-form').addEventListener('submit', submitAdminStock);
    el('admin-status-filter').addEventListener('change', () => loadAdminCatalog(el('admin-search').value.trim(), 1));
    el('admin-previous-page').addEventListener('click', () => loadAdminCatalog(el('admin-search').value.trim(), state.adminPage - 1));
    el('admin-next-page').addEventListener('click', () => loadAdminCatalog(el('admin-search').value.trim(), state.adminPage + 1));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !el('admin-drawer').classList.contains('hidden')) closeAdminDrawer();
    });
    let searchTimer;
    el('search').addEventListener('input', (event) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadCatalog(event.target.value.trim()), 250);
    });
    let adminSearchTimer;
    el('admin-search').addEventListener('input', (event) => {
        clearTimeout(adminSearchTimer);
        adminSearchTimer = setTimeout(() => loadAdminCatalog(event.target.value.trim(), 1), 250);
    });

    renderCart();
    if (state.token) {
        bootAuthenticated().catch(() => {
            clearSession();
            hideAuth();
            loadCatalog();
            renderGuestOrders();
        });
    } else {
        loadCatalog();
        renderGuestOrders();
    }
})();
