(() => {
    'use strict';

    const config = window.FreshBasket;
    const state = {
        token: localStorage.getItem('freshbasket_token'), user: null, groceries: [], cart: new Map(),
        authMode: 'login', pendingAction: null, adminSection: 'overview',
        adminGroceries: [], adminProductMeta: null, adminProductPage: 1,
        adminOrders: [], adminOrderMeta: null, adminOrderPage: 1,
        adminUsers: [], adminUserMeta: null, adminUserPage: 1,
        roles: [], permissions: [], selectedRole: null, drawerMode: null, selectedAdminItem: null,
    };

    const el = (id) => document.getElementById(id);
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);
    const money = (cents) => `৳${(Number(cents) / 100).toFixed(2)}`;
    const initials = (name) => name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
    const humanize = (value) => String(value).replace(/[.-]/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
    const formatDate = (value, withTime = false) => new Intl.DateTimeFormat(config.locale === 'bn' ? 'bn-BD' : 'en-BD', withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' }).format(new Date(value));
    const statusText = (status) => config.text[status] || humanize(status);

    async function api(path, options = {}) {
        const headers = { Accept: 'application/json', ...(options.headers || {}) };
        if (options.body) headers['Content-Type'] = 'application/json';
        if (state.token) headers.Authorization = `Bearer ${state.token}`;
        const response = await fetch(`${config.apiBase}${path}`, { ...options, headers });
        const payload = response.status === 204 ? null : await response.json().catch(() => null);
        if (response.status === 401 && !path.startsWith('/auth/login') && !path.startsWith('/auth/register')) clearSession();
        if (!response.ok) {
            const validation = payload?.errors ? Object.values(payload.errors).flat().join(' ') : null;
            throw new Error(validation || payload?.message || 'Request failed. Please try again.');
        }
        return payload;
    }

    const hasPermission = (permission) => state.user?.permissions?.includes(permission) === true;
    const canUseAdmin = () => state.user?.permissions?.some((permission) => ['dashboard.view', 'groceries.view', 'orders.view-all', 'users.view', 'roles.view'].includes(permission)) === true;

    function showAuth() { el('auth-modal').classList.remove('hidden'); setTimeout(() => el('auth-form').elements.email.focus(), 30); }
    function hideAuth() { el('auth-modal').classList.add('hidden'); }
    function closeAuth() { state.pendingAction = null; hideAuth(); }

    function clearSession() {
        state.token = null; state.user = null; localStorage.removeItem('freshbasket_token');
        closeAdminDrawer(); closeAdminSidebar(); renderAccount(); renderWorkspace();
    }

    function renderAccount() { el('account-button').textContent = state.user ? `${state.user.name} · ${config.text.logout}` : config.text.signIn; }

    async function bootAuthenticated() {
        const me = await api('/auth/me'); state.user = me.data; renderAccount(); renderWorkspace(); hideAuth();
        if (!canUseAdmin()) await Promise.all([loadCatalog(), loadOrders()]);
    }

    function renderWorkspace() {
        const admin = canUseAdmin();
        el('workspace-navigation').classList.toggle('hidden', !admin); el('orders-link').classList.toggle('hidden', admin);
        if (admin) {
            el('site-header').classList.add('hidden'); el('storefront').classList.add('hidden'); el('admin-panel').classList.remove('hidden'); el('admin-user-name').textContent = state.user.name;
            document.querySelectorAll('[data-permission]').forEach((item) => item.classList.toggle('hidden', !hasPermission(item.dataset.permission)));
            const preferred = document.querySelector(`[data-admin-section="${state.adminSection}"]:not(.hidden)`)?.dataset.adminSection || document.querySelector('.admin-nav-item[data-admin-section]:not(.hidden)')?.dataset.adminSection;
            if (preferred) activateAdminSection(preferred);
        } else {
            el('site-header').classList.remove('hidden'); el('admin-panel').classList.add('hidden'); el('storefront').classList.remove('hidden', 'admin-storefront-preview');
        }
    }

    function showStorefrontPreview() {
        closeAdminSidebar(); el('admin-panel').classList.add('hidden'); el('site-header').classList.remove('hidden'); el('storefront').classList.remove('hidden'); el('storefront').classList.add('admin-storefront-preview'); el('admin-link').classList.remove('active'); el('storefront-link').classList.add('active'); loadCatalog(el('search').value.trim());
    }

    function showAdminWorkspace() {
        if (!canUseAdmin()) return;
        el('site-header').classList.add('hidden'); el('storefront').classList.add('hidden'); el('admin-panel').classList.remove('hidden'); activateAdminSection(state.adminSection);
    }

    function activateAdminSection(section) {
        const navigation = document.querySelector(`[data-admin-section="${section}"][data-permission]`);
        if (navigation?.classList.contains('hidden')) return;
        state.adminSection = section;
        document.querySelectorAll('[data-admin-view]').forEach((view) => view.classList.toggle('hidden', view.dataset.adminView !== section));
        document.querySelectorAll('.admin-nav-item[data-admin-section]').forEach((item) => item.classList.toggle('active', item.dataset.adminSection === section));
        closeAdminSidebar();
        if (section === 'overview') loadDashboard();
        if (section === 'products') loadAdminCatalog(el('admin-search').value.trim(), state.adminProductPage);
        if (section === 'admin-orders') loadAdminOrders(state.adminOrderPage);
        if (section === 'users') loadAdminUsers(state.adminUserPage);
        if (section === 'roles') loadRoles(true);
    }

    function openAdminSidebar() { document.body.classList.add('sidebar-open'); el('admin-sidebar-backdrop').classList.remove('hidden'); }
    function closeAdminSidebar() { document.body.classList.remove('sidebar-open'); el('admin-sidebar-backdrop').classList.add('hidden'); }

    async function loadCatalog(search = '') {
        el('product-list').innerHTML = '<div class="loading-row">…</div>';
        try {
            const payload = await api(`/groceries?per_page=50&search=${encodeURIComponent(search)}`); state.groceries = payload.data;
            const availableIds = new Set(state.groceries.map((item) => item.id)); [...state.cart.keys()].forEach((id) => { if (!availableIds.has(id)) state.cart.delete(id); });
            renderCatalog(); renderCart();
        } catch (error) { el('product-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`; }
    }

    function renderCatalog() {
        state.groceries.forEach((item) => { if (state.cart.has(item.id)) state.cart.get(item.id).item = item; });
        el('product-list').innerHTML = state.groceries.length ? state.groceries.map((item) => `<div class="product-row" role="row"><div class="product-identity" role="cell"><span class="product-mark">${escapeHtml(initials(item.name))}</span><span class="product-name"><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.unit)}</small></span></div><span class="price" role="cell">${money(item.unit_price_cents)}</span><span class="stock" role="cell">${item.stock_quantity} ${escapeHtml(config.text.inStock)}</span><button class="add-button" data-action="add" data-id="${item.id}" type="button">${escapeHtml(config.text.add)}</button></div>`).join('') : '<div class="empty-state">No groceries match your search.</div>';
    }

    function renderCart() {
        const entries = [...state.cart.values()];
        el('cart-items').innerHTML = entries.length ? entries.map(({ item, quantity }) => `<div class="cart-item"><div><strong>${escapeHtml(item.name)}</strong><div class="qty-control"><button data-action="decrease" data-id="${item.id}" type="button">−</button><span>${quantity}</span><button data-action="increase" data-id="${item.id}" type="button">+</button></div></div><span class="price">${money(item.unit_price_cents * quantity)}</span></div>`).join('') : `<div class="cart-empty">${escapeHtml(config.text.emptyBasket)}</div>`;
        el('cart-total').textContent = money(entries.reduce((sum, row) => sum + row.item.unit_price_cents * row.quantity, 0)); el('checkout').disabled = entries.length === 0;
    }

    function changeQuantity(id, delta) {
        const item = state.groceries.find((row) => row.id === id); if (!item) return;
        const quantity = Math.min(item.stock_quantity, (state.cart.get(id)?.quantity || 0) + delta);
        if (quantity <= 0) state.cart.delete(id); else state.cart.set(id, { item, quantity }); renderCart();
    }

    async function checkout() {
        if (state.cart.size === 0) return;
        if (!state.token) { state.pendingAction = 'checkout'; showAuth(); return; }
        const button = el('checkout'); button.disabled = true;
        try {
            await api('/orders', { method: 'POST', body: JSON.stringify({ items: [...state.cart.values()].map(({ item, quantity }) => ({ grocery_item_id: item.id, quantity })) }) });
            state.cart.clear(); renderCart(); toast(config.text.orderPlaced); await Promise.all([loadCatalog(el('search').value), loadOrders()]);
        } catch (error) { if (!state.token) { state.pendingAction = 'checkout'; showAuth(); } toast(error.message); button.disabled = false; }
    }

    async function loadOrders() {
        if (!state.token) { renderGuestOrders(); return; }
        try {
            const payload = await api('/orders?per_page=20'); const orders = payload.data; el('order-count').textContent = orders.length ? `${payload.meta.total}` : '';
            el('order-list').innerHTML = orders.length ? `<div class="order-row header"><span>${config.text.orderId}</span><span>${config.text.date}</span><span>${config.text.items}</span><span>${config.text.total}</span><span>${config.text.status}</span></div>${orders.map((order) => `<div class="order-row"><strong>#FB-${String(order.id).padStart(5, '0')}</strong><span>${formatDate(order.placed_at, true)}</span><span>${order.items.length}</span><span class="price">${money(order.total_amount_cents)}</span><span class="status">${escapeHtml(statusText(order.status))}</span></div>`).join('')}` : `<div class="empty-state">${config.text.emptyOrders}</div>`;
        } catch (error) { el('order-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`; }
    }

    function renderGuestOrders() { el('order-count').textContent = ''; el('order-list').innerHTML = `<div class="empty-state">${escapeHtml(config.text.signInForOrders)}</div>`; }

    async function loadDashboard() {
        try {
            const { data } = await api('/admin/dashboard');
            el('admin-metrics').innerHTML = [[config.text.products, data.metrics.products], [config.text.lowStock, data.metrics.low_stock], [config.text.orders, data.metrics.orders], [config.text.users, data.metrics.users]].map(([label, value]) => `<div class="admin-metric"><div><span>${escapeHtml(label)}</span><strong>${value}</strong></div></div>`).join('');
            const manageOrder = hasPermission('orders.view-all') ? `<button class="text-link" data-admin-section="admin-orders" type="button">${escapeHtml(config.text.manage)}</button>` : '';
            el('overview-orders').innerHTML = data.recent_orders.length ? data.recent_orders.map((order) => `<div class="overview-order-row"><strong>#FB-${String(order.id).padStart(5, '0')}</strong><span>${escapeHtml(order.customer.name)}</span><span>${formatDate(order.placed_at, true)}</span><span class="price">${money(order.total_amount_cents)}</span><span class="status-label ${order.status}">${escapeHtml(statusText(order.status))}</span>${manageOrder}</div>`).join('') : `<div class="empty-state">${escapeHtml(config.text.noAdminOrders)}</div>`;
            el('stock-attention-list').innerHTML = data.stock_attention.length ? data.stock_attention.map((item) => `<div class="stock-attention-row"><span>${escapeHtml(item.name)}</span><span>${item.stock_quantity} ${escapeHtml(config.text.inStock)}</span></div>`).join('') : `<div class="empty-state">${escapeHtml(config.text.noAdminProducts)}</div>`;
        } catch (error) { toast(error.message); }
    }

    async function loadAdminCatalog(search = '', page = 1) {
        el('admin-product-list').innerHTML = '<div class="loading-row">…</div>';
        try {
            const status = el('admin-status-filter').value; const statusQuery = status === 'all' ? '' : `&is_active=${status === 'active' ? 1 : 0}`;
            const payload = await api(`/admin/groceries?per_page=15&page=${page}&search=${encodeURIComponent(search)}${statusQuery}`);
            state.adminGroceries = payload.data; state.adminProductMeta = payload.meta; state.adminProductPage = payload.meta.current_page; renderAdminCatalog();
        } catch (error) { el('admin-product-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`; }
    }

    function renderAdminCatalog() {
        const actions = (item) => [
            hasPermission('groceries.update') ? `<button class="admin-action" data-admin-action="edit" data-id="${item.id}" type="button">${config.text.edit}</button>` : '',
            hasPermission('inventory.update') ? `<button class="admin-action" data-admin-action="stock" data-id="${item.id}" type="button">${config.text.stock}</button>` : '',
            hasPermission('groceries.delete') ? `<button class="admin-action danger" data-admin-action="delete" data-id="${item.id}" type="button">${config.text.delete}</button>` : '',
        ].join('');
        el('admin-product-list').innerHTML = state.adminGroceries.length ? state.adminGroceries.map((item) => `<div class="admin-row"><span class="admin-product-name"><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.description || '—')}</small></span><span>${escapeHtml(item.unit)}</span><span class="price">${money(item.unit_price_cents)}</span><span class="admin-stock-value">${item.stock_quantity}</span><span><span class="admin-status ${item.is_active ? '' : 'inactive'}">${escapeHtml(item.is_active ? config.text.active : config.text.inactive)}</span></span><span class="admin-actions">${actions(item)}</span></div>`).join('') : `<div class="empty-state">${config.text.noAdminProducts}</div>`;
        el('admin-table-summary').textContent = config.text.productsShown.replace(':shown', state.adminGroceries.length).replace(':total', state.adminProductMeta?.total ?? 0); el('admin-previous-page').disabled = !state.adminProductMeta || state.adminProductPage <= 1; el('admin-next-page').disabled = !state.adminProductMeta || state.adminProductPage >= state.adminProductMeta.last_page;
    }

    async function loadAdminOrders(page = 1) {
        el('admin-order-list').innerHTML = '<div class="loading-row">…</div>';
        try {
            const query = new URLSearchParams({ per_page: 15, page, search: el('admin-order-search').value.trim(), status: el('admin-order-status').value, date_range: el('admin-order-date').value }); [...query].forEach(([key, value]) => { if (!value) query.delete(key); });
            const payload = await api(`/admin/orders?${query}`); state.adminOrders = payload.data; state.adminOrderMeta = payload.meta; state.adminOrderPage = payload.meta.current_page; renderAdminOrders();
        } catch (error) { el('admin-order-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`; }
    }

    function renderAdminOrders() {
        el('admin-order-list').innerHTML = state.adminOrders.length ? state.adminOrders.map((order) => `<div class="data-row"><strong>#FB-${String(order.id).padStart(5, '0')}</strong><span class="data-primary"><strong>${escapeHtml(order.customer.name)}</strong><small>${escapeHtml(order.customer.email)}</small></span><span>${order.items.length}</span><span>${formatDate(order.placed_at, true)}</span><span class="price">${money(order.total_amount_cents)}</span><span><span class="status-label ${order.status}">${escapeHtml(statusText(order.status))}</span></span><span><button class="admin-action" data-order-id="${order.id}" type="button">${config.text.manage}</button></span></div>`).join('') : `<div class="empty-state">${config.text.noAdminOrders}</div>`;
        el('admin-order-summary').textContent = config.text.ordersShown.replace(':shown', state.adminOrders.length).replace(':total', state.adminOrderMeta?.total ?? 0); el('admin-order-previous').disabled = !state.adminOrderMeta || state.adminOrderPage <= 1; el('admin-order-next').disabled = !state.adminOrderMeta || state.adminOrderPage >= state.adminOrderMeta.last_page;
    }

    async function loadRoleOptions() {
        if (state.roles.length) return;
        const { data } = await api('/admin/users/role-options'); state.roles = data; renderRoleFilter();
    }

    async function loadAdminUsers(page = 1) {
        el('admin-user-list').innerHTML = '<div class="loading-row">…</div>';
        try {
            await loadRoleOptions(); const query = new URLSearchParams({ per_page: 15, page, search: el('admin-user-search').value.trim(), role: el('admin-user-role').value, is_active: el('admin-user-status').value }); [...query].forEach(([key, value]) => { if (value === '') query.delete(key); });
            const payload = await api(`/admin/users?${query}`); state.adminUsers = payload.data; state.adminUserMeta = payload.meta; state.adminUserPage = payload.meta.current_page; renderAdminUsers();
        } catch (error) { el('admin-user-list').innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`; }
    }

    function renderRoleFilter() {
        const select = el('admin-user-role'); const selected = select.value;
        select.innerHTML = `<option value="">${escapeHtml(config.text.allRoles || 'All roles')}</option>${state.roles.map((role) => `<option value="${escapeHtml(role.name)}">${escapeHtml(humanize(role.name))}</option>`).join('')}`; select.value = selected;
    }

    function renderAdminUsers() {
        el('admin-user-list').innerHTML = state.adminUsers.length ? state.adminUsers.map((user) => `<div class="data-row"><span class="data-primary"><strong>${escapeHtml(user.name)}</strong><small>${escapeHtml(user.email)}</small></span><span>${escapeHtml(user.roles.map(humanize).join(', '))}</span><span>${user.orders_count}</span><span><span class="status-label ${user.is_active ? '' : 'inactive'}">${escapeHtml(user.is_active ? config.text.active : config.text.inactive)}</span></span><span>${formatDate(user.created_at)}</span><span><button class="admin-action" data-user-id="${user.id}" type="button">${config.text.manage}</button></span></div>`).join('') : `<div class="empty-state">${config.text.noUsers}</div>`;
        el('admin-user-summary').textContent = config.text.usersShown.replace(':shown', state.adminUsers.length).replace(':total', state.adminUserMeta?.total ?? 0); el('admin-user-previous').disabled = !state.adminUserMeta || state.adminUserPage <= 1; el('admin-user-next').disabled = !state.adminUserMeta || state.adminUserPage >= state.adminUserMeta.last_page;
    }

    async function loadRoles(selectFirst = false) {
        try {
            const { data } = await api('/admin/roles'); state.roles = data.roles; state.permissions = data.permissions; renderRoleFilter(); renderRoles();
            if (selectFirst && !state.selectedRole) selectRole(state.roles[0] || null); else if (state.selectedRole) selectRole(state.roles.find((role) => role.id === state.selectedRole.id) || state.roles[0] || null);
        } catch (error) { toast(error.message); }
    }

    function renderRoles() { el('admin-role-list').innerHTML = state.roles.map((role) => `<button class="role-list-row ${state.selectedRole?.id === role.id ? 'active' : ''}" data-role-id="${role.id}" type="button"><strong>${escapeHtml(humanize(role.name))}</strong><span>${escapeHtml(role.is_system ? config.text.system : config.text.custom)}</span><span>${role.users_count}</span><span>${role.permissions.length}</span></button>`).join(''); }

    function selectRole(role) {
        state.selectedRole = role; renderRoles(); const form = el('admin-role-form'); form.reset(); el('role-editor-title').textContent = role ? humanize(role.name) : config.text.selectRole; form.elements.name.value = role?.name || '';
        const locked = role?.is_system === true; form.elements.name.disabled = locked; el('admin-save-role').classList.toggle('hidden', locked || !hasPermission('roles.manage')); el('admin-delete-role').classList.toggle('hidden', locked || !role || !hasPermission('roles.manage')); el('role-system-note').classList.toggle('hidden', !locked); el('permission-groups').innerHTML = renderPermissionGroups(role?.permissions || [], locked || !hasPermission('roles.manage')); el('admin-role-error').textContent = '';
    }

    function renderPermissionGroups(selected, disabled) {
        const groups = state.permissions.reduce((result, permission) => {
            (result[permission.group] ||= []).push(permission);
            return result;
        }, {});
        return Object.entries(groups).map(([group, permissions]) => `<div class="permission-group"><h3>${escapeHtml(config.text.permissionGroups[group] || humanize(group))}</h3>${permissions.map((permission) => `<label class="permission-option"><input name="permissions" type="checkbox" value="${escapeHtml(permission.name)}" ${selected.includes(permission.name) ? 'checked' : ''} ${disabled ? 'disabled' : ''}><span>${escapeHtml(permissionLabel(permission.name))}</span></label>`).join('')}</div>`).join('');
    }

    function permissionLabel(permission) {
        const [group, action] = permission.split('.'); const labels = { view: 'View', create: 'Create', update: 'Update', delete: 'Delete', manage: 'Manage', 'view-all': 'View all', 'view-own': 'View own' };
        return `${labels[action] || humanize(action)} ${humanize(group)}`;
    }

    function showDrawerForm(formId, title) {
        ['admin-product-form', 'admin-stock-form', 'admin-user-form', 'admin-order-form'].forEach((id) => el(id).classList.toggle('hidden', id !== formId)); el('admin-drawer-title').textContent = title; el('admin-drawer-backdrop').classList.remove('hidden'); el('admin-drawer').classList.remove('hidden'); document.body.classList.add('drawer-open'); return el(formId);
    }

    function closeAdminDrawer() { el('admin-drawer-backdrop').classList.add('hidden'); el('admin-drawer').classList.add('hidden'); document.body.classList.remove('drawer-open'); state.drawerMode = null; state.selectedAdminItem = null; }

    function openAdminProductDrawer(item = null) {
        state.drawerMode = item ? 'edit-product' : 'create-product'; state.selectedAdminItem = item; const form = showDrawerForm('admin-product-form', item ? config.text.editProduct : config.text.addProduct); form.reset(); document.querySelector('.create-stock-field').classList.toggle('hidden', Boolean(item)); document.querySelector('.edit-stock-note').classList.toggle('hidden', !item); form.elements.stock_quantity.required = !item; el('admin-product-submit').textContent = item ? config.text.saveChanges : config.text.createProduct; el('admin-product-error').textContent = '';
        if (item) { form.elements.name.value = item.name; form.elements.description.value = item.description || ''; form.elements.unit.value = item.unit; form.elements.unit_price.value = (item.unit_price_cents / 100).toFixed(2); form.elements.is_active.value = item.is_active ? '1' : '0'; } setTimeout(() => form.elements.name.focus(), 30);
    }

    function openAdminStockDrawer(item) {
        state.drawerMode = 'stock'; state.selectedAdminItem = item; const form = showDrawerForm('admin-stock-form', config.text.updateStock); form.reset(); el('stock-product-name').innerHTML = `<strong>${escapeHtml(item.name)}</strong><br>${item.stock_quantity} ${escapeHtml(config.text.inStock)}`; form.elements.stock_quantity.value = item.stock_quantity; el('admin-stock-error').textContent = ''; setTimeout(() => form.elements.stock_quantity.select(), 30);
    }

    async function openAdminUserDrawer(user = null) {
        await loadRoleOptions(); state.drawerMode = user ? 'edit-user' : 'create-user'; state.selectedAdminItem = user; const form = showDrawerForm('admin-user-form', user ? config.text.manageUser : config.text.addUser); const editable = hasPermission('users.manage'); form.reset(); document.querySelectorAll('.admin-user-password-field').forEach((field) => { field.classList.toggle('hidden', Boolean(user) && !editable); field.querySelector('input').required = !user; }); form.elements.name.value = user?.name || ''; form.elements.email.value = user?.email || ''; form.elements.is_active.value = user?.is_active === false ? '0' : '1'; el('admin-user-role-options').innerHTML = state.roles.map((role) => `<label class="role-choice"><input name="roles" type="checkbox" value="${escapeHtml(role.name)}" ${user?.roles.includes(role.name) ? 'checked' : ''} ${editable ? '' : 'disabled'}><span>${escapeHtml(humanize(role.name))}</span></label>`).join(''); [...form.elements].forEach((control) => { if (control.name !== 'roles') control.disabled = !editable; }); el('admin-user-submit').classList.toggle('hidden', !editable); el('admin-user-submit').textContent = user ? config.text.saveChanges : config.text.createUser; el('admin-user-error').textContent = ''; if (editable) setTimeout(() => form.elements.name.focus(), 30);
    }

    function openAdminOrderDrawer(order) {
        state.drawerMode = 'order'; state.selectedAdminItem = order; const form = showDrawerForm('admin-order-form', `${config.text.orderId} #FB-${String(order.id).padStart(5, '0')}`); el('admin-order-meta').innerHTML = `<div><span>${escapeHtml(config.text.customer)}</span><p><strong>${escapeHtml(order.customer.name)}</strong><br>${escapeHtml(order.customer.email)}</p></div><div><span>${config.text.date}</span><p>${formatDate(order.placed_at, true)}</p></div>`; el('admin-order-items').innerHTML = order.items.map((item) => `<div class="order-detail-item"><span>${escapeHtml(item.name)} × ${item.quantity}</span><strong>${money(item.subtotal_cents)}</strong></div>`).join(''); el('admin-order-total').textContent = money(order.total_amount_cents); const statuses = [order.status, ...order.allowed_statuses]; form.elements.status.innerHTML = statuses.map((status) => `<option value="${status}">${escapeHtml(statusText(status))}</option>`).join(''); el('admin-order-submit').classList.toggle('hidden', !hasPermission('orders.update')); el('admin-order-submit').disabled = order.allowed_statuses.length === 0; form.elements.status.disabled = !hasPermission('orders.update') || order.allowed_statuses.length === 0; el('admin-order-error').textContent = '';
    }

    async function submitAdminProduct(event) {
        event.preventDefault(); const form = event.currentTarget; const editing = state.drawerMode === 'edit-product'; const body = { name: form.elements.name.value.trim(), description: form.elements.description.value.trim() || null, unit: form.elements.unit.value.trim(), unit_price_cents: Math.round(Number(form.elements.unit_price.value) * 100), is_active: form.elements.is_active.value === '1' }; if (!editing) body.stock_quantity = Number(form.elements.stock_quantity.value); const button = el('admin-product-submit'); button.disabled = true; el('admin-product-error').textContent = '';
        try { await api(editing ? `/admin/groceries/${state.selectedAdminItem.id}` : '/admin/groceries', { method: editing ? 'PATCH' : 'POST', body: JSON.stringify(body) }); closeAdminDrawer(); toast(editing ? config.text.productUpdated : config.text.productCreated); await loadAdminCatalog(el('admin-search').value.trim(), editing ? state.adminProductPage : 1); } catch (error) { el('admin-product-error').textContent = error.message; } finally { button.disabled = false; }
    }

    async function submitAdminStock(event) {
        event.preventDefault(); const button = el('admin-stock-submit'); button.disabled = true; el('admin-stock-error').textContent = '';
        try { await api(`/admin/groceries/${state.selectedAdminItem.id}/stock`, { method: 'PATCH', body: JSON.stringify({ stock_quantity: Number(event.currentTarget.elements.stock_quantity.value) }) }); closeAdminDrawer(); toast(config.text.stockUpdated); await loadAdminCatalog(el('admin-search').value.trim(), state.adminProductPage); } catch (error) { el('admin-stock-error').textContent = error.message; } finally { button.disabled = false; }
    }

    async function submitAdminUser(event) {
        event.preventDefault(); const form = event.currentTarget; const editing = state.drawerMode === 'edit-user'; const roles = [...form.querySelectorAll('[name="roles"]:checked')].map((input) => input.value); const body = { name: form.elements.name.value.trim(), email: form.elements.email.value.trim(), password: form.elements.password.value || null, password_confirmation: form.elements.password_confirmation.value || null, is_active: form.elements.is_active.value === '1', roles }; const button = el('admin-user-submit'); button.disabled = true; el('admin-user-error').textContent = '';
        try { await api(editing ? `/admin/users/${state.selectedAdminItem.id}` : '/admin/users', { method: editing ? 'PATCH' : 'POST', body: JSON.stringify(body) }); closeAdminDrawer(); toast(editing ? config.text.userUpdated : config.text.userCreated); await loadAdminUsers(editing ? state.adminUserPage : 1); } catch (error) { el('admin-user-error').textContent = error.message; } finally { button.disabled = false; }
    }

    async function submitAdminOrder(event) {
        event.preventDefault(); const button = el('admin-order-submit'); button.disabled = true; el('admin-order-error').textContent = '';
        try { await api(`/admin/orders/${state.selectedAdminItem.id}`, { method: 'PATCH', body: JSON.stringify({ status: event.currentTarget.elements.status.value }) }); closeAdminDrawer(); toast(config.text.orderUpdated); await loadAdminOrders(state.adminOrderPage); } catch (error) { el('admin-order-error').textContent = error.message; } finally { button.disabled = false; }
    }

    async function submitRole(event) {
        event.preventDefault(); const creating = !state.selectedRole; const form = event.currentTarget; const body = { name: form.elements.name.value.trim(), permissions: [...form.querySelectorAll('[name="permissions"]:checked')].map((input) => input.value) }; const button = el('admin-save-role'); button.disabled = true; el('admin-role-error').textContent = '';
        try { const payload = await api(creating ? '/admin/roles' : `/admin/roles/${state.selectedRole.id}`, { method: creating ? 'POST' : 'PATCH', body: JSON.stringify(body) }); state.selectedRole = payload.data; toast(creating ? config.text.roleCreated : config.text.roleUpdated); await loadRoles(); } catch (error) { el('admin-role-error').textContent = error.message; } finally { button.disabled = false; }
    }

    async function deleteSelectedRole() {
        if (!state.selectedRole || !window.confirm(config.text.deleteRoleConfirm)) return;
        try { await api(`/admin/roles/${state.selectedRole.id}`, { method: 'DELETE' }); state.selectedRole = null; toast(config.text.roleDeleted); await loadRoles(true); } catch (error) { el('admin-role-error').textContent = error.message; }
    }

    async function handleAdminProductAction(action, id) {
        const item = state.adminGroceries.find((row) => row.id === id); if (!item) return;
        if (action === 'edit') return openAdminProductDrawer(item); if (action === 'stock') return openAdminStockDrawer(item);
        if (action === 'delete' && window.confirm(config.text.deleteConfirm)) { try { await api(`/admin/groceries/${id}`, { method: 'DELETE' }); toast(config.text.productDeleted); await loadAdminCatalog(el('admin-search').value.trim(), state.adminGroceries.length === 1 && state.adminProductPage > 1 ? state.adminProductPage - 1 : state.adminProductPage); } catch (error) { toast(error.message); } }
    }

    function switchAuth(mode) {
        state.authMode = mode; document.querySelectorAll('[data-auth-tab]').forEach((button) => button.classList.toggle('active', button.dataset.authTab === mode)); document.querySelectorAll('.register-only').forEach((node) => node.classList.toggle('hidden', mode !== 'register')); el('auth-submit').textContent = mode === 'register' ? config.text.register : config.text.signIn; el('auth-form').elements.password.autocomplete = mode === 'register' ? 'new-password' : 'current-password'; el('auth-error').textContent = '';
    }

    async function submitAuth(event) {
        event.preventDefault(); const form = event.currentTarget; const body = Object.fromEntries(new FormData(form).entries()); if (state.authMode === 'login') { delete body.name; delete body.password_confirmation; } const button = el('auth-submit'); button.disabled = true; el('auth-error').textContent = '';
        try { const payload = await api(`/auth/${state.authMode}`, { method: 'POST', body: JSON.stringify(body) }); state.token = payload.data.access_token; state.user = payload.data.user; localStorage.setItem('freshbasket_token', state.token); form.reset(); renderAccount(); renderWorkspace(); hideAuth(); const pending = state.pendingAction; state.pendingAction = null; if (!canUseAdmin()) { await Promise.all([loadCatalog(), loadOrders()]); if (pending === 'checkout') await checkout(); if (pending === 'orders') el('orders').scrollIntoView({ behavior: 'smooth' }); } } catch (error) { el('auth-error').textContent = error.message; } finally { button.disabled = false; }
    }

    async function logout() {
        if (!state.token) return showAuth(); try { await api('/auth/logout', { method: 'POST' }); } catch (_) { /* clear locally */ } clearSession(); renderCart(); renderGuestOrders(); hideAuth(); loadCatalog();
    }

    let toastTimer;
    function toast(message) { const node = el('toast'); node.textContent = message; node.classList.add('show'); clearTimeout(toastTimer); toastTimer = setTimeout(() => node.classList.remove('show'), 3200); }
    const debounce = (callback, delay = 250) => { let timer; return (...args) => { clearTimeout(timer); timer = setTimeout(() => callback(...args), delay); }; };

    document.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-action]'); if (actionButton && ['add', 'increase', 'decrease'].includes(actionButton.dataset.action)) changeQuantity(Number(actionButton.dataset.id), actionButton.dataset.action === 'decrease' ? -1 : 1); if (actionButton?.dataset.action === 'close-admin-drawer') closeAdminDrawer();
        const sectionButton = event.target.closest('[data-admin-section]'); if (sectionButton) activateAdminSection(sectionButton.dataset.adminSection);
        const productButton = event.target.closest('[data-admin-action]'); if (productButton) handleAdminProductAction(productButton.dataset.adminAction, Number(productButton.dataset.id));
        const orderButton = event.target.closest('[data-order-id]'); if (orderButton) openAdminOrderDrawer(state.adminOrders.find((row) => row.id === Number(orderButton.dataset.orderId)));
        const userButton = event.target.closest('[data-user-id]'); if (userButton) openAdminUserDrawer(state.adminUsers.find((row) => row.id === Number(userButton.dataset.userId)));
        const roleButton = event.target.closest('[data-role-id]'); if (roleButton) selectRole(state.roles.find((row) => row.id === Number(roleButton.dataset.roleId)));
    });

    document.querySelectorAll('[data-auth-tab]').forEach((button) => button.addEventListener('click', () => switchAuth(button.dataset.authTab)));
    el('auth-form').addEventListener('submit', submitAuth); el('account-button').addEventListener('click', logout); el('admin-logout').addEventListener('click', logout); el('modal-close').addEventListener('click', closeAuth);
    el('orders-link').addEventListener('click', () => { if (!state.token) { state.pendingAction = 'orders'; showAuth(); return; } el('orders').scrollIntoView({ behavior: 'smooth' }); }); el('checkout').addEventListener('click', checkout);
    el('admin-link').addEventListener('click', showAdminWorkspace); el('storefront-link').addEventListener('click', showStorefrontPreview); el('admin-storefront-link').addEventListener('click', showStorefrontPreview); el('admin-menu-toggle').addEventListener('click', openAdminSidebar); el('admin-sidebar-backdrop').addEventListener('click', closeAdminSidebar);
    el('admin-add-product').addEventListener('click', () => openAdminProductDrawer()); el('admin-add-user').addEventListener('click', () => openAdminUserDrawer()); el('admin-drawer-close').addEventListener('click', closeAdminDrawer); el('admin-drawer-backdrop').addEventListener('click', closeAdminDrawer);
    el('admin-product-form').addEventListener('submit', submitAdminProduct); el('admin-stock-form').addEventListener('submit', submitAdminStock); el('admin-user-form').addEventListener('submit', submitAdminUser); el('admin-order-form').addEventListener('submit', submitAdminOrder); el('admin-role-form').addEventListener('submit', submitRole); el('admin-add-role').addEventListener('click', () => selectRole(null)); el('admin-cancel-role').addEventListener('click', () => selectRole(state.roles[0] || null)); el('admin-delete-role').addEventListener('click', deleteSelectedRole);
    el('admin-status-filter').addEventListener('change', () => loadAdminCatalog(el('admin-search').value.trim(), 1)); el('admin-previous-page').addEventListener('click', () => loadAdminCatalog(el('admin-search').value.trim(), state.adminProductPage - 1)); el('admin-next-page').addEventListener('click', () => loadAdminCatalog(el('admin-search').value.trim(), state.adminProductPage + 1));
    el('admin-order-status').addEventListener('change', () => loadAdminOrders(1)); el('admin-order-date').addEventListener('change', () => loadAdminOrders(1)); el('admin-order-previous').addEventListener('click', () => loadAdminOrders(state.adminOrderPage - 1)); el('admin-order-next').addEventListener('click', () => loadAdminOrders(state.adminOrderPage + 1));
    el('admin-user-role').addEventListener('change', () => loadAdminUsers(1)); el('admin-user-status').addEventListener('change', () => loadAdminUsers(1)); el('admin-user-previous').addEventListener('click', () => loadAdminUsers(state.adminUserPage - 1)); el('admin-user-next').addEventListener('click', () => loadAdminUsers(state.adminUserPage + 1));
    el('search').addEventListener('input', debounce((event) => loadCatalog(event.target.value.trim()))); el('admin-search').addEventListener('input', debounce((event) => loadAdminCatalog(event.target.value.trim(), 1))); el('admin-order-search').addEventListener('input', debounce(() => loadAdminOrders(1))); el('admin-user-search').addEventListener('input', debounce(() => loadAdminUsers(1)));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { if (!el('admin-drawer').classList.contains('hidden')) closeAdminDrawer(); else closeAdminSidebar(); } });

    renderCart(); if (state.token) bootAuthenticated().catch(() => { clearSession(); hideAuth(); loadCatalog(); renderGuestOrders(); }); else { loadCatalog(); renderGuestOrders(); }
})();
