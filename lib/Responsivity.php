<?php

namespace lib;

class Responsivity
{
    /**
     * Legacy response system
     * @param mixed $message
     * @param int $code
     * @return void
     */
    public static function JSON_response($message, int $code = 200)
    {
        header("Content-Type: application/json");
        http_response_code($code);
        echo json_encode($message);
    }

    /**
     * `200 OK` -> Indicates that the request has succeeded.
     * 
     * `201 Created` -> Indicates that the request has succeeded and a new resource has been created as a result.
     * 
     * `202 Accepted` -> Indicates that the request has been received but not completed yet. It is typically used in log running requests and batch processing.
     * 
     * `203 Non-Authoritative Information` -> Indicates that the returned metainformation in the entity header is not the definitive set as available from the origin server but is gathered from a local or third-party copy. The set presented MAY be a subset or superset of the original version.
     * 
     * `204 No Content` -> The server has fulfilled the request but does not need to return a response body. The server may return the updated meta information.
     * 
     * `205 Reset Content` -> Indicates the client to reset the document that sent this request.
     * 
     * `206 Partial Content` -> It is used when the Range header is sent from the client to request only part of a resource.
     * 
     * `207 Multi-Status (WebDAV)` -> An indicator to a client that multiple operations happened, and that the status for each operation can be found in the body of the response.
     * 
     * `208 Already Reported (WebDAV)` -> Allows a client to tell the server that the same resource (with the same binding) was mentioned earlier. It never appears as a true HTTP response code in the status line, and only appears in bodies.
     * 
     * `226 IM Used` -> The server has fulfilled a GET request for the resource, and the response is a representation of the result of one or more instance-manipulations applied to the current instance.
     * @param mixed $message
     * @param int $add
     * @return never
     */
    public static function response_success($message, int $add = 0)
    {
        header("Content-Type: application/json");
        http_response_code(200 + $add);
        echo json_encode($message);
        die;
    }
}