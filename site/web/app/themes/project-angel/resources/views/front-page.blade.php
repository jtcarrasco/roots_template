@extends('layouts.app')

@section('content')
  <section class="hero bg-gray-50 py-24 text-center">
    <div class="container mx-auto px-4">
      <h1 class="text-5xl font-bold text-gray-900 mb-6">
        Welcome to {!! get_bloginfo('name') !!}
      </h1>
      <p class="text-xl text-gray-600 max-w-2xl mx-auto">
        {!! get_bloginfo('description') !!}
      </p>
    </div>
  </section>

  <section class="content py-16">
    <div class="container mx-auto px-4 max-w-3xl">
      @php(the_content())
    </div>
  </section>
@endsection
