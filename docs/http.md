arc\http\ClientCurl
========
Dropin replacement for the ClientStream http client. it accepts the same options and converts them to curl options

\arc\http\ClientCurl::get
--------------------
\arc\http\ClientCurl::post
--------------------
\arc\http\ClientCurl::put
--------------------
\arc\http\ClientCurl::delete
--------------------
\arc\http\ClientCurl::request
-------------------------------
These methods are identical to their \arc\http:: counterparts.

\arc\http\ClientCurl::$responseHeaders
--------------------
\arc\http\ClientCurl::$requestHeaders
----------------------------------------
These public properties provide access to the response and request headers of the last request.

\arc\http\ClientCurl::headers
-------------------------------
    (object) \arc\http\ClientCurl::headers( (array) $headers )

This method adds the given headers to the default set of headers to be sent with each request.

