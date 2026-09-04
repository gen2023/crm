@extends('layouts.app')

@section('title', 'Редактирование заказа — CRM')

@section('content')
    <h1>Редактирование заказа #{{ $order->id }}</h1>

    <div class="card">
        <form method="POST" action="{{ route('orders.update', $order) }}">
            @csrf
            @method('PUT')
            @include('orders.partials.form', ['order' => $order])
            <button class="btn" type="submit" style="margin-top:1.5rem;">Сохранить</button>
        </form>
    </div>
@endsection
