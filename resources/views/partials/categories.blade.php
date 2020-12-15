<ul class="list-group list-group-horizontal" style="margin-bottom: 50px;">
	@foreach($data['categories'] as $category)
		<li class="list-group-item"><a href="{{ url('/category', $category->id) }}">{{ $category->name }}</a></li>
	@endforeach
</ul>
