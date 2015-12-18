<?php

/*
 * This file is part of the Ariadne Component Library.
 *
 * (c) Muze <info@muze.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace arc\http;

class ClientCurl implements Client
{
    private $options = array('headers' => array());

    public $responseHeaders = null;
    public $requestHeaders = null;

    protected function parseRequestURL($url)
    {
        $components = parse_url( $url );

        return isset($components['query']) ? $components['query'] : false;
    }

    protected function mergeOptions()
    {
        $args = func_get_args();
        array_unshift( $args, $this->options );

        $res = call_user_func_array( 'array_merge', $args );
        return $res;
    }

    protected function buildURL($url, $request)
    {
        if (is_array( $request ) || $request instanceof \ArrayObject) {
            $request = http_build_query( (array) $request );
        }
        $request = (string) $request; // to force a \ar\connect\url\urlQuery to a possibly empty string.
        if ($request) {
            if (strpos( (string) $url, '?' ) === false) {
                $request = '?' . $request;
            } else {
                $request = '&' . $request;
            }
            $url .= $request;
        }

        return $url;
    }

    protected function streamToCurlOptions($options, $curloptions) {
        /* FIXME: parse stream httpcontext options
            - method -- not needed
            - header -- done
            - user_agent -- done
            - content -- not needed
            - proxy -- todo
            - request_fulluri -- todo
            - follow_location -- done
            - max_redirects -- done
            - protocol_version -- done
            - timeout -- done
            - ignore_errors -- done
        */
        if (count($options['headers']))
        {
            $curloptions[CURLOPT_HTTPHEADER] = $options['headers'];
        }

        if (isset($options['user_agent'])){
            $curloptions[CURLOPT_USERAGENT] = $options['user_agent'];
        }

        /*
            TODO: parse proxy url in seperate settings
            URI specifying address of proxy server. (e.g. tcp://proxy.example.com:5100).
            what kind of proxies does the stream api support?
        if (isset($options['proxy'])){
            $curloptions[] = $options['proxy'];
        }
         */

        if (isset($options['follow_location']))
        {
            $curloptions[CURLOPT_FOLLOWLOCATION] = $options['follow_location'];
        }
        if (isset($options['max_redirects']))
        {
            $curloptions[CURLOPT_MAXREDIRS] = $options['max_redirects'];
        }
        if (isset($options['protocol_version']))
        {
            if ($options['protocol_version'] == 1.0)
            {
                $curloptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
            } else if ($options['protocol_version'] == 1.1)
            {
                $curloptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
            }
        }
        if (isset($options['timeout']))
        {
            $curloptions[CURLOPT_TIMEOUT_MS] = (int) (1000 * $options['timeout']);
        }
        if (isset($options['ignore_errors']))
        {
            $curloptions[CURLOPT_FAILONERROR] = !$options['ignore_errors'];
        }

        return $curloptions;
    }

    public function request( $type, $url, $request = null, $options = array() )
    {
        $this->responseHeaders = null;
        $this->requestHeaders = null;

        // TODO: set https validation to on
        // TODO: discover caert.pem if not set

        $curloptions = array(
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $type, // GET POST PUT PATCH DELETE HEAD OPTIONS
        );

        if ($type == 'GET') {
            if ($request) {
                $url = $this->buildURL( $url, $request );
                $request = '';
            }
        } else {
            $curloptions[CURLOPT_CUSTOMREQUEST] = $type;
            if($request) {
                $curloptions[CURlPOSTFIELDS] = $request;
            }
        }
        $curloptions[CURLOPT_URL] = $url;

        $options = $this->mergeOptions( array(
            'method' => $type,
            'content' => $request
        ), $options );

        if (isset($options['header'])) {
            $options['headers'] =  $options['headers'] + explode('\r\n', $options['header']);
            $options['header'] = false;
        }

        $curloptions = $this->streamToCurlOptions($options, $curloptions);

        // Compose querry
        $ch = curl_init();
        curl_setopt_array( $ch, $curloptions);

        $result = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $result = substr($result, $header_size);

        $headers = explode("\n", $header);
        array_walk($headers, function(&$value) {
            $value = rtrim($value, "\r");
        });
        $this->responseHeaders = $headers;
        $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $this->requestHeaders = rtrim($requestHeaders,"\r\n")."\r\n";

        return $result;
    }

    public function __construct( $options = array() )
    {
        $this->options = $options + $this->options;
    }

    public function get( $url, $request = null, $options = array() )
    {
        if (!isset($request)) {
            $request = $this->parseRequestURL($url);
        }

        return $this->request( 'GET', $url, $request, $options );
    }

    public function post( $url, $request = null, $options = array() )
    {
        return $this->request( 'POST', $url, $request, $options );
    }

    public function put( $url, $request = null, $options = array() )
    {
        return $this->request( 'PUT', $url, $request, $options );
    }

    public function delete( $url, $request = null, $options = array() )
    {
        return $this->request( 'DELETE', $url, $request, $options );
    }

    public function headers($headers)
    {
        if (!isset($this->options['headers'])) {
            $this->options['headers'] = array();
        }
        if (!is_array($headers)) {
            $headers = explode("\r\n", $headers);
            if (end($headers) == '') {
                array_pop($headers);
            }

        }

        $this->options['headers'] = array_merge($this->options['headers'], $headers);

        return $this;
    }
}
