@extends('layouts.app')

@section('title', 'Редактирование роли — CRM')

@section('content')
    <h1>Редактирование роли «{{ $role->name }}»</h1>

    <div class="card">
        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('roles.partials.form', ['role' => $role])
            <button class="btn" type="submit" style="margin-top:1.5rem;">Сохранить</button>
        </form>
    </div>
@endsection
