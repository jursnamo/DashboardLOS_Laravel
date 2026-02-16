@extends('layouts.dashboard')

@section('title','Branches')

@section('content')
   @include('partials.master_crud', [
        'title' => 'Branches',
        'routePrefix' => 'branches',
        'items' => $items,
        'idField' => 'branch_id',
        'columns' => [
            ['label' => 'Branch Name', 'attr' => 'branch_name'],
            ['label' => 'Area / Region', 'attr' => 'area_region']
        ]
    ])
@endsection
