@props(['name', 'id' => null])

@php $id = $id ?? $name; @endphp

<div class="password-field">
    <input type="password" id="{{ $id }}" name="{{ $name }}" {{ $attributes }}>
    <button type="button" class="password-toggle" data-target="{{ $id }}" tabindex="-1" aria-label="Показать пароль">
        <x-icon name="eye" />
    </button>
</div>
