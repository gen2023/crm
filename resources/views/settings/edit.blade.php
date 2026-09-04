@extends('layouts.app')

@section('title', 'Настройки — CRM')

@section('content')
    <h1>Настройки</h1>

    <div class="card">
        <div class="tab-nav">
            <button type="button" class="tab-btn active" data-tab="dashboard">Dashboard</button>
        </div>

        <div class="tab-panel" data-tab-panel="dashboard">
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')

                <label>Карточки на Dashboard</label>
                <div class="permissions">
                    @foreach ($cards as $key => $card)
                        <label style="font-weight:normal;display:flex;gap:.4rem;align-items:center;">
                            <input type="checkbox" name="cards[]" value="{{ $key }}" {{ in_array($key, old('cards', $enabledCards), true) ? 'checked' : '' }}>
                            {{ $card['label'] }}
                        </label>
                    @endforeach
                </div>

                <label for="low_stock_threshold">Порог «мало на складе» (штук)</label>
                <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0" style="max-width:160px;"
                       value="{{ old('low_stock_threshold', $lowStockThreshold) }}">

                <button class="btn" type="submit" style="margin-top:1.5rem;">Сохранить</button>
            </form>
        </div>
    </div>
@endsection
