@props(['except' => []])
@foreach (request()->only(['q', 'vendor_id', 'group', 'from', 'to', 'method', 'status', 'due_from', 'due_to', 'sort', 'receipt_status', 'assignment']) as $key => $value)
    @if (!in_array($key, $except, true) && (is_scalar($value) || $value === null))
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endif
@endforeach
