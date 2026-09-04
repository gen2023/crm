@php
    $existingItems = old('items', $order?->items->map(fn ($item) => [
        'product_id' => $item->product_id,
        'quantity' => $item->quantity,
    ])->toArray() ?: [['product_id' => '', 'quantity' => 1]]);
@endphp

<label for="customer_id">Клиент</label>
<select id="customer_id" name="customer_id" required>
    <option value="">— выберите —</option>
    @foreach ($customers as $customer)
        <option value="{{ $customer->id }}" {{ (string) old('customer_id', $order?->customer_id) === (string) $customer->id ? 'selected' : '' }}>
            {{ $customer->name }} ({{ $customer->phone }})
        </option>
    @endforeach
</select>

<label for="status">Статус</label>
<select id="status" name="status" required>
    @foreach ($statuses as $value => $label)
        <option value="{{ $value }}" {{ old('status', $order?->status ?? 'new') === $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>

<label for="source">Источник</label>
<input type="text" id="source" name="source" value="{{ old('source', $order?->source) }}">

<label for="delivery_address">Адрес / способ доставки</label>
<input type="text" id="delivery_address" name="delivery_address" value="{{ old('delivery_address', $order?->delivery_address) }}">

<label for="payment_method">Способ оплаты</label>
<input type="text" id="payment_method" name="payment_method" value="{{ old('payment_method', $order?->payment_method) }}">

<label for="marketplace_order_id">ID заказа маркетплейса</label>
<input type="text" id="marketplace_order_id" name="marketplace_order_id" value="{{ old('marketplace_order_id', $order?->marketplace_order_id) }}">

<label for="marketplace_order_name">Название заказа маркетплейса</label>
<input type="text" id="marketplace_order_name" name="marketplace_order_name" value="{{ old('marketplace_order_name', $order?->marketplace_order_name) }}">

<label for="comment">Комментарий</label>
<textarea id="comment" name="comment" rows="3">{{ old('comment', $order?->comment) }}</textarea>

<label>Товары</label>
<div id="order-items">
    @foreach ($existingItems as $index => $item)
        <div class="order-item-row" style="display:flex;gap:.5rem;margin-top:.5rem;align-items:center;">
            <select name="items[{{ $index }}][product_id]" required style="flex:2;">
                <option value="">— товар —</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" {{ (string) ($item['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                        {{ $product->name }} ({{ number_format((float) $product->price, 2) }})
                    </option>
                @endforeach
            </select>
            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" min="1" required style="flex:1;">
            <button type="button" class="icon-btn danger remove-item-row" title="Убрать"><x-icon name="trash" /></button>
        </div>
    @endforeach
</div>
<button type="button" class="btn" id="add-item-row" style="margin-top:.75rem;">
    <x-icon name="plus" />
    Добавить товар
</button>

<template id="order-item-template">
    <div class="order-item-row" style="display:flex;gap:.5rem;margin-top:.5rem;align-items:center;">
        <select name="items[__INDEX__][product_id]" required style="flex:2;">
            <option value="">— товар —</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }} ({{ number_format((float) $product->price, 2) }})</option>
            @endforeach
        </select>
        <input type="number" name="items[__INDEX__][quantity]" value="1" min="1" required style="flex:1;">
        <button type="button" class="icon-btn danger remove-item-row" title="Убрать"><x-icon name="trash" /></button>
    </div>
</template>

<script>
    (function () {
        var container = document.getElementById('order-items');
        var addBtn = document.getElementById('add-item-row');
        var template = document.getElementById('order-item-template');
        var index = {{ count($existingItems) }};

        addBtn.addEventListener('click', function () {
            var html = template.innerHTML.split('__INDEX__').join(index++);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            container.appendChild(wrapper.firstChild);
        });

        container.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-item-row');
            if (btn && container.children.length > 1) {
                btn.closest('.order-item-row').remove();
            }
        });
    })();
</script>
