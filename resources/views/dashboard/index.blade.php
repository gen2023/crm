@extends('layouts.app')

@section('title', 'Dashboard — CRM')

@section('content')
    <h1>Dashboard</h1>

    <div class="card">
        <h2 style="font-size:1rem;margin-top:0;">История заходов</h2>
        <table>
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentLogins as $entry)
                    <tr>
                        <td>{{ $entry->actor?->name ?? '—' }}</td>
                        <td>{{ $entry->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Пока нет записей.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
