<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\http;

/**
 * HTTP response status codes
 *
 * The below status codes are defined by {@see https://datatracker.ietf.org/doc/html/rfc2616#section-10}.
 * You can find an updated specification in {@see https://datatracker.ietf.org/doc/html/rfc7231#section-6.5.1}
 */
class response_code {
    // ------ INFRMATION RESPONSES ------

    /**
     * This interim response indicates that everything so far is OK and that
     * the client should continue the request, or ignore the response if the
     * request is already finished.
     *
     * @var int
     */
    public const CONTINUE = 100;

    /**
     * This code is sent in response to an Upgrade request header from the client,
     * and indicates the protocol the server is switching to.
     *
     * @var int
     */
    public const SWITCHING_PROTOCOL = 101;


    /**
     * This status code is primarily intended to be used with the Link header,
     * letting the user agent start preloading resources while the server prepares a response.
     *
     * @var int
     */
    public const EARLY_HINTS = 103;

    // ------ END ------

    // ----- SUCCESSFUL RESPONSES -----

    /**
     * The request has succeeded. The meaning of the success depends on the HTTP method:
     * + GET: The resource has been fetched and is transmitted in the message body.
     * + HEAD: The entity headers are in the message body.
     * + PUT or POST: The resource describing the result of the action is transmitted in the message body.
     * + TRACE: The message body contains the request message as received by the server.
     *
     * @var int
     */
    public const OK = 200;

    /**
     * he request has succeeded and a new resource has been created as a result.
     * This is typically the response sent after POST requests, or some PUT requests.
     *
     * @var int
     */
    public const CREATED = 201;

    /**
     * The request has been received but not yet acted upon. It is noncommittal,
     * since there is no way in HTTP to later send an asynchronous response indicating the outcome of the request.
     * It is intended for cases where another process or server handles the request, or for batch processing.
     *
     * @var int
     */
    public const ACCEPTED = 202;

    /**
     * This response code means the returned meta-information is not exactly the same as
     * is available from the origin server,  but is collected from a local or a third-party
     * copy. This is mostly used for mirrors or backups of another resource. Except for that
     * specific case, the "200 OK" response is preferred to this status.
     *
     * @var int
     */
    public const NON_AUTHORITATIVE_INFORMATION = 203;

    /**
     * There is no content to send for this request, but the headers may be useful.
     * The user-agent may update its cached headers for this resource with the new ones.
     *
     * @var int
     */
    public const NO_CONTENT = 204;

    /**
     * Tells the user-agent to reset the document which sent this request.
     *
     * @var int
     */
    public const RESET_CONTENT = 205;

    /**
     * This response code is used when the Range header is sent from the client to request only part of a resource.
     *
     * @var int
     */
    public const PARTIAL_CONTENT = 206;

    // ------ END ------

    // ------ REDIRECTION MESSAGES ------

    /**
     * The request has more than one possible response. The user-agent or user should
     * choose one of them. (There is no standardized way of choosing one of the responses,
     * but HTML links to the possibilities are recommended so the user can pick.)
     *
     * @var int
     */
    public const MULTI_CHOICE = 300;

    /**
     * The URL of the requested resource has been changed permanently.
     * The new URL is given in the response.
     *
     * @var int
     */
    public const MOVED_PERMANENTLY = 301;

    /**
     * This response code means that the URI of requested resource has been changed temporarily.
     * Further changes in the URI might be made in the future.
     * Therefore, this same URI should be used by the client in future requests.
     *
     * @var int
     */
    public const FOUND = 302;

    /**
     * The server sent this response to direct the client to get the requested resource at another URI with a GET request.
     *
     * @var int
     */
    public const SEE_OTHER = 303;

    /**
     * This is used for caching purposes. It tells the client that the response has not been modified,
     * so the client can continue to use the same cached version of the response.
     *
     * @var int
     */
    public const NOT_MODIFIED = 304;

    /**
     * The server sends this response to direct the client to get the requested resource at another URI
     * with same method that was used in the prior request. This has the same semantics as the 302 Found
     * HTTP response code, with the exception that the user agent must not change the HTTP
     * method used: If a POST was used in the first request, a POST must be used in the second request.
     *
     * @var int
     */
    public const TEMPORARY_REDIRECT = 307;

    /**
     * This means that the resource is now permanently located at another URI,
     * specified by the Location: HTTP Response header. This has the same semantics
     * as the 301 Moved Permanently HTTP response code, with the exception that the
     * user agent must not change the HTTP method used: If a POST was used in the first
     * request, a POST must be used in the second request.
     *
     * @var int
     */
    public const PERMANENT_REDIRECT = 308;

    // ------ END ------

    // ------ CLIENT ERROR RESPONSES ------

    /**
     * The server could not understand the request due to invalid syntax.
     *
     * @var int
     */
    public const BAD_REQUEST = 400;

    /**
     * Although the HTTP standard specifies "unauthorized", semantically this response
     * means "unauthenticated". That is, the client must authenticate itself to get
     * the requested response.
     *
     * @var int
     */
    public const UNAUTHORIZED = 401;

    /**
     * The client does not have access rights to the content; that is, it is unauthorized,
     * so the server is refusing to give the requested resource. Unlike 401, the client's
     * identity is known to the server.
     *
     * @var int
     */
    public const FORBIDDEN = 403;

    /**
     * The server can not find the requested resource. In the browser, this means the URL is not recognized.
     * In an API, this can also mean that the endpoint is valid but the resource itself does not exist.
     * Servers may also send this response instead of 403 to hide the existence of a resource from an
     * unauthorized client. This response code is probably the most famous one due to its frequent
     * occurrence on the web.
     *
     * @var int
     */
    public const NOT_FOUND = 404;

    /**
     * The request method is known by the server but has been disabled and cannot be used.
     * For example, an API may forbid DELETE-ing a resource. The two mandatory methods,
     * GET and HEAD, must never be disabled and should not return this error code.
     *
     * @var int
     */
    public const METHOD_NOT_ALLOWED = 405;

    /**
     * This response is sent when the web server, after performing server-driven content negotiation,
     * doesn't find any content that conforms to the criteria given by the user agent.
     *
     * @var int
     */
    public const NOT_ACCEPTABLE = 406;

    /**
     * This is similar to 401 but authentication is needed to be done by a proxy.
     *
     * @var int
     */
    public const PROXY_AUTHENTICATION_REQUIRED = 407;

    /**
     * This response is sent on an idle connection by some servers, even without any previous request by the client.
     * It means that the server would like to shut down this unused connection.
     *
     * @var int
     */
    public const REQUEST_TIMEOUT = 408;

    /**
     * This response is sent when a request conflicts with the current state of the server.
     *
     * @var int
     */
    public const CONFLICT = 409;

    /**
     * This response is sent when the requested content has been permanently deleted from server,
     * with no forwarding address. Clients are expected to remove their caches and links to the resource.
     * The HTTP specification intends this status code to be used for "limited-time, promotional services".
     * APIs should not feel compelled to indicate resources that have been deleted with this status code.
     *
     * @var int
     */
    public const GONE = 410;

    /**
     * Server rejected the request because the Content-Length header field is not defined and the server requires it.
     *
     * @var int
     */
    public const LENGTH_REQUIRED = 411;

    /**
     * The client has indicated preconditions in its headers which the server does not meet.
     *
     * @var int
     */
    public const PRECONDITION_FAILED = 412;

    /**
     * Request entity is larger than limits defined by server; the server might close the connection
     * or return an Retry-After header field.
     *
     * @var int
     */
    public const PAYLOAD_TOO_LARGE = 413;

    /**
     * The URI requested by the client is longer than the server is willing to interpret.
     * @var int
     */
    public const URI_TOO_LONG = 414;

    /**
     * The media format of the requested data is not supported by the server, so the server is rejecting the request.
     *
     * @var int
     */
    public const UNSUPPORTED_MEDIA_TYPE = 415;

    /**
     * The range specified by the Range header field in the request can't be fulfilled;
     * it's possible that the range is outside the size of the target URI's data.
     *
     * @var int
     */
    public const RANGE_NOT_SATISFIABLE = 416;

    /**
     * This response code means the expectation indicated by the Expect request header field can't be met by the server.
     *
     * @var int
     */
    public const EXPECTATION_FAILED = 417;

    /**
     * The server refuses the attempt to brew coffee with a teapot.
     *
     * @var int
     */
    public const I_AM_A_TEAPOT = 418;

    /**
     * The request was directed at a server that is not able to produce a response.
     * This can be sent by a server that is not configured to produce responses for the
     * combination of scheme and authority that are included in the request URI.
     *
     * @var int
     */
    public const MISDIRECTED_REQUEST = 421;

    /**
     * The server refuses to perform the request using the current protocol but might be willing
     * to do so after the client upgrades to a different protocol. The server sends an Upgrade header
     * in a 426 response to indicate the required protocol(s).
     *
     * @var int
     */
    public const UPGRADE_REQUIRED = 426;

    /**
     * The origin server requires the request to be conditional. This response is intended to
     * prevent the 'lost update' problem, where a client GETs a resource's state, modifies it,
     * and PUTs it back to the server, when meanwhile a third party has modified the state on
     * the server, leading to a conflict.
     *
     * @var int
     */
    public const PRECONDITION_REQUIRED = 428;

    /**
     * The user has sent too many requests in a given amount of time ("rate limiting").
     *
     * @var int
     */
    public const TOO_MANY_REQUESTS = 429;

    /**
     * The server is unwilling to process the request because its header fields are too large.
     * The request may be resubmitted after reducing the size of the request header fields.
     *
     * @var int
     */
    public const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

    /**
     * The user-agent requested a resource that cannot legally be provided, such as a web page censored by a government.
     *
     * @var int
     */
    public const UNVAILABLE_FOR_LEGAL_REASONS = 451;

    // ----- END -----

    // ----- SERVER ERROR RESPONSES -----

    /**
     * The server has encountered a situation it doesn't know how to handle.
     *
     * @var int
     */
    public const INTERNAL_SERVER_ERROR = 500;

    /**
     * The request method is not supported by the server and cannot be handled.
     * The only methods that servers are required to support (and therefore that must not return this code)
     * are GET and HEAD.
     *
     * @var int
     */
    public const NOT_IMPLEMENTED = 501;

    /**
     * This error response means that the server, while working as a gateway to get a response
     * needed to handle the request, got an invalid response.
     *
     * @var int
     */
    public const BAD_GATEWAY = 502;

    /**
     * The server is not ready to handle the request. Common causes are a server that is
     * down for maintenance or that is overloaded
     *
     * @var int
     */
    public const SERVICE_UNAVAILABLE = 503;

    /**
     * This error response is given when the server is acting as a gateway and cannot get a response in time.
     *
     * @var int
     */
    public const GATEWAY_TIMEOUT = 504;

    /**
     * The HTTP version used in the request is not supported by the server.
     *
     * @var int
     */
    public const VERSION_NOT_SUPPORTED = 505;

    /**
     * The server has an internal configuration error: the chosen variant resource is configured
     * to engage in transparent content negotiation itself, and is therefore not a proper end
     * point in the negotiation process.
     *
     * @var int
     */
    public const VARIANT_ALSO_NEGOTIATES = 506;

    /**
     * Further extensions to the request are required for the server to fulfill it.
     *
     * @var int
     */
    public const NOT_EXTENDED = 510;

    /**
     * The 511 status code indicates that the client needs to authenticate to gain network access
     *
     * @var int
     */
    public const NETWORK_AUTHENTICATION_REQUIRED = 511;

    // ----- END -----

    // ----- HELPER FUNCTIONS -----

    /**
     * @param int $http_code
     * @return bool
     */
    public static function is_successful_response(int $http_code): bool {
        return static::OK <= $http_code && $http_code <= 299;
    }
}