@php
    $selectedRoles = old('roles', $user?->roles->pluck('name')->toArray() ?? []);
@endphp

<label for="name">Имя</label>
<input type="text" id="name" name="name" value="{{ old('name', $user?->name) }}" required>

<label for="email">Email</label>
<input type="email" id="email" name="email" value="{{ old('email', $user?->email) }}" required>

<label for="password">Пароль{{ $user ? ' (оставьте пустым, чтобы не менять)' : '' }}</label>
<input type="password" id="password" name="password" {{ $user ? '' : 'required' }}>

<label for="password_confirmation">Подтверждение пароля</label>
<input type="password" id="password_confirmation" name="password_confirmation">

<label for="status">Статус</label>
<select id="status" name="status" required>
    <option value="active" {{ old('status', $user?->status ?? 'active') === 'active' ? 'selected' : '' }}>active</option>
    <option value="inactive" {{ old('status', $user?->status) === 'inactive' ? 'selected' : '' }}>inactive</option>
</select>

<label>Роли</label>
<div class="permissions">
    @foreach ($roles as $role)
        <label style="font-weight:normal;display:flex;gap:.4rem;align-items:center;">
            <input type="checkbox" name="roles[]" value="{{ $role->name }}" {{ in_array($role->name, $selectedRoles, true) ? 'checked' : '' }}>
            {{ $role->name }}
        </label>
    @endforeach
</div>
