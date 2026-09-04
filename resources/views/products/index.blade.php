@extends('layouts.app')

@section('title', 'Products — CRM')

@section('content')
    <h1>Товары</h1>

    @can('products.create')
        <p>
            <a class="btn" href="{{ route('products.create') }}">
                <x-icon name="plus" />
                Добавить товар
            </a>
        </p>
    @endcan

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Название</th>
                    <th>SKU</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Остаток</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            @if ($product->photoUrl())
                                <img src="{{ $product->photoUrl() }}" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;">
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->category ?: '—' }}</td>
                        <td>{{ number_format((float) $product->price, 2) }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="{{ route('products.show', $product) }}" title="Просмотр">
                                    <x-icon name="eye" />
                                </a>
                                @can('products.edit')
                                    <a class="icon-btn" href="{{ route('products.edit', $product) }}" title="Редактировать">
                                        <x-icon name="pencil" />
                                    </a>
                                @endcan
                                @can('products.delete')
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Удалить товар «{{ $product->name }}»?');">
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
                    <tr><td colspan="7">Товаров пока нет.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
@endsection
