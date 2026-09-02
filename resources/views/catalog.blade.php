<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="FreshBasket grocery booking system">
    <title>FreshBasket</title>
    <link rel="stylesheet" href="{{ asset('css/freshbasket.css') }}">
</head>
<body>
    <header class="site-header" id="site-header">
        <a class="brand" href="{{ route('catalog') }}" aria-label="FreshBasket home">
            <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 18h30l-3 21H12L9 18Zm6 0 5-9m13 9-5-9M16 24v9m8-9v9m8-9v9"/></svg>
            <span>FreshBasket</span>
        </a>
        <nav class="header-actions" aria-label="Primary navigation">
            <div class="workspace-navigation hidden" id="workspace-navigation">
                <button class="text-button" id="storefront-link" type="button">{{ __('ui.storefront') }}</button>
                <button class="text-button active" id="admin-link" type="button">{{ __('ui.admin_panel') }}</button>
            </div>
            <div class="languages" aria-label="Language">
                <a href="?lang=en" @class(['active' => app()->getLocale() === 'en'])>English</a>
                <a href="?lang=bn" @class(['active' => app()->getLocale() === 'bn'])>বাংলা</a>
            </div>
            <button class="text-button" id="orders-link" type="button">{{ __('ui.orders') }}</button>
            <button class="account-button" id="account-button" type="button">{{ __('ui.sign_in') }}</button>
        </nav>
    </header>

    <main id="storefront">
        <div class="workspace">
            <section class="catalog-panel" aria-labelledby="catalog-heading">
                <div class="catalog-heading">
                    <div>
                        <h1 id="catalog-heading">{{ __('ui.tagline') }}</h1>
                        <p>{{ __('ui.support') }}</p>
                    </div>
                    <svg class="produce-line" viewBox="0 0 190 100" aria-hidden="true"><path d="M14 89c36-34 84-48 161-61M62 67C43 58 43 39 54 26c13 9 17 26 8 41Zm40-19c-13-17-5-34 9-43 9 15 7 31-9 43Zm31-12c1-18 15-28 31-27-1 16-12 27-31 27ZM92 55c8 5 15 16 15 30M34 77c2 7 2 14-1 21"/></svg>
                </div>

                <div class="catalog-tools">
                    <label class="search-box">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                        <span class="sr-only">{{ __('ui.search') }}</span>
                        <input id="search" type="search" placeholder="{{ __('ui.search') }}" autocomplete="off">
                    </label>
                    <div class="availability">{{ __('ui.available') }}</div>
                </div>

                <div class="product-table" role="table" aria-label="Groceries">
                    <div class="product-header" role="row">
                        <span role="columnheader">{{ __('ui.product') }}</span>
                        <span role="columnheader">{{ __('ui.unit_price') }}</span>
                        <span role="columnheader">{{ __('ui.stock') }}</span>
                        <span role="columnheader">{{ __('ui.action') }}</span>
                    </div>
                    <div id="product-list" aria-live="polite">
                        <div class="loading-row">{{ __('ui.loading') }}</div>
                    </div>
                </div>
            </section>

            <aside class="cart-panel" aria-labelledby="basket-heading">
                <div class="section-title">
                    <h2 id="basket-heading">{{ __('ui.basket') }}</h2>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16l-2 12H6L4 8Zm4 0 4-5 4 5M9 12v4m6-4v4"/></svg>
                </div>
                <div id="cart-items"></div>
                <div class="cart-footer">
                    <div class="total-line"><span>{{ __('ui.total') }}</span><strong id="cart-total">৳0.00</strong></div>
                    <button class="primary-button" id="checkout" type="button" disabled>{{ __('ui.place_order') }}</button>
                </div>
            </aside>
        </div>

        <section class="orders-panel" id="orders" aria-labelledby="orders-heading">
            <div class="section-title">
                <h2 id="orders-heading">{{ __('ui.orders') }}</h2>
                <span id="order-count"></span>
            </div>
            <div class="orders-table" id="order-list"></div>
        </section>
    </main>

    <main class="admin-shell hidden" id="admin-panel">
        <div class="admin-sidebar-backdrop hidden" id="admin-sidebar-backdrop"></div>
        <aside class="admin-sidebar" id="admin-sidebar">
            <a class="admin-brand" href="{{ route('catalog') }}" aria-label="FreshBasket admin home">
                <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 18h30l-3 21H12L9 18Zm6 0 5-9m13 9-5-9M16 24v9m8-9v9m8-9v9"/></svg>
                <span>FreshBasket</span>
            </a>
            <nav class="admin-sidebar-nav" aria-label="Admin navigation">
                <button class="admin-nav-item active" data-admin-section="overview" data-permission="dashboard.view" type="button"><svg viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg><span>{{ __('ui.overview') }}</span></button>
                <button class="admin-nav-item" data-admin-section="products" data-permission="groceries.view" type="button"><svg viewBox="0 0 24 24"><path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10"/></svg><span>{{ __('ui.products') }}</span></button>
                <button class="admin-nav-item" data-admin-section="admin-orders" data-permission="orders.view-all" type="button"><svg viewBox="0 0 24 24"><path d="M7 4h10l2 3v13H5V7l2-3Zm0 4h10M9 12h6m-6 4h4"/></svg><span>{{ __('ui.admin_orders') }}</span></button>
                <button class="admin-nav-item" data-admin-section="users" data-permission="users.view" type="button"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3-7 8-7s8 3 8 7"/></svg><span>{{ __('ui.users') }}</span></button>
                <button class="admin-nav-item" data-admin-section="roles" data-permission="roles.view" type="button"><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3 8 8 10 5-2 8-5 8-10V6l-8-3Zm-3 9 2 2 4-5"/></svg><span>{{ __('ui.roles_permissions') }}</span></button>
                <span class="admin-nav-divider"></span>
                <button class="admin-nav-item" id="admin-storefront-link" type="button"><svg viewBox="0 0 24 24"><path d="M4 10v10h16V10M3 10l2-6h14l2 6M8 20v-6h5v6M3 10c1 2 3 2 4 0 1 2 3 2 4 0 1 2 3 2 4 0 1 2 3 2 4 0"/></svg><span>{{ __('ui.storefront') }}</span></button>
            </nav>
            <div class="admin-sidebar-footer">
                <div class="admin-sidebar-languages"><a href="?lang=en">EN</a><span>/</span><a href="?lang=bn">বাংলা</a></div>
                <div class="admin-identity"><span class="admin-avatar" aria-hidden="true"></span><span><strong id="admin-user-name">{{ __('ui.admin_panel') }}</strong><button id="admin-logout" type="button">{{ __('ui.logout') }}</button></span></div>
            </div>
        </aside>

        <div class="admin-main">
            <div class="admin-mobile-bar"><button id="admin-menu-toggle" type="button" aria-label="{{ __('ui.open_menu') }}"><span></span><span></span><span></span></button><strong>FreshBasket</strong></div>

            <section class="admin-view" data-admin-view="overview">
                <div class="admin-page-heading"><div><h1>{{ __('ui.overview') }}</h1><p>{{ __('ui.overview_help') }}</p></div></div>
                <div class="admin-metrics" id="admin-metrics"></div>
                <div class="overview-grid">
                    <section class="management-surface"><div class="surface-heading"><h2>{{ __('ui.recent_orders') }}</h2></div><div class="overview-orders" id="overview-orders"></div></section>
                    <section class="management-surface stock-attention"><div class="surface-heading"><h2>{{ __('ui.stock_attention') }}</h2></div><div id="stock-attention-list"></div><button class="text-link" data-admin-section="products" data-permission="groceries.view" type="button">{{ __('ui.manage_products') }} →</button></section>
                </div>
            </section>

            <section class="admin-view hidden" data-admin-view="products">
                <div class="admin-page-heading"><div><h1>{{ __('ui.manage_groceries') }}</h1><p>{{ __('ui.manage_groceries_help') }}</p></div><button class="primary-button admin-add-button" id="admin-add-product" data-permission="groceries.create" type="button">+ {{ __('ui.add_product') }}</button></div>
                <section class="admin-catalogue management-surface">
                    <div class="admin-tools"><label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="admin-search" type="search" placeholder="{{ __('ui.search_products') }}"></label><label class="admin-filter"><select id="admin-status-filter"><option value="all">{{ __('ui.all_statuses') }}</option><option value="active">{{ __('ui.active') }}</option><option value="inactive">{{ __('ui.inactive') }}</option></select></label></div>
                    <div class="admin-table-wrap"><div class="admin-table" role="table"><div class="admin-row admin-table-header"><span>{{ __('ui.product') }}</span><span>{{ __('ui.unit') }}</span><span>{{ __('ui.unit_price') }}</span><span>{{ __('ui.stock') }}</span><span>{{ __('ui.status') }}</span><span>{{ __('ui.actions') }}</span></div><div id="admin-product-list"></div></div></div>
                    <div class="admin-table-footer"><p class="admin-table-summary" id="admin-table-summary"></p><div class="admin-pagination"><button class="admin-action" id="admin-previous-page" type="button">{{ __('ui.previous') }}</button><button class="admin-action" id="admin-next-page" type="button">{{ __('ui.next') }}</button></div></div>
                </section>
            </section>

            <section class="admin-view hidden" data-admin-view="admin-orders">
                <div class="admin-page-heading"><div><h1>{{ __('ui.admin_orders') }}</h1><p>{{ __('ui.admin_orders_help') }}</p></div></div>
                <section class="management-surface"><div class="management-tools three"><label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="admin-order-search" type="search" placeholder="{{ __('ui.search_orders') }}"></label><select id="admin-order-status"><option value="">{{ __('ui.all_statuses') }}</option><option value="confirmed">{{ __('ui.confirmed') }}</option><option value="processing">{{ __('ui.processing') }}</option><option value="completed">{{ __('ui.completed') }}</option><option value="cancelled">{{ __('ui.cancelled') }}</option></select><select id="admin-order-date"><option value="">{{ __('ui.all_dates') }}</option><option value="today">{{ __('ui.today') }}</option><option value="7days">{{ __('ui.last_7_days') }}</option><option value="30days">{{ __('ui.last_30_days') }}</option></select></div><div class="admin-table-wrap"><div class="data-table orders-management-table"><div class="data-row data-header"><span>{{ __('ui.order_id') }}</span><span>{{ __('ui.customer') }}</span><span>{{ __('ui.items') }}</span><span>{{ __('ui.date') }}</span><span>{{ __('ui.total') }}</span><span>{{ __('ui.status') }}</span><span>{{ __('ui.actions') }}</span></div><div id="admin-order-list"></div></div></div><div class="admin-table-footer"><p id="admin-order-summary"></p><div class="admin-pagination"><button class="admin-action" id="admin-order-previous" type="button">{{ __('ui.previous') }}</button><button class="admin-action" id="admin-order-next" type="button">{{ __('ui.next') }}</button></div></div></section>
            </section>

            <section class="admin-view hidden" data-admin-view="users">
                <div class="admin-page-heading"><div><h1>{{ __('ui.users') }}</h1><p>{{ __('ui.users_help') }}</p></div><button class="primary-button admin-add-button" id="admin-add-user" data-permission="users.manage" type="button">+ {{ __('ui.add_user') }}</button></div>
                <section class="management-surface"><div class="management-tools three"><label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="admin-user-search" type="search" placeholder="{{ __('ui.search_users') }}"></label><select id="admin-user-role"><option value="">{{ __('ui.all_roles') }}</option></select><select id="admin-user-status"><option value="">{{ __('ui.all_statuses') }}</option><option value="1">{{ __('ui.active') }}</option><option value="0">{{ __('ui.inactive') }}</option></select></div><div class="admin-table-wrap"><div class="data-table users-management-table"><div class="data-row data-header"><span>{{ __('ui.user') }}</span><span>{{ __('ui.role') }}</span><span>{{ __('ui.orders') }}</span><span>{{ __('ui.status') }}</span><span>{{ __('ui.joined') }}</span><span>{{ __('ui.actions') }}</span></div><div id="admin-user-list"></div></div></div><div class="admin-table-footer"><p id="admin-user-summary"></p><div class="admin-pagination"><button class="admin-action" id="admin-user-previous" type="button">{{ __('ui.previous') }}</button><button class="admin-action" id="admin-user-next" type="button">{{ __('ui.next') }}</button></div></div></section>
            </section>

            <section class="admin-view hidden" data-admin-view="roles">
                <div class="admin-page-heading"><div><h1>{{ __('ui.roles_permissions') }}</h1><p>{{ __('ui.roles_help') }}</p></div><button class="secondary-button compact-button" id="admin-add-role" data-permission="roles.manage" type="button">+ {{ __('ui.add_role') }}</button></div>
                <div class="roles-workspace"><section class="management-surface roles-list"><div class="role-list-header"><span>{{ __('ui.role') }}</span><span>{{ __('ui.type') }}</span><span>{{ __('ui.users') }}</span><span>{{ __('ui.permissions') }}</span></div><div id="admin-role-list"></div></section><section class="management-surface role-editor"><form id="admin-role-form"><h2 id="role-editor-title">{{ __('ui.select_role') }}</h2><label>{{ __('ui.role_name') }}<input name="name" type="text" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"></label><div class="permission-groups" id="permission-groups"></div><p class="admin-form-note" id="role-system-note">{{ __('ui.system_role_help') }}</p><div class="form-error" id="admin-role-error"></div><div class="role-editor-actions"><button class="danger-button hidden" id="admin-delete-role" type="button">{{ __('ui.delete_role') }}</button><span></span><button class="secondary-button compact-button" id="admin-cancel-role" type="button">{{ __('ui.cancel') }}</button><button class="primary-button compact-button" id="admin-save-role" type="submit">{{ __('ui.save_changes') }}</button></div></form></section></div>
            </section>
        </div>
    </main>

    <div class="admin-drawer-backdrop hidden" id="admin-drawer-backdrop"></div>
    <aside class="admin-drawer hidden" id="admin-drawer" aria-labelledby="admin-drawer-title" aria-modal="true" role="dialog">
        <div class="admin-drawer-header">
            <h2 id="admin-drawer-title">{{ __('ui.add_product') }}</h2>
            <button class="drawer-close" id="admin-drawer-close" type="button" aria-label="{{ __('ui.close') }}">&times;</button>
        </div>
        <form id="admin-product-form" novalidate>
            <label>{{ __('ui.name') }}<input name="name" type="text" maxlength="255" required></label>
            <label>{{ __('ui.description') }}<textarea name="description" maxlength="2000" rows="3"></textarea></label>
            <label>{{ __('ui.unit') }}<input name="unit" type="text" maxlength="32" required></label>
            <label>{{ __('ui.unit_price_taka') }}<input name="unit_price" type="number" min="0" max="9999999.99" step="0.01" required></label>
            <label class="create-stock-field">{{ __('ui.initial_stock') }}<input name="stock_quantity" type="number" min="0" max="1000000" step="1" required></label>
            <label>{{ __('ui.status') }}
                <select name="is_active" required>
                    <option value="1">{{ __('ui.active') }}</option>
                    <option value="0">{{ __('ui.inactive') }}</option>
                </select>
            </label>
            <div class="admin-form-note edit-stock-note hidden">{{ __('ui.stock_separate_help') }}</div>
            <div class="form-error" id="admin-product-error" role="alert"></div>
            <div class="admin-drawer-actions">
                <button class="secondary-button" data-action="close-admin-drawer" type="button">{{ __('ui.cancel') }}</button>
                <button class="primary-button" id="admin-product-submit" type="submit">{{ __('ui.create_product') }}</button>
            </div>
        </form>
        <form class="hidden" id="admin-stock-form" novalidate>
            <p class="stock-product-name" id="stock-product-name"></p>
            <label>{{ __('ui.stock_quantity') }}<input name="stock_quantity" type="number" min="0" max="1000000" step="1" required></label>
            <p class="admin-form-note">{{ __('ui.absolute_stock_help') }}</p>
            <div class="form-error" id="admin-stock-error" role="alert"></div>
            <div class="admin-drawer-actions">
                <button class="secondary-button" data-action="close-admin-drawer" type="button">{{ __('ui.cancel') }}</button>
                <button class="primary-button" id="admin-stock-submit" type="submit">{{ __('ui.update_stock') }}</button>
            </div>
        </form>
        <form class="hidden" id="admin-user-form" novalidate>
            <label>{{ __('ui.name') }}<input name="name" type="text" maxlength="120" required></label>
            <label>{{ __('ui.email') }}<input name="email" type="email" maxlength="255" required></label>
            <label class="admin-user-password-field">{{ __('ui.password') }}<input name="password" type="password" minlength="8"></label>
            <label class="admin-user-password-field">{{ __('ui.confirm_password') }}<input name="password_confirmation" type="password" minlength="8"></label>
            <label>{{ __('ui.status') }}<select name="is_active"><option value="1">{{ __('ui.active') }}</option><option value="0">{{ __('ui.inactive') }}</option></select></label>
            <fieldset class="role-checkboxes"><legend>{{ __('ui.roles') }}</legend><p>{{ __('ui.select_roles_help') }}</p><div id="admin-user-role-options"></div></fieldset>
            <p class="admin-form-note">{{ __('ui.last_admin_help') }}</p>
            <div class="form-error" id="admin-user-error" role="alert"></div>
            <div class="admin-drawer-actions"><button class="secondary-button" data-action="close-admin-drawer" type="button">{{ __('ui.cancel') }}</button><button class="primary-button" id="admin-user-submit" type="submit">{{ __('ui.save_changes') }}</button></div>
        </form>
        <form class="hidden" id="admin-order-form" novalidate>
            <div class="order-detail-meta" id="admin-order-meta"></div>
            <div class="order-detail-items"><h3>{{ __('ui.items') }}</h3><div id="admin-order-items"></div><div class="order-detail-total"><span>{{ __('ui.total') }}</span><strong id="admin-order-total"></strong></div></div>
            <label>{{ __('ui.status') }}<select name="status" id="admin-order-status-select"></select></label>
            <p class="admin-form-note">{{ __('ui.order_stock_help') }}</p>
            <div class="form-error" id="admin-order-error" role="alert"></div>
            <div class="admin-drawer-actions"><button class="secondary-button" data-action="close-admin-drawer" type="button">{{ __('ui.close') }}</button><button class="primary-button" id="admin-order-submit" type="submit">{{ __('ui.update_status') }}</button></div>
        </form>
    </aside>

    <div class="modal-backdrop hidden" id="auth-modal" role="dialog" aria-modal="true" aria-labelledby="auth-heading">
        <div class="auth-modal">
            <button class="modal-close" id="modal-close" type="button" aria-label="Close">×</button>
            <h2 id="auth-heading">{{ __('ui.welcome') }}</h2>
            <p>{{ __('ui.auth_help') }}</p>
            <div class="auth-tabs" role="tablist">
                <button class="active" data-auth-tab="login" type="button">{{ __('ui.sign_in') }}</button>
                <button data-auth-tab="register" type="button">{{ __('ui.register') }}</button>
            </div>
            <form id="auth-form" novalidate>
                <label class="register-only hidden">{{ __('ui.name') }}<input name="name" type="text" maxlength="120" autocomplete="name"></label>
                <label>{{ __('ui.email') }}<input name="email" type="email" required autocomplete="email"></label>
                <label>{{ __('ui.password') }}<input name="password" type="password" required autocomplete="current-password"></label>
                <label class="register-only hidden">{{ __('ui.confirm_password') }}<input name="password_confirmation" type="password" autocomplete="new-password"></label>
                <div class="form-error" id="auth-error" role="alert"></div>
                <button class="primary-button" id="auth-submit" type="submit">{{ __('ui.sign_in') }}</button>
            </form>
        </div>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script>
        window.FreshBasket = {{ Illuminate\Support\Js::from([
            'apiBase' => url('/api/v1'),
            'locale' => app()->getLocale(),
            'text' => [
                'add' => __('ui.add'), 'inStock' => __('ui.in_stock'), 'emptyBasket' => __('ui.empty_basket'),
                'emptyOrders' => __('ui.empty_orders'), 'signIn' => __('ui.sign_in'), 'register' => __('ui.register'),
                'logout' => __('ui.logout'), 'orderPlaced' => __('ui.order_placed'), 'orderId' => __('ui.order_id'),
                'date' => __('ui.date'), 'items' => __('ui.items'), 'total' => __('ui.total'), 'status' => __('ui.status'),
                'signInForOrders' => __('ui.sign_in_for_orders'),
                'storefront' => __('ui.storefront'), 'adminPanel' => __('ui.admin_panel'),
                'edit' => __('ui.edit'), 'stock' => __('ui.stock'), 'delete' => __('ui.delete'),
                'active' => __('ui.active'), 'inactive' => __('ui.inactive'), 'actions' => __('ui.actions'),
                'addProduct' => __('ui.add_product'), 'editProduct' => __('ui.edit_product'),
                'createProduct' => __('ui.create_product'), 'saveChanges' => __('ui.save_changes'),
                'updateStock' => __('ui.update_stock'), 'deleteConfirm' => __('ui.delete_product_confirm'),
                'productCreated' => __('ui.product_created'), 'productUpdated' => __('ui.product_updated'),
                'stockUpdated' => __('ui.stock_updated'), 'productDeleted' => __('ui.product_deleted'),
                'productsShown' => __('ui.products_shown'), 'noAdminProducts' => __('ui.no_admin_products'),
                'previous' => __('ui.previous'), 'next' => __('ui.next'),
                'overview' => __('ui.overview'), 'products' => __('ui.products'), 'orders' => __('ui.admin_orders'), 'users' => __('ui.users'),
                'rolesPermissions' => __('ui.roles_permissions'), 'lowStock' => __('ui.low_stock'),
                'recentOrders' => __('ui.recent_orders'), 'stockAttention' => __('ui.stock_attention'),
                'manageProducts' => __('ui.manage_products'), 'manage' => __('ui.manage'),
                'confirmed' => __('ui.confirmed'), 'processing' => __('ui.processing'),
                'completed' => __('ui.completed'), 'cancelled' => __('ui.cancelled'),
                'ordersShown' => __('ui.admin_orders_shown'), 'noAdminOrders' => __('ui.no_admin_orders'),
                'orderUpdated' => __('ui.order_updated'), 'updateStatus' => __('ui.update_status'),
                'addUser' => __('ui.add_user'), 'manageUser' => __('ui.manage_user'), 'allRoles' => __('ui.all_roles'),
                'customer' => __('ui.customer'),
                'createUser' => __('ui.create_user'), 'userCreated' => __('ui.user_created'),
                'userUpdated' => __('ui.user_updated'), 'usersShown' => __('ui.users_shown'),
                'noUsers' => __('ui.no_users'), 'system' => __('ui.system'), 'custom' => __('ui.custom'),
                'addRole' => __('ui.add_role'), 'selectRole' => __('ui.select_role'),
                'deleteRoleConfirm' => __('ui.delete_role_confirm'), 'roleCreated' => __('ui.role_created'),
                'roleUpdated' => __('ui.role_updated'), 'roleDeleted' => __('ui.role_deleted'),
                'permissionGroups' => [
                    'dashboard' => __('ui.dashboard_group'), 'groceries' => __('ui.catalogue_group'),
                    'inventory' => __('ui.inventory_group'), 'orders' => __('ui.orders_group'),
                    'users' => __('ui.users_group'), 'roles' => __('ui.roles_group'),
                ],
            ],
        ]) }};
    </script>
    <script src="{{ asset('js/freshbasket.js') }}" defer></script>
</body>
</html>
