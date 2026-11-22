@extends('layouts.dashboard')

@section('title', 'Verify Renters')
@section('heading', 'Verify Renters')

@section('body')
<x-card>
    @if ($renters->isEmpty())
        <p class="text-neutral-500">There are no pending registrations.</p>
    @else
        <x-table>
            <thead>
                <tr>
                    <th>Full name</th>
                    <th>NIK</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th class="text-right">Action</th>
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
                        <td>
                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('admin.renters.approve', $renter) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="success" size="sm">Approve</x-button>
                                </form>
                                <form method="POST" action="{{ route('admin.renters.reject', $renter) }}"
                                      onsubmit="return confirm('Reject this registration?')">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="outline-danger" size="sm">Reject</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-card>
@endsection