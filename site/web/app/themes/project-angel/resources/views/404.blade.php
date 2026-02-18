@extends('layouts.app')

@section('content')
  <div class="container mx-auto px-4 py-32 text-center">
    <h1 class="text-8xl font-bold text-gray-200 mb-4">404</h1>
    <h2 class="text-3xl font-semibold text-gray-800 mb-4">Page Not Found</h2>
    <p class="text-gray-500 mb-8">
      Sorry, the page you're looking for doesn't exist or has been moved.
    </p>
    <a href="{{ home_url('/') }}"
       class="inline-block bg-gray-900 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
      Back to Home
    </a>
  </div>
@endsection
