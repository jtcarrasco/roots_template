<header class="site-header bg-white shadow-sm py-4">
  <div class="container mx-auto px-4 flex items-center justify-between">
    <a href="{{ home_url('/') }}" class="site-logo text-xl font-bold text-gray-900">
      {!! $siteName !!}
    </a>

    @if (has_nav_menu('primary_navigation'))
      <nav class="site-nav" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
        {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'flex gap-6', 'container' => false, 'echo' => false]) !!}
      </nav>
    @endif
  </div>
</header>
