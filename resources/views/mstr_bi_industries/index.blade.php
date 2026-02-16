@extends('layouts.dashboard')

@section('title','BI Industries')

@section('content')
    @include('partials.master_crud', [
        'title' => 'BI Industries',
        'routePrefix' => 'bi_industries',
        'items' => $items,
        'idField' => 'bi_code',
        'columns' => [
            ['label' => 'Code', 'attr' => 'bi_code'],
            ['label' => 'Description', 'attr' => 'description']
        ]
    ])
@endsection
