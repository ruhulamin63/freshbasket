<?php

namespace App\Enums;

enum PermissionName: string
{
    case GroceriesView = 'groceries.view';
    case GroceriesCreate = 'groceries.create';
    case GroceriesUpdate = 'groceries.update';
    case GroceriesDelete = 'groceries.delete';
    case InventoryUpdate = 'inventory.update';
    case OrdersCreate = 'orders.create';
    case OrdersViewOwn = 'orders.view-own';
}
