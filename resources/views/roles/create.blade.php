@extends('layouts.app')

@section('title', 'Новая роль — CRM')

@section('content')
    <h1>Новая роль</h1>

    <div class="card">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            @include('roles.partials.form', ['role' => null])
            <button class="btn" type="submit">Создать</button>
        </form>
    </div>
@endsection
