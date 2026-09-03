@extends('layouts.app')

@section('title', 'Roles — CRM')

@section('content')
    <h1>Роли</h1>

    @can('roles.create')
        <p><a class="btn" href="{{ route('roles.create') }}">Создать роль</a></p>
    @endcan

    <table>
        <thead>
            <tr>
                <th>Название</th>
                <th>Описание</th>
                <th>Permissions</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->description }}</td>
                    <td>{{ $role->permissions_count }}</td>
                    <td>
                        <a href="{{ route('roles.show', $role) }}">Просмотр</a>
                        @can('roles.edit')
                            · <a href="{{ route('roles.edit', $role) }}">Редактировать</a>
                        @endcan
                        @can('roles.delete')
                            · <form method="POST" action="{{ route('roles.destroy', $role) }}" style="display:inline" onsubmit="return confirm('Удалить роль «{{ $role->name }}»?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:0;font-size:inherit;">Удалить</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Ролей пока нет.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $roles->links() }}
@endsection
