<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRoleRequest;
use App\Http\Requests\Api\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\AccessControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly AccessControlService $access) {}

    public function index(): JsonResponse
    {
        $permissions = collect(PermissionName::cases())->map(fn (PermissionName $permission) => [
            'name' => $permission->value,
            'group' => str($permission->value)->before('.')->value(),
        ])->values();

        return response()->json(['data' => [
            'roles' => RoleResource::collection($this->access->roles())->resolve(),
            'permissions' => $permissions,
        ]]);
    }

    public function store(StoreRoleRequest $request)
    {
        return (new RoleResource($this->access->create($request->validated())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        return new RoleResource($this->access->update($role, $request->validated()));
    }

    public function destroy(Role $role): Response
    {
        $this->access->delete($role);

        return response()->noContent();
    }
}
