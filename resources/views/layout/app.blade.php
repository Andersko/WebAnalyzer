<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="keywords" content="WebAnalyzer">
        <meta name="author" content="Andrej Pavlovic">
        <meta name="description" content="Tool for analyzing web page">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Web Analyzer</title>

        <!-- Tailwind CSS -->
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body class="bg-gray-200">
        <nav class="p-4 bg-white flex">
            <ul class="flex align-items-center">
                <li>
                    <a href="{{ route('home') }}" class="font-bold">Home</a>
                </li>
            </ul>
        </nav>

        <div class="flex justify-center">
            <div class="w-2/3 bg-gray-50 p-10 rounded-lg mt-3 table-row flex justify-content-center">
                @yield('form')
                @yield('content')
            </div>
        </div>
    </body>
</html>
