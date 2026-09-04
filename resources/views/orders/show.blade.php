@extends('layouts.app')

@section('title', 'Заказ #'.$order->id.' — CRM')

@section('content')
    <h1>Заказ #{{ $order->id }}</h1>

    <div class="card">
        <p>Клиент: <a href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a> ({{ $order->customer->phone }})</p>
        <p>Статус: {{ $order->statusLabel() }}</p>
        <p>Источник: {{ $order->source ?: '—' }}</p>
        <p>Адрес / способ доставки: {{ $order->delivery_address ?: '—' }}</p>
        <p>Способ оплаты: {{ $order->payment_method ?: '—' }}</p>
        <p>ID заказа маркетплейса: {{ $order->marketplace_order_id ?: '—' }}</p>
        <p>Название заказа маркетплейса: {{ $order->marketplace_order_name ?: '—' }}</p>
        <p>Комментарий: {{ $order->comment ?: '—' }}</p>
        <p>Создан: {{ $order->created_at->format('d.m.Y H:i') }}</p>

        <p>
            <a href="{{ route('orders.index') }}">Назад к списку</a>
            @can('orders.edit')
                · <a href="{{ route('orders.edit', $order) }}">Редактировать</a>
            @endcan
        </p>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;margin-top:0;">Товары</h2>
        <table>
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Кол-во</th>
                    <th>Цена</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format((float) $item->price, 2) }}</td>
                        <td>{{ number_format($item->lineTotal(), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top:1rem;font-weight:600;">Итого: {{ number_format((float) $order->total_amount, 2) }}</p>
    </div>
@endsection
