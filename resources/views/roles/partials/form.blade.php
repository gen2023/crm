@php
    $selected = old('permissions', $role?->permissions->pluck('name')->toArray() ?? []);
@endphp

<label for="name">Название</label>
<input type="text" id="name" name="name" value="{{ old('name', $role?->name) }}" required>

<label for="description">Описание</label>
<textarea id="description" name="description" rows="3">{{ old('description', $role?->description) }}</textarea>

<label>Permissions</label>
<div class="permissions">
    @foreach ($permissions as $permission)
        <label style="font-weight:normal;display:flex;gap:.4rem;align-items:center;">
            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" {{ in_array($permission->name, $selected, true) ? 'checked' : '' }}>
            {{ $permission->name }}
        </label>
    @endforeach
</div>
