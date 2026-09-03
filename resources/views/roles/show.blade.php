@extends('layouts.app')

@section('title', $role->name.' — CRM')

@section('content')
    <h1>Роль «{{ $role->name }}»</h1>

    <div class="card">
        <p>{{ $role->description }}</p>

        <h2 style="font-size:1rem;">Permissions</h2>
        <ul>
            @forelse ($role->permissions as $permission)
                <li>{{ $permission->name }}</li>
            @empty
                <li>Нет назначенных permissions.</li>
            @endforelse
        </ul>

        <p>
            <a href="{{ route('roles.index') }}">Назад к списку</a>
            @can('roles.edit')
                · <a href="{{ route('roles.edit', $role) }}">Редактировать</a>
            @endcan
        </p>
    </div>
@endsection
