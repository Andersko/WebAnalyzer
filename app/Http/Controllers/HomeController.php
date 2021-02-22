<?php

namespace App\Http\Controllers;

use App\Analyzer\AnalyzerClient as Client;
use GuzzleHttp\RequestOptions;

class HomeController extends Controller
{
    function index ()
    {
        return view('index');
    }

    function analyze ()
    {
        $url = request()->get('URL');

        if ( filter_var($url, FILTER_VALIDATE_URL) === false ) {
            return view('index')->with('msg', 'Please provide valid URL.')->with('url', $url);
        }

        $client = new Client($url, [
            RequestOptions::TIMEOUT => 10, // seconds
            RequestOptions::ALLOW_REDIRECTS => [
                'max' => 10,
                'track_redirects' => true,
            ]
        ]);

        $data = $client->analyze();

        return view('index')->with('content', $data)->with('url', $url);
    }
}
