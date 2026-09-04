@extends('layouts.app')

@section('title', 'Orders — CRM')

@section('content')
    <h1>Заказы</h1>

    @can('orders.create')
        <p>
            <a class="btn" href="{{ route('orders.create') }}">
                <x-icon name="plus" />
                Добавить заказ
            </a>
        </p>
    @endcan

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Клиент</th>
                    <th>Статус</th>
                    <th>Источник</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->customer->name }}</td>
                        <td>{{ $order->statusLabel() }}</td>
                        <td>{{ $order->source ?: '—' }}</td>
                        <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                        <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="{{ route('orders.show', $order) }}" title="Просмотр">
                                    <x-icon name="eye" />
                                </a>
                                @can('orders.edit')
                                    <a class="icon-btn" href="{{ route('orders.edit', $order) }}" title="Редактировать">
                                        <x-icon name="pencil" />
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Заказов пока нет.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
@endsection
