@extends('layouts.app')

@section('title', 'Новый товар — CRM')

@section('content')
    <h1>Новый товар</h1>

    <div class="card">
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf
            @include('products.partials.form', ['product' => null])
            <button class="btn" type="submit">Создать</button>
        </form>
    </div>
@endsection
