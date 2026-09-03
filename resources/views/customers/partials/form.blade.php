<label for="name">ФИО</label>
<input type="text" id="name" name="name" value="{{ old('name', $customer?->name) }}" required>

<label for="phone">Телефон</label>
<input type="text" id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}" required>

<label for="email">Email</label>
<input type="email" id="email" name="email" value="{{ old('email', $customer?->email) }}">
