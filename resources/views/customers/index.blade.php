@extends('layouts.app')

@section('title', 'Customers — CRM')

@section('content')
    <h1>Клиенты</h1>

    @can('customers.create')
        <p>
            <a class="btn" href="{{ route('customers.create') }}">
                <x-icon name="plus" />
                Добавить клиента
            </a>
        </p>
    @endcan

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Заказов</th>
                    <th>Сумма</th>
                    <th>Последний заказ</th>
                    <th>Надёжность</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->email ?: '—' }}</td>
                        <td>{{ $customer->orders_count }}</td>
                        <td>{{ number_format((float) $customer->total_orders_amount, 2) }}</td>
                        <td>{{ $customer->last_order_at?->format('d.m.Y') ?? '—' }}</td>
                        <td>{{ $customer->reliability() !== null ? $customer->reliability().'%' : '—' }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="{{ route('customers.show', $customer) }}" title="Просмотр">
                                    <x-icon name="eye" />
                                </a>
                                @can('customers.edit')
                                    <a class="icon-btn" href="{{ route('customers.edit', $customer) }}" title="Редактировать">
                                        <x-icon name="pencil" />
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Клиентов пока нет.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $customers->links() }}
@endsection
