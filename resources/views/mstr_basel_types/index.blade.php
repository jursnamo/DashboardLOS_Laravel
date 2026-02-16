@extends('layouts.dashboard')

@section('title','Basel Types')

@section('content')
    @include('partials.master_crud', [
        'title' => 'Basel Types',
        'routePrefix' => 'basel_types',
        'items' => $items,
        'idField' => 'id',
        'columns' => [
            ['label' => 'Type Name', 'attr' => 'type_name']
        ]
    ])
@endsection
