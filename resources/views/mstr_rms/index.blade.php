@extends('layouts.dashboard')

@section('title','Relationship Managers')

@section('content')
    @include('partials.master_crud', [
        'title' => 'Relationship Managers',
        'routePrefix' => 'rms',
        'items' => $items,
        'idField' => 'rm_code',
        'columns' => [
            ['label' => 'Code', 'attr' => 'rm_code'],
            ['label' => 'Name', 'attr' => 'rm_name'],
            ['label' => 'Unit', 'attr' => 'unit_name']
        ]
    ])
@endsection
