@extends('layouts.app')

@section('title', 'Roles — CRM')

@section('content')
    <h1>Роли</h1>

    @can('roles.create')
        <p>
            <a class="btn" href="{{ route('roles.create') }}">
                <x-icon name="plus" />
                Создать роль
            </a>
        </p>
    @endcan

    <div class="card">
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
                            <div class="row-actions">
                                <a class="icon-btn" href="{{ route('roles.show', $role) }}" title="Просмотр">
                                    <x-icon name="eye" />
                                </a>
                                @can('roles.edit')
                                    <a class="icon-btn" href="{{ route('roles.edit', $role) }}" title="Редактировать">
                                        <x-icon name="pencil" />
                                    </a>
                                @endcan
                                @can('roles.delete')
                                    <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Удалить роль «{{ $role->name }}»?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn danger" title="Удалить">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Ролей пока нет.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $roles->links() }}
@endsection
