@extends('layouts.app')

@section('title', 'Dashboard — CRM')

@section('content')
    <h1>Dashboard</h1>

    <div class="card-grid">
        <div class="card">
            <h2 style="font-size:1rem;margin-top:0;">История заходов</h2>
            <table>
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLogins as $entry)
                        <tr>
                            <td>{{ $entry->actor?->name ?? '—' }}</td>
                            <td>{{ $entry->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2">Пока нет записей.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('orders.view')
            <div class="card">
                <h2 style="font-size:1rem;margin-top:0;">Последние 5 заказов</h2>
                <table>
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Клиент</th>
                            <th>Статус</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td><a href="{{ route('orders.show', $order) }}">#{{ $order->id }}</a></td>
                                <td>{{ $order->customer->name }}</td>
                                <td>{{ $order->statusLabel() }}</td>
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Заказов пока нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 style="font-size:1rem;margin-top:0;">Заказы по статусам</h2>
                <table>
                    <tbody>
                        @foreach (\App\Models\Order::STATUS_LABELS as $status => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td>{{ $orderStatusCounts[$status] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endcan

        @can('products.view')
            <div class="card">
                <h2 style="font-size:1rem;margin-top:0;">Мало на складе (&lt; {{ config('dashboard.low_stock_threshold') }})</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Остаток</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStockProducts as $product)
                            <tr>
                                <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                <td>{{ $product->stock }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">Все товары в достатке.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endcan
    </div>
@endsection
