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

class ClientCurl implements ClientInterface
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

    public function request( $type, $url, $request = null, $options = array() )
    {
        $this->responseHeaders = null;
        $this->requestHeaders = null;

        $curloptions = array(
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $type, // GET POST PUT PATCH DELETE HEAD OPTIONS
        );

        // TODO: set https validation to on
        // TODO: discover caert.pem if not set

        if ($type == 'GET' ) {
            if ( $request) {
                $url = $this->buildURL( $url, $request );
                $request = '';
            }
        } else {
            $curloptions[CURLOPT_CUSTOMREQUEST]= $type;
            if( $request ) {
                $curloptions[CURlPOSTFIELDS] = $request;
            }
        }

        $options = $this->mergeOptions( array(
            'method' => $type,
            'content' => $request
        ), $options );

        if (isset($options['header'])) {
            $options['headers'] =  $options['headers'] + explode('\r\n', $options['header']);
            $options['header'] = false;
        }

        if ( count($options['headers'])) {
            $curloptions[CURLOPT_HTTPHEADER] = $options['headers'];
        }

        /* FIXME: parse stream httpcontext options
            - method -- not needed
            - header -- done
            - user_agent
            - content -- not needed
            - proxy
            - request_fulluri
            - follow_location
            - max_redirects
            - protocol_version
            - timeout
            - ignore_errors
        */

        // Compose querry
        $ch = curl_init();
        curl_setopt_array( $ch, $curloptions);

        $result = curl_exec($ch);
        // TODO: extract http response headers
        $this->responseHeaders = array();
        $this->requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);

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