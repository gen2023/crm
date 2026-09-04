@extends('layouts.app')

@section('title', 'Настройки — CRM')

@section('content')
    <h1>Настройки</h1>

    <div class="card">
        <h2 style="font-size:1rem;margin-top:0;">Карточки на Dashboard</h2>
        <p style="font-size:.85rem;color:#666;margin-top:0;">Какие карточки показывать на дашборде — настройка общая для всех пользователей.</p>

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            @method('PUT')

            <div class="permissions">
                @foreach ($cards as $key => $card)
                    <label style="font-weight:normal;display:flex;gap:.4rem;align-items:center;">
                        <input type="checkbox" name="cards[]" value="{{ $key }}" {{ in_array($key, $enabledCards, true) ? 'checked' : '' }}>
                        {{ $card['label'] }}
                    </label>
                @endforeach
            </div>

            <button class="btn" type="submit" style="margin-top:1.5rem;">Сохранить</button>
        </form>
    </div>
@endsection
