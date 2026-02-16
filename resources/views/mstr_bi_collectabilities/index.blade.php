@extends('layouts.dashboard')

@section('title','BI Collectabilities')

@section('content')
    @include('partials.master_crud', [
        'title' => 'BI Collectabilities',
        'routePrefix' => 'bi_collectabilities',
        'items' => $items,
        'idField' => 'id',
        'columns' => [
            ['label' => 'Status', 'attr' => 'status']
        ]
    ])
@endsection
