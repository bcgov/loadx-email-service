<?php
/*
 * Created on Nov 17, 2011
 * 
 */

/**
 * Make an http POST request and return the response content and headers
 * @param string $url    url of the requested script
 * @param array $data    hash array of request variables
 * @return returns a hash array with response content and headers in the following form:
 *     array ('content'=>'<html></html>'
 *         , 'headers'=>array ('HTTP/1.1 200 OK', 'Connection: close', ...)
 *         )
*/
function http_post($url, $data)
{
    $data_url = http_build_query($data);
    $ctx = stream_context_create(array('http' => array(
        'method'        => 'POST',
        'header'        => "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\nConnection: close\r\n",
        'content'       => $data_url,
        'timeout'       => 30,
        'ignore_errors' => true,   // return body even on 4xx/5xx
    )));
    $content = file_get_contents($url, false, $ctx);
    if ($content === false) {
        error_log("http_post failed for $url: " . print_r(error_get_last(), true));
    }
    return array('content' => $content,
                 'headers' => isset($http_response_header) ? $http_response_header : array());
}