@extends('layouts.app')

@section('title', 'Редактирование клиента — CRM')

@section('content')
    <h1>Редактирование «{{ $customer->name }}»</h1>

    <div class="card">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('customers.partials.form', ['customer' => $customer])
            <button class="btn" type="submit" style="margin-top:1.5rem;">Сохранить</button>
        </form>
    </div>
@endsection
