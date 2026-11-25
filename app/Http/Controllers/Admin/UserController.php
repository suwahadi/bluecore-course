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
use Illuminate\Support\Facades\DB;
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
        $emailChanged = $data['email'] !== $user->email;

        $saved = DB::transaction(function () use ($user, $role, $data, $emailChanged) {
            if ($user->isAdmin() && $role !== UserRole::Admin && $this->lockedAdminCount() <= 1) {
                return false;
            }

            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => $role,
                ...($emailChanged ? ['email_verified_at' => null] : []),
                ...(filled($data['password'] ?? null) ? ['password' => $data['password']] : []),
            ])->save();

            return true;
        });

        if (! $saved) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['role' => 'Minimal harus ada satu admin.'], 'user-'.$user->id);
        }

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

        $erased = DB::transaction(function () use ($user, $request, $anonymizer) {
            if ($user->isAdmin() && $this->lockedAdminCount() <= 1) {
                return false;
            }

            $anonymizer->anonymize($user, $request->user());

            return true;
        });

        if (! $erased) {
            return back()->with('error', 'Minimal harus ada satu admin.');
        }

        return back()->with('status', 'Data pribadi pengguna dihapus.');
    }

    private function lockedAdminCount(): int
    {
        return User::query()->where('role', UserRole::Admin)->lockForUpdate()->count();
    }
}
