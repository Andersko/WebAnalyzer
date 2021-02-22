<?php

namespace App\Analyzer;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

class AnalyzerClient extends Client
{
    private $url;
    private $response;
    private $rawHTML;

    function __construct($url, ...$args)
    {
        // Parent construct call
        call_user_func_array(['parent', '__construct'], $args);

        $this->url = $url;
    }

    function analyze()
    {
        $headers = [
            'Accept-Encoding' => 'gzip',
        ];

        $request = new Request('GET', $this->url, $headers);

        try {
            $this->response = $this->send($request->withProtocolVersion('2')); // force HTTP/2.0
        }
        catch (GuzzleException $e) {
            return null;
        }

        $this->rawHTML = $this->response->getBody()->getContents();

        return [
            'gzip' => $this->supportsGzip(),
            'h2' => $this->supportsH2(),
            'status_code' => $this->statusCode(),
            'redirects_status_code' => $this->redirectsStatusCode(),
            'alt_tags_used' => $this->allAltTagsApplied(),
            'robots' => $this->robotsAllowed(),
            'page_speed' => $this->googlePageSpeedInsights(),
            'schema' => $this->schema(),
        ];
    }

    private function supportsGzip()
    {
        if ( $this->response->hasHeader('content-encoding') || $this->response->hasHeader('x-encoded-content-encoding') )
        {
            $header = $this->response->getHeader('content-encoding');

            if ( empty($header) ) {
                $header = $this->response->getHeader('x-encoded-content-encoding');
            }

            foreach ($header as $line) {
                foreach ( explode(',', $line) as $word )
                {
                    $word = explode(';', $word)[0];

                    if (strcasecmp($word, 'gzip') == 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function supportsH2()
    {
        if ( strcasecmp($this->response->getProtocolVersion(), '2') == 0 ) {
            return true;
        }

        return false;
    }

    private function statusCode()
    {
        return $this->response->getStatusCode();
    }

    private function redirectsStatusCode()
    {
        $arr = array_map('trim', explode(',', $this->response->getHeaderLine('X-Guzzle-Redirect-Status-History')));

        if ( $arr[0] === "" ) {
            // No redirects
            return false;
        }

        return $arr;
    }

    private function allAltTagsApplied()
    {
        return preg_match('/<img(?![^>]*\balt=)[^>]*?>/', $this->rawHTML) === 1 ? false : true;
    }

    private function robotsAllowed()
    {
        $data = [
            'robots.txt' => [
                'found' => false,
                'allowed' => false,
            ],
            'x-robots-tag' => [
                'found' => false,
                'allowed' => false,
            ],
            'meta_tag' => [
                'found' => false,
                'allowed' => false,
            ],
        ];

        $host = parse_url($this->url)['host'];

        try {
            $responseRobots = $this->get($host . '/robots.txt');

            if ($responseRobots->getStatusCode() < 400) {
                $data['robots.txt']['found'] = true;

                $responseRobotsContents = $responseRobots->getBody()->getContents();

                if (preg_match('/^User-agent: \*\nDisallow: \/\n/', $responseRobotsContents) === 0) {
                    $data['robots.txt']['allowed'] = true;
                }
            }
        }
        catch (GuzzleException $e) {
            // pass
        }

        if ( $this->response->hasHeader('x-robots-tag') )
        {
            $data['x-robots-tag']['found'] = true;

            $headerValue = $this->response->getHeaderLine('x-robots-tag');

            if ( preg_match('/noindex/', $headerValue) != 1 ) {
                $data['x-robots-tag']['allowed'] = true;
            }
        }

        if ( preg_match('/^<meta([ \n\t])+name="robots"/', $this->rawHTML) == 1 )
        {
            $data['meta_tag']['found'] = true;

            if ( preg_match('/^<meta([ \n\t])+name="robots"([ \n\t])+content="noindex"([ \n\t])+\/>$/', $this->rawHTML) === 0 ) {
                $data['meta_tag']['allowed'] = true;
            }
        }

        return $data;
    }

    private function googlePageSpeedInsights()
    {
        try {
            $response = $this->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=' . $this->url);
        }
        catch (GuzzleException $e) {
            return null;
        }

        return $response->getBody()->getContents();
    }

    /**
     * Detects only schemas from source code, not injected asynchronously.
     *
     * @return array|null
     * Array of schema elements if page has some, null otherwise.
     */
    private function schema()
    {
        preg_match_all('#itemtype="https?://schema\.org([a-zA-Z]|/|\d)*"#', $this->rawHTML, $matches);

        if (!empty($matches[0]) && $matches[0][0] != '') {

            $schemas = [];

            foreach ($matches[0] as $value) {
                preg_match('#://schema\.org.*#', $value, $value);
                $value = substr($value[0], 13, -1);
                array_push($schemas, $value);
            }

            return array_unique($schemas);
        }
        else {
            return null;
        }
    }

    function setUrl ($url)
    {
        $this->url = $url;
    }
}
