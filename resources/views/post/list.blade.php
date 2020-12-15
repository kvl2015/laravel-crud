@extends('layout')

@section('content')
	<h1 style="margin-top:50px;">Review my Post</h1>
	@include('partials/categories', ['categories' => $data['categories']])
	
	<a href="{{ url('/post/create') }}" class="btn btn-info">Create</a><br/><br/>
	@if (count($data['posts']))
		@foreach($data['posts'] as $post)
		<div class="card col-12" style="margin-bottom: 20px;">
			  <div class="card-body">
			    	<h5 class="card-title">{{ $post->title }}</h5>
			    	<a href="{{ url('/post/edit', $post->id) }}" class="btn btn-success">Edit</a>
			    	<a href="{{ url('/post/delete', $post->id) }}" class="btn btn-danger">Delete</a>
			  </div>
		</div>
		@endforeach
	@else
		<p>No post yet</p>
	@endif
	<div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
	
@endsection