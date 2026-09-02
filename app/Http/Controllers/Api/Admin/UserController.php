<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminUserIndexRequest;
use App\Http\Requests\Api\StoreAdminUserRequest;
use App\Http\Requests\Api\UpdateAdminUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\RoleResource;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $users,
        private readonly AccessControlService $access,
    ) {}

    public function roleOptions(): JsonResponse
    {
        return response()->json([
            'data' => RoleResource::collection($this->access->roles())->resolve(),
        ]);
    }

    public function index(AdminUserIndexRequest $request)
    {
        return AdminUserResource::collection($this->users->paginate(
            $request->validated('search'),
            $request->validated('role'),
            $request->activeStatus(),
            $request->perPage(),
            $request->page(),
        ));
    }

    public function store(StoreAdminUserRequest $request)
    {
        return (new AdminUserResource($this->users->create($request->validated())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): AdminUserResource
    {
        return new AdminUserResource($this->users->find($user->id));
    }

    public function update(UpdateAdminUserRequest $request, User $user): AdminUserResource
    {
        return new AdminUserResource($this->users->update($request->user('api'), $user, $request->validated()));
    }
}
