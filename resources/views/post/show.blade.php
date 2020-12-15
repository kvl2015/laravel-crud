@extends('layout')

@section('content')
	<h1 style="margin-top:50px;">{{ $data['post']->title}}</h1>

	@include('partials/categories', ['categories' => $data['categories']])
	
	{!! $data['post']->body !!}
	
@endsection