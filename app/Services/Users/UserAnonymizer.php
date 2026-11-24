<?php

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Models\SegmentProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserAnonymizer
{
    public function anonymize(User $user, User $admin): void
    {
        DB::transaction(function () use ($user) {
            SegmentProgress::query()->where('user_id', $user->id)->delete();
            $user->enrollments()->delete();
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

            $user->forceFill([
                'name' => "Pengguna dihapus #{$user->id}",
                'email' => "deleted-{$user->id}@example.invalid",
                'phone' => null,
                'password' => Str::random(64),
                'email_verified_at' => null,
                'remember_token' => null,
                'role' => UserRole::User,
            ])->save();
        });

        Log::info('Admin removed personal data', ['user_id' => $user->id, 'admin_id' => $admin->id, 'at' => now()->toIso8601String()]);
    }
}
