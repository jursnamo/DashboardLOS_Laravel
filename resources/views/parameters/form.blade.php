@extends('layouts.dashboard')

@section('title', 'Parameter Form')

@section('content')
<div class="p-3">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $item ? 'Edit' : 'Create' }} {{ ucfirst($type) }}</h5>
            @if($item)
                <form method="POST" action="{{ route('parameters.update', [$type,$item->getKey()]) }}">
                @method('PUT')
            @else
                <form method="POST" action="{{ route('parameters.store', $type) }}">
            @endif
                @csrf
                @if(!empty($fields) && is_array($fields))
                    @foreach($fields as $f)
                        <div class="form-group mb-3">
                            <label>{{ ucwords(str_replace(['_'], ' ', $f)) }}</label>
                            <input name="{{ $f }}" class="form-control" value="{{ old($f, $item->{$f} ?? '') }}" {{ $f === 'name' || $f === 'branch_name' ? 'required' : '' }}>
                        </div>
                    @endforeach
                @else
                    <div class="form-group mb-3">
                        <label>Name</label>
                        <input name="name" class="form-control" value="{{ old('name', $item->name ?? $item->branch_name ?? $item->rm_name ?? $item->description ?? '') }}" required>
                    </div>
                @endif
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('parameters.index', $type) }}" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
