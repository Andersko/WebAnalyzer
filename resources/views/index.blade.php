@extends('layout.app')

@section('form')
            <form action="{{ route('home') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="URL" class="sr-only">Address to be analyzed.</label>
                    <input type="text" id="URL" name="URL" placeholder="https://www.example.com"
                           class="bg-gray-100 border-2 w-full p-4 mb-6 rounded-lg font-medium"
                           value="{{ isset($url) ? $url : '' }}">

                    @if(isset($msg))
                        <div class="text-red-500 mb-4 text-sm">
                            {{ $msg }}
                        </div>
                    @endif
                </div>
                <div class="flex justify-center">
                    <button type="submit" class="@if(!isset($msg)) mt-4 @endif bg-blue-500 text-white px-4 py-2 rounded font-medium">Analyze!</button>
                </div>
            </form>
@endsection

@section('content')
    @if(isset($content))
        <?php
            $names = [
                'gzip' => 'GZIP',
                'h2' => 'HTTP/2.0',
                'status_code' => 'Status code',
                'redirects_status_code' => 'Status codes of redirects',
                'alt_tags_used' => 'Every img tag\'s attribute "alt" is filled',
                'robots' => 'Robots information',
                'page_speed' => 'Google PageSpeed Insights',
                'schema' => 'Schema.org elements'
            ];

            $namesRobots = [
                'robots.txt' => 'File robots.txt:',
                'x-robots-tag' => 'X-Robots-Tag Header:',
                'meta_tag' => 'HTML meta tag:',
                'found' => 'Found:',
                'allowed' => 'Allowed:',
            ];
        ?>

        @foreach($content as $key => $value)
        <div class="bg-gray-100 border-2 p-4 m-6 w-1/3 rounded-lg font-medium">
            <h1>{{ $names[$key] }}</h1>

            @if($key == 'gzip' | $key == 'h2' | $key == 'alt_tags_used')
                @if($value)
                    <h2 class="text-green-500">Yes</h2>
                @else
                    <h2 class="text-red-500">No</h2>
                @endif

            @elseif($key == 'status_code')
                @if($value < 300)
                    <h2 class="text-green-500">{{ $value }}</h2>
                @elseif($value >= 400)
                    <h2 class="text-red-500">{{ $value }}</h2>
                @else
                    <h2 class="text-yellow-500">{{ $value }}</h2>
                @endif

            @elseif($key == 'redirects_status_code')
                @if($value == false)
                    <h2 class="text-yellow-500">No redirects</h2>
                @else
                    @foreach($value as $item)
                        <h2 class="text-yellow-500">{{ $item }}</h2>
                    @endforeach
                @endif

            @elseif($key == 'robots')
                @foreach($value as $key => $item)
                    <br>
                    <h2>{{ $namesRobots[$key] }}</h2>

                    @foreach($item as $key => $value)
                        @if($value)
                            <h3>{{ $namesRobots[$key] }} <span class="text-green-500"> Yes</span></h3>
                        @else
                            <h3>{{ $namesRobots[$key] }} <span class="text-red-500"> No</span></h3>

                            @if(!$value)
                                {{-- If not found, don't show allowed info --}}
                                @break
                            @endif
                        @endif
                    @endforeach
                @endforeach

            @elseif($key == 'page_speed')
                @if($value == null)
                    <h2 class="text-red-500">
                        Error: request to Google PageSpeed probably wasn't successful.
                        (Maybe too much requests from this IP)
                    </h2>
                @else
                    <h2>First 3 lines:</h2>

                    <div class="text-xs font-normal">
                        <br>
                        {{ explode("\n", $value)[0] }}
                        <br>
                        {{ explode("\n", $value)[1] }}
                        <br>
                        {{ explode("\n", $value)[2] }}
                    </div>
                @endif

            @elseif($key == 'schema')
                @if(empty($value))
                    <h2 class="text-yellow-500">None</h2>
                @else
                    @foreach($value as $item)
                        <h2 class="text-yellow-500">{{ $item }}</h2>
                    @endforeach
                @endif
            @endif
        </div>
        @endforeach
    @endif
@endsection
