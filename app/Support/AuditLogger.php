<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records who did what to which record, and when. Deliberately minimal per
 * docs/PHASE-1-SPEC.md's Audit Log section — no diff/rollback UI yet, just
 * an append-only, queryable trail that future modules reuse as-is (the
 * subject is polymorphic, so no schema change is needed to audit
 * Customers/Products/Orders/... later).
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties  Never include passwords,
     *                                             tokens, or other secrets.
     */
    public function log(string $action, Model $subject, array $properties = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'properties' => $properties,
        ]);
    }
}
