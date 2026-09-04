@extends('layouts.app')

@section('title', 'Редактирование пользователя — CRM')

@section('content')
    <h1>Редактирование «{{ $user->name }}»</h1>

    <div class="card">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users.partials.form', ['user' => $user])
            <button class="btn" type="submit" style="margin-top:1.5rem;">Сохранить</button>
        </form>
    </div>
@endsection
