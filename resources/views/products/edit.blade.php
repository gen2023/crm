@extends('layouts.app')

@section('title', 'Редактирование товара — CRM')

@section('content')
    <h1>Редактирование «{{ $product->name }}»</h1>

    <div class="card">
        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('products.partials.form', ['product' => $product])
            <button class="btn" type="submit" style="margin-top:1.5rem;">Сохранить</button>
        </form>
    </div>
@endsection
