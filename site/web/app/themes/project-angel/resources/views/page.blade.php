@extends('layouts.app')

@section('content')
  <div class="container mx-auto px-4 py-16 max-w-3xl">
    @while(have_posts()) @php(the_post())
      <article @php(post_class('prose prose-lg max-w-none'))>
        <h1 class="text-4xl font-bold text-gray-900 mb-8">
          {!! get_the_title() !!}
        </h1>
        <div class="entry-content">
          @includeFirst(['partials.content-page', 'partials.content'])
        </div>
      </article>
    @endwhile
  </div>
@endsection
