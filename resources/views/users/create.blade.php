@extends('layouts.app')

@section('title', 'Новый пользователь — CRM')

@section('content')
    <h1>Новый пользователь</h1>

    <div class="card">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users.partials.form', ['user' => null])
            <button class="btn" type="submit" style="margin-top:1.5rem;">Создать</button>
        </form>
    </div>
@endsection
