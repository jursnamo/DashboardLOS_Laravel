@extends('layouts.dashboard')

@section('title','Economy Sectors')

@section('content')
    @include('partials.master_crud', [
        'title' => 'Economy Sectors',
        'routePrefix' => 'economy_sectors',
        'items' => $items,
        'idField' => 'id',
        'columns' => [
            ['label' => 'Sector Name', 'attr' => 'sector_name']
        ]
    ])
@endsection
