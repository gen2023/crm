<?php

namespace App\Modules\Dashboard\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * @return Collection<int, AuditLog>
     */
    public function recentLogins(int $limit = 5): Collection
    {
        return AuditLog::query()
            ->where('action', 'auth.login')
            ->with('actor')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
