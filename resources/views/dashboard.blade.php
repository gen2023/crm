@extends('layouts.app')

@section('title', 'Dashboard — CRM')

@section('content')
    <h1>Добро пожаловать, {{ $user->name }}</h1>
    <p>Email: {{ $user->email }}</p>
    <p>Роли: {{ $user->getRoleNames()->implode(', ') ?: '—' }}</p>
    <p>Последний вход: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</p>
@endsection
