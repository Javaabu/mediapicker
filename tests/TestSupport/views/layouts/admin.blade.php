<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@section('title', 'Admin')

<body>
<main class="main">


    <!-- Contents -->
    <section class="content">
        <header class="content__title">
            <h1>@yield('page-title')</h1>
            @yield('page-subheading')
            {{--<small>Sub heading contents</small>--}}
            @yield('model-actions')
        </header>

        @section('content')
        @show
    </section>
</main>

@stack('scripts')
</body>
</html>
