@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm font-medium text-success dark:text-success-dark']) }}>
        {{ $status }}
    </div>
@endif
