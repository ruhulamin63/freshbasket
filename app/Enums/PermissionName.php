<?php

namespace App\Enums;

enum PermissionName: string
{
    case DashboardView = 'dashboard.view';
    case GroceriesView = 'groceries.view';
    case GroceriesCreate = 'groceries.create';
    case GroceriesUpdate = 'groceries.update';
    case GroceriesDelete = 'groceries.delete';
    case InventoryUpdate = 'inventory.update';
    case OrdersCreate = 'orders.create';
    case OrdersViewOwn = 'orders.view-own';
    case OrdersViewAll = 'orders.view-all';
    case OrdersUpdate = 'orders.update';
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesView = 'roles.view';
    case RolesManage = 'roles.manage';
}
