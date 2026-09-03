@extends('layouts.app')

@section('title', 'Users — CRM')

@section('content')
    <h1>Пользователи</h1>

    @can('users.create')
        <p><a class="btn" href="{{ route('users.create') }}">Создать пользователя</a></p>
    @endcan

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
                        <a href="{{ route('users.show', $user) }}">Просмотр</a>
                        @can('users.edit')
                            · <a href="{{ route('users.edit', $user) }}">Редактировать</a>
                        @endcan
                        @if (! $user->is(auth()->user()))
                            @can('users.delete')
                                @if ($user->status === 'active')
                                    · <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline" onsubmit="return confirm('Деактивировать «{{ $user->name }}»?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:0;font-size:inherit;">Деактивировать</button>
                                    </form>
                                @else
                                    · <form method="POST" action="{{ route('users.activate', $user) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" style="background:none;border:none;color:#15803d;cursor:pointer;padding:0;font-size:inherit;">Активировать</button>
                                    </form>
                                @endif
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Пользователей пока нет.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $users->links() }}
@endsection
