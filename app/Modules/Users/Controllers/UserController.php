<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Requests\StoreUserRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use App\Modules\Users\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(): View
    {
        return view('users.index', [
            'users' => $this->userService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => $this->userService->allRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->route('users.index')->with('status', 'Пользователь создан.');
    }

    public function show(User $user): View
    {
        return view('users.show', [
            'user' => $user->load('roles'),
        ]);
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->userService->allRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('users.index')->with('status', 'Пользователь обновлён.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->deactivate($user);

        return redirect()->route('users.index')->with('status', 'Пользователь деактивирован.');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->userService->activate($user);

        return redirect()->route('users.index')->with('status', 'Пользователь активирован.');
    }
}
