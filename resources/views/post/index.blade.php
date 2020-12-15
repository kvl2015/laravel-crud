@extends('layout')

@section('content')
	<h1 style="margin-top:50px;">Review all {{ isset($data['category']) ? '«'.$data['category']->name.'» ' : ''}}Post</h1>
	@include('partials/categories', ['categories' => $data['categories']])
	
	@foreach($data['posts'] as $post)
		<div class="card col-12" style="margin-bottom: 20px;">
			<img src="{{ asset('storage/'.$post->image) }}" class="card-img-top" alt="...">
		  	<div class="card-body">
		    	<h5 class="card-title">{{ $post->title }}</h5>
		    	<p class="card-text">{!! $post->excerpt !!}</p>
		    	<a href="{{ url('/post', $post->slug )}}" class="btn btn-primary">Read more</a>
		  	</div>
		</div>
	@endforeach
@endsection