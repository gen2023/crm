<?php

namespace App\Modules\Customers\Services;

use App\Models\Customer;
use App\Support\AuditLogger;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function paginate(): LengthAwarePaginator
    {
        return Customer::query()->orderBy('name')->paginate(20);
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null}  $data
     */
    public function create(array $data): Customer
    {
        $customer = Customer::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
        ]);

        $this->auditLogger->log('customer.created', $customer, [
            'phone' => $customer->phone,
        ]);

        return $customer;
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null}  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
        ]);

        $this->auditLogger->log('customer.updated', $customer, [
            'changes' => $customer->getChanges(),
        ]);

        return $customer;
    }
}
