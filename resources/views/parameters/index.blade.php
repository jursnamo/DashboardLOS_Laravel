@extends('layouts.dashboard')

@section('title', 'Parameters')

@section('content')
<div class="p-3">
	<div class="card">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h5 class="card-title mb-0">Parameter: {{ ucfirst($type) }}</h5>
				<a href="{{ route('parameters.create', $type) }}" class="btn btn-primary">Create New</a>
			</div>

			@if(session('success'))
				<div class="alert alert-success">{{ session('success') }}</div>
			@endif

			<div class="table-responsive">
				<table class="table table-striped table-sm">
					<thead>
						<tr>
							<th>#</th>
							<th>Name</th>
							<th class="text-end">Actions</th>
						</tr>
					</thead>
					<tbody>
						@foreach($items as $item)
						<tr>
							<td>{{ $loop->iteration + ($items->currentPage()-1)*$items->perPage() }}</td>
							<td>{{ $item->name ?? $item->branch_name ?? $item->rm_name ?? $item->description ?? ($item->sector_name ?? ($item->type_name ?? '-')) }}</td>
							<td class="text-end">
								<a href="{{ route('parameters.edit', [$type,$item->getKey()]) }}" class="btn btn-sm btn-warning">Edit</a>
								<form action="{{ route('parameters.destroy', [$type,$item->getKey()]) }}" method="POST" style="display:inline-block">
									@csrf
									@method('DELETE')
									<button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
								</form>
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			<div class="mt-3">{{ $items->links() }}</div>
		</div>
	</div>
</div>
@endsection



