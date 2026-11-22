{{-- Lists every validation error of the request at the top of a page. --}}
@if ($errors->any())
    <div role="alert" class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif