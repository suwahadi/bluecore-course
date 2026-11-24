<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function view(?User $user, Page $page): bool
    {
        return $page->is_published || (bool) $user?->isAdmin();
    }
}
