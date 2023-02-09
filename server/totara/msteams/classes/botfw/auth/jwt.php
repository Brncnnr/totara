<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\auth;

use stdClass;
use totara_core\util\base64url;
use totara_msteams\botfw\exception\jwt_exception;

/**
 * A class that manages a JSON Web Token.
 */
class jwt {
    /** @var string */
    private $token;
    /** @var stdClass */
    private $header;
    /** @var stdClass */
    private $payload;
    /** @var string */
    private $signature;

    /**
     * Industry-standard clock-skew is 5 minutes, according to Microsoft.
     */
    private const CLOCK_SKEW = 300;

    /**
     * Private constructor to enforce the factory pattern.
     *
     * @param string $token JSON string
     * @param integer|null $time timestamp
     * @throws jwt_exception
     */
    private function __construct(string $token, ?int $time) {
        $time = $time ?? time();
        $this->token = $token;
        [$this->header, $this->payload, $this->signature] = self::parse($token, $time);
    }

    /**
     * @return stdClass
     */
    public function get_header(): object {
        return $this->header;
    }

    /**
     * @return stdClass
     */
    public function get_payload(): object {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function get_signature(): string {
        return $this->signature;
    }

    /**
     * @return string
     */
    public function as_string(): string {
        return $this->token;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->as_string();
    }

    /**
     * Decode JSON for JWT.
     *
     * @param string $json
     * @return object
     */
    protected static function safe_json_decode(string $json) {
        $object = @json_decode($json, false, 512, JSON_BIGINT_AS_STRING);
        if ($object === null || !is_object($object)) {
            $message = json_last_error() ? json_last_error_msg() : 'Invalid JSON format';
            throw new jwt_exception($message);
        }
        return $object;
    }

    /**
     * Parse and verify JSON Web Token. See RFC 7517, RFC 7519, RFC 7797.
     *
     * @param string $input
     * @param integer $time the timestamp
     * @return array of [header_object, payload_object, signature]
     * @throws jwt_exception
     */
    protected static function parse(string $input, int $time): array {
        $parts = explode('.', $input);
        if (count($parts) !== 3) {
            throw new jwt_exception();
        }
        $header = base64url::decode($parts[0]);
        $payload = base64url::decode($parts[1]);
        // NOTE: the signature part is binary
        $signature = base64url::decode($parts[2]);

        if (empty($header) || empty($payload) || empty($signature)) {
            throw new jwt_exception();
        }

        $header = self::safe_json_decode($header);
        $payload = self::safe_json_decode($payload);
        /** @var \totara_msteams\botfw\internal\jwt_header $header */
        /** @var \totara_msteams\botfw\internal\jwt_payload $payload */

        // Reject unsecured JWT.
        if (!isset($header->alg) || $header->alg === 'none') {
            throw new jwt_exception();
        }
        // Verify the 'Type' header parameter if set. Note that the parameter is case insensitive.
        if (isset($header->typ) && strcasecmp($header->typ, 'JWT')) {
            throw new jwt_exception();
        }
        // Verify the 'Expiration Time' claim if set.
        if (isset($payload->exp) && $payload->exp <= ($time - self::CLOCK_SKEW)) {
            throw new jwt_exception();
        }
        // Verify the 'Not Before' claim if set.
        if (isset($payload->nbf) && $payload->nbf > ($time + self::CLOCK_SKEW)) {
            throw new jwt_exception();
        }
        // Verify the 'Issued At' claim if set.
        if (isset($payload->iat) && $payload->iat > ($time + self::CLOCK_SKEW)) {
            throw new jwt_exception();
        }

        return [$header, $payload, $signature];
    }

    /**
     * Parse and verify JSON Web Token.
     *
     * @param string $input JSON string
     * @param integer|null $time timestamp
     * @return self
     * @throws jwt_exception if fails
     */
    public static function load(string $input, int $time = null): self {
        return new jwt($input, $time);
    }

    /**
     * Parse and verify JSON Web Token.
     *
     * @param string $input JSON string
     * @param integer|null $time timestamp
     * @return self|null jwt instance or null if fails
     */
    public static function try_load(string $input, int $time = null): ?self {
        try {
            return new jwt($input, $time);
        } catch (jwt_exception $ex) {
            // Swallow an exception because you told me to.
            return null;
        }
    }
}
