@extends('layouts.app')

@section('title', $product->name.' — CRM')

@section('content')
    <h1>{{ $product->name }}</h1>

    <div class="card">
        @if ($product->photoUrl())
            <p><img src="{{ $product->photoUrl() }}" alt="" style="width:160px;height:160px;object-fit:cover;border-radius:8px;"></p>
        @endif
        <p>SKU: {{ $product->sku }}</p>
        <p>Категория: {{ $product->category ?: '—' }}</p>
        <p>Цена: {{ number_format((float) $product->price, 2) }}</p>
        <p>Остаток: {{ $product->stock }}</p>
        <p>Описание: {{ $product->description ?: '—' }}</p>

        <p>
            <a href="{{ route('products.index') }}">Назад к списку</a>
            @can('products.edit')
                · <a href="{{ route('products.edit', $product) }}">Редактировать</a>
            @endcan
        </p>
    </div>
@endsection
