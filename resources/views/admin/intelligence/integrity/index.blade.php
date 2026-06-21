@extends('admin.intelligence.layout')
@php($pageTitle = 'Integrity Analytics')
@section('intelligence_content')
@include('admin.intelligence.partials.performance-cards', ['data' => $data])
<div class="rounded-xl border bg-white p-4 shadow-sm mt-4"><h3 class="text-sm font-semibold mb-2">Score Distribution</h3><ul class="text-sm">@foreach($data['score_distribution'] ?? [] as $bucket => $count)<li class="flex justify-between border-b py-1"><span>{{ $bucket }}</span><strong>{{ $count }}</strong></li>@endforeach</ul></div>
@endsection
