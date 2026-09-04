<label for="name">Название</label>
<input type="text" id="name" name="name" value="{{ old('name', $product?->name) }}" required>

<label for="sku">SKU (артикул)</label>
<input type="text" id="sku" name="sku" value="{{ old('sku', $product?->sku) }}" required>

<label for="price">Цена</label>
<input type="text" id="price" name="price" value="{{ old('price', $product?->price) }}" required>

<label for="stock">Остаток на складе</label>
<input type="text" id="stock" name="stock" value="{{ old('stock', $product?->stock ?? 0) }}" required>

<label for="category">Категория</label>
<input type="text" id="category" name="category" value="{{ old('category', $product?->category) }}">

<label for="description">Описание</label>
<textarea id="description" name="description" rows="4">{{ old('description', $product?->description) }}</textarea>

<label for="photo">Фото</label>
<input type="file" id="photo" name="photo" accept="image/*">
@if ($product?->photoUrl())
    <p style="margin-top:.5rem;"><img src="{{ $product->photoUrl() }}" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;"></p>
@endif
