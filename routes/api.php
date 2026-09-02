<?php

use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\GroceryController as AdminGroceryController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:auth')->prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::get('groceries', [CatalogController::class, 'index']);
    Route::get('groceries/{grocery}', [CatalogController::class, 'show'])->whereNumber('grocery');

    Route::middleware(['auth:api', 'active'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:auth');
        });

        Route::middleware(['role:user,api', 'permission:orders.view-own,api'])->group(function () {
            Route::get('orders', [OrderController::class, 'index']);
            Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
        });
        Route::post('orders', [OrderController::class, 'store'])
            ->middleware(['role:user,api', 'permission:orders.create,api', 'throttle:orders']);

        Route::prefix('admin')->group(function () {
            Route::get('dashboard', AdminDashboardController::class)->middleware('permission:dashboard.view,api');
            Route::get('groceries', [AdminGroceryController::class, 'index'])->middleware('permission:groceries.view,api');
            Route::post('groceries', [AdminGroceryController::class, 'store'])->middleware('permission:groceries.create,api');
            Route::get('groceries/{grocery}', [AdminGroceryController::class, 'show'])->middleware('permission:groceries.view,api');
            Route::put('groceries/{grocery}', [AdminGroceryController::class, 'update'])->middleware('permission:groceries.update,api');
            Route::patch('groceries/{grocery}', [AdminGroceryController::class, 'update'])->middleware('permission:groceries.update,api');
            Route::delete('groceries/{grocery}', [AdminGroceryController::class, 'destroy'])->middleware('permission:groceries.delete,api');
            Route::patch('groceries/{grocery}/stock', [AdminGroceryController::class, 'updateStock'])->middleware('permission:inventory.update,api');

            Route::get('orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view-all,api');
            Route::get('orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->middleware('permission:orders.view-all,api');
            Route::patch('orders/{order}', [AdminOrderController::class, 'update'])->whereNumber('order')->middleware('permission:orders.update,api');

            Route::get('users', [AdminUserController::class, 'index'])->middleware('permission:users.view,api');
            Route::get('users/role-options', [AdminUserController::class, 'roleOptions'])->middleware('permission:users.view,api');
            Route::post('users', [AdminUserController::class, 'store'])->middleware('permission:users.manage,api');
            Route::get('users/{user}', [AdminUserController::class, 'show'])->whereNumber('user')->middleware('permission:users.view,api');
            Route::patch('users/{user}', [AdminUserController::class, 'update'])->whereNumber('user')->middleware('permission:users.manage,api');

            Route::get('roles', [AdminRoleController::class, 'index'])->middleware('permission:roles.view,api');
            Route::post('roles', [AdminRoleController::class, 'store'])->middleware('permission:roles.manage,api');
            Route::patch('roles/{role}', [AdminRoleController::class, 'update'])->whereNumber('role')->middleware('permission:roles.manage,api');
            Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->whereNumber('role')->middleware('permission:roles.manage,api');
        });
    });
});
