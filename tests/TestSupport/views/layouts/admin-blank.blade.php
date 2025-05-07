<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@section('title', 'Admin')

<body class="bg-white">
@section('content')
@show

@stack('scripts')
</body>
</html>
