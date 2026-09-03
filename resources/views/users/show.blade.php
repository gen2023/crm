@extends('layouts.app')

@section('title', $user->name.' — CRM')

@section('content')
    <h1>{{ $user->name }}</h1>

    <div class="card">
        <p>Email: {{ $user->email }}</p>
        <p>Статус: {{ $user->status }}</p>
        <p>Роли: {{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</p>
        <p>Создан: {{ $user->created_at->format('d.m.Y H:i') }}</p>
        <p>Последний вход: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</p>

        <p>
            <a href="{{ route('users.index') }}">Назад к списку</a>
            @can('users.edit')
                · <a href="{{ route('users.edit', $user) }}">Редактировать</a>
            @endcan
        </p>
    </div>
@endsection
