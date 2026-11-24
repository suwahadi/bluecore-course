<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\Users\UserAnonymizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'role']);

        return view('admin.users.index', [
            'users' => User::query()->filteredForAdmin($filters)->withAdminTotals()->with('enrollments:id,user_id,course_id')->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'userCount' => User::query()->count(),
            'adminCount' => User::adminCount(),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title', 'status']),
            'draftStatus' => CourseStatus::Draft,
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $role = $user->is($request->user()) ? $user->role : UserRole::from($data['role']);

        if ($user->isAdmin() && $role !== UserRole::Admin && User::adminCount() <= 1) {
            return back()->withInput()->withErrors(['role' => 'Minimal harus ada satu admin.'], 'user-'.$user->id);
        }

        $emailChanged = $data['email'] !== $user->email;

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $role,
            ...($emailChanged ? ['email_verified_at' => null] : []),
            ...(filled($data['password'] ?? null) ? ['password' => $data['password']] : []),
        ])->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Pengguna disimpan.');
    }

    public function anonymize(Request $request, User $user, UserAnonymizer $anonymizer): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Data pribadi akun sendiri tidak bisa dihapus.');
        }

        if ($user->isAdmin() && User::adminCount() <= 1) {
            return back()->with('error', 'Minimal harus ada satu admin.');
        }

        $anonymizer->anonymize($user, $request->user());

        return back()->with('status', 'Data pribadi pengguna dihapus.');
    }
}
