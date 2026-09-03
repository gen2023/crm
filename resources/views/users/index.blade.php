@extends('layouts.app')

@section('title', 'Users — CRM')

@section('content')
    <h1>Пользователи</h1>

    @can('users.create')
        <p>
            <a class="btn" href="{{ route('users.create') }}">
                <x-icon name="plus" />
                Создать пользователя
            </a>
        </p>
    @endcan

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роли</th>
                    <th>Статус</th>
                    <th>Создан</th>
                    <th>Последний вход</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td>{{ $user->status }}</td>
                        <td>{{ $user->created_at->format('d.m.Y') }}</td>
                        <td>{{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="{{ route('users.show', $user) }}" title="Просмотр">
                                    <x-icon name="eye" />
                                </a>
                                @can('users.edit')
                                    <a class="icon-btn" href="{{ route('users.edit', $user) }}" title="Редактировать">
                                        <x-icon name="pencil" />
                                    </a>
                                @endcan
                                @if (! $user->is(auth()->user()))
                                    @can('users.delete')
                                        @if ($user->status === 'active')
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Деактивировать «{{ $user->name }}»?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn danger" title="Деактивировать">
                                                    <x-icon name="trash" />
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('users.activate', $user) }}">
                                                @csrf
                                                <button type="submit" class="icon-btn success" title="Активировать">
                                                    <x-icon name="check" />
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Пользователей пока нет.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
