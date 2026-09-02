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
    <header class="site-header">
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

    <main class="admin-panel hidden" id="admin-panel">
        <section class="admin-heading">
            <div>
                <h1>{{ __('ui.manage_groceries') }}</h1>
                <p>{{ __('ui.manage_groceries_help') }}</p>
            </div>
            <button class="primary-button admin-add-button" id="admin-add-product" type="button">
                <span aria-hidden="true">+</span> {{ __('ui.add_product') }}
            </button>
        </section>

        <section class="admin-catalogue" aria-labelledby="admin-catalogue-heading">
            <h2 class="sr-only" id="admin-catalogue-heading">{{ __('ui.manage_groceries') }}</h2>
            <div class="admin-tools">
                <label class="search-box">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                    <span class="sr-only">{{ __('ui.search_products') }}</span>
                    <input id="admin-search" type="search" placeholder="{{ __('ui.search_products') }}" autocomplete="off">
                </label>
                <label class="admin-filter">
                    <span class="sr-only">{{ __('ui.filter_status') }}</span>
                    <select id="admin-status-filter">
                        <option value="all">{{ __('ui.all_statuses') }}</option>
                        <option value="active">{{ __('ui.active') }}</option>
                        <option value="inactive">{{ __('ui.inactive') }}</option>
                    </select>
                </label>
            </div>
            <div class="admin-table-wrap">
                <div class="admin-table" role="table" aria-label="{{ __('ui.manage_groceries') }}">
                    <div class="admin-row admin-table-header" role="row">
                        <span role="columnheader">{{ __('ui.product') }}</span>
                        <span role="columnheader">{{ __('ui.unit') }}</span>
                        <span role="columnheader">{{ __('ui.unit_price') }}</span>
                        <span role="columnheader">{{ __('ui.stock') }}</span>
                        <span role="columnheader">{{ __('ui.status') }}</span>
                        <span role="columnheader">{{ __('ui.actions') }}</span>
                    </div>
                    <div id="admin-product-list" aria-live="polite">
                        <div class="loading-row">{{ __('ui.loading') }}</div>
                    </div>
                </div>
            </div>
            <div class="admin-table-footer">
                <p class="admin-table-summary" id="admin-table-summary"></p>
                <div class="admin-pagination">
                    <button class="admin-action" id="admin-previous-page" type="button">{{ __('ui.previous') }}</button>
                    <button class="admin-action" id="admin-next-page" type="button">{{ __('ui.next') }}</button>
                </div>
            </div>
        </section>
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
            ],
        ]) }};
    </script>
    <script src="{{ asset('js/freshbasket.js') }}" defer></script>
</body>
</html>
