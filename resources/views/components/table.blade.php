{{-- Scrollable table wrapper. Cell styling comes from the .data-table class in app.css. --}}
<div class="overflow-x-auto">
    <table {{ $attributes->class('data-table') }}>{{ $slot }}</table>
</div>