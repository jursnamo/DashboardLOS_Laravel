@extends('layouts.dashboard')

@section('title','CIMB Sectors')

@section('content')
    @include('partials.master_crud', [
        'title' => 'CIMB Sectors',
        'routePrefix' => 'cimb_sectors',
        'items' => $items,
        'idField' => 'sectoral_code',
        'columns' => [
            ['label' => 'Code', 'attr' => 'sectoral_code'],
            ['label' => 'Description', 'attr' => 'description']
        ]
    ])
@endsection
