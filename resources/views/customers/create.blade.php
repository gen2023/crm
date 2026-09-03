@extends('layouts.app')

@section('title', 'Новый клиент — CRM')

@section('content')
    <h1>Новый клиент</h1>

    <div class="card">
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @include('customers.partials.form', ['customer' => null])
            <button class="btn" type="submit">Создать</button>
        </form>
    </div>
@endsection
