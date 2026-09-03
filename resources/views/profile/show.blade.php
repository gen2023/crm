@extends('layouts.app')

@section('title', 'Профиль — CRM')

@section('content')
    <h1>Профиль</h1>

    <div class="card">
        <p>Имя: {{ $user->name }}</p>
        <p>Email: {{ $user->email }}</p>
        <p>Роли: {{ $user->getRoleNames()->implode(', ') ?: '—' }}</p>
        <p>Последний вход: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</p>
    </div>
@endsection
