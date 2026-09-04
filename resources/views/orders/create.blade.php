@extends('layouts.app')

@section('title', 'Новый заказ — CRM')

@section('content')
    <h1>Новый заказ</h1>

    <div class="card">
        <form method="POST" action="{{ route('orders.store') }}">
            @csrf
            @include('orders.partials.form', ['order' => null])
            <button class="btn" type="submit" style="margin-top:1.5rem;">Создать</button>
        </form>
    </div>
@endsection
