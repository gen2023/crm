@extends('layouts.app')

@section('title', $customer->name.' — CRM')

@section('content')
    <h1>{{ $customer->name }}</h1>

    <div class="card">
        <p>Телефон: {{ $customer->phone }}</p>
        <p>Email: {{ $customer->email ?: '—' }}</p>
        <p>IP: {{ $customer->ip ?: '—' }}</p>
        <p>UTM: {{ $customer->utm ? json_encode($customer->utm, JSON_UNESCAPED_UNICODE) : '—' }}</p>

        <p>
            <a href="{{ route('customers.index') }}">Назад к списку</a>
            @can('customers.edit')
                · <a href="{{ route('customers.edit', $customer) }}">Редактировать</a>
            @endcan
        </p>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;margin-top:0;">Заказы</h2>
        <p>Количество заказов: {{ $customer->orders_count }}</p>
        <p>Сумма заказов: {{ number_format((float) $customer->total_orders_amount, 2) }}</p>
        <p>Дата последнего заказа: {{ $customer->last_order_at?->format('d.m.Y H:i') ?? '—' }}</p>
        <p>Надёжность: {{ $customer->reliability() !== null ? $customer->reliability().'%' : '—' }}</p>
    </div>
@endsection
