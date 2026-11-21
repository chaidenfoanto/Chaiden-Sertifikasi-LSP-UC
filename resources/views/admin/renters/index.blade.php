@extends('layouts.legacy')

@section('title', 'Verify Renters')

@section('content')
<h1 class="h4 mb-3">Verify Renters</h1>

<div class="card shadow-sm">
    <div class="card-body p-4">
        @if ($renters->isEmpty())
            <p class="text-muted mb-0">There are no pending registrations.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Full name</th>
                            <th>NIK</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($renters as $renter)
                            <tr>
                                <td>{{ $renter->full_name }}</td>
                                <td>{{ $renter->nik }}</td>
                                <td>{{ $renter->phone_number }}</td>
                                <td>{{ $renter->email }}</td>
                                <td>{{ $renter->created_at->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <form method="POST" action="{{ route('admin.renters.approve', $renter) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.renters.reject', $renter) }}"
                                              onsubmit="return confirm('Reject this registration?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection