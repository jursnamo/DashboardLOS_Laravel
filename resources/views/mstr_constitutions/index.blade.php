@extends('layouts.dashboard')

@section('title','Constitutions')

@section('content')
    @include('partials.master_crud', [
        'title' => 'Constitutions',
        'routePrefix' => 'constitutions',
        'items' => $items,
        'idField' => 'id',
        'columns' => [
            ['label' => 'Name', 'attr' => 'name']
        ]
    ])
@endsection
