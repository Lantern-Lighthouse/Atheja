<?php

namespace lib;

class Responsivity
{

    /* These lines of code are defining constants within the `Responsivity` class in PHP. Each constant
    represents an HTTP status code along with its corresponding numeric value. These constants are
    used to provide a more readable and maintainable way to refer to HTTP status codes throughout
    the class and its methods. */
    const HTTP_OK = 200;
    const HTTP_Created = 201;
    const HTTP_Accepted = 202;
    const HTTP_No_Content = 204;
    
    const HTTP_Moved_Permanently = 301;
    const HTTP_Found = 302;
    const HTTP_See_Other = 303;
    const HTTP_Not_Modified = 304;
    const HTTP_Temporary_Redirect = 307;
    
    const HTTP_Bad_Request = 400;
    const HTTP_Unauthorized = 401;
    const HTTP_Forbidden = 403;
    const HTTP_Not_Found = 404;
    const HTTP_Method_Not_Allowed = 405;
    const HTTP_Not_Acceptable = 406;
    const HTTP_Precondition_Failed = 412;
    const HTTP_Unsupported_Media = 415;
    const HTTP_Teapot = 418;
    const HTTP_Unavailable_Legal_Reasons = 451;
    
    const HTTP_Internal_Error = 500;
    const HTTP_Not_Implemented = 501;

    /**
     * The function `response` sends a JSON-encoded message with an optional HTTP status code.
     * 
     * @param string message The `message` parameter in the `response` function can accept either a
     * string or an array. This parameter is used to provide the data that will be encoded into JSON
     * format and sent as the response. It can be a simple string message or an array of data to be
     * returned to the client.
     * @param int code The `` parameter in the `response` function is used to specify the HTTP
     * status code that will be sent in the response header. By default, it is set to `HTTP_OK`, which
     * typically corresponds to the status code `200 OK` indicating a successful response. However, you
     * can override
     */
    public static function response(string|array $message, int $code = HTTP_OK)
    {
        header("Content-Type: application/json");
        http_response_code($code);
        echo json_encode($message);
        die;
    }
}