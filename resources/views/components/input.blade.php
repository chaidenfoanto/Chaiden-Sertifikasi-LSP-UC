@props(['name', 'label', 'type' => 'text'])
<div>
    <label for="{{ $name }}" class="field-label">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" required
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        {{ $attributes->class([
            'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-black focus:ring-2 focus:ring-black/10',
            'border-neutral-300' => ! $errors->has($name),
            'border-red-600' => $errors->has($name),
        ]) }}>
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>