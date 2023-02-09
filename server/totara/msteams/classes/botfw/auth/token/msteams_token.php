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

namespace totara_msteams\botfw\auth\token;

use stdClass;
use totara_core\http\exception\http_exception;
use totara_core\http\request;
use totara_msteams\botfw\auth\jwt;
use totara_msteams\botfw\context;

/**
 * A class to verify an token coming through the authorization header.
 * https://docs.microsoft.com/en-us/azure/bot-service/rest-api/bot-framework-rest-connector-authentication
 */
class msteams_token extends token {
    private const ISSUER_URL = 'https://api.botframework.com';
    private const OIDC_URL = 'https://login.botframework.com/v1/.well-known/openidconfiguration';
    private const KEY = '@openid_meta';

    /**
     * Constructor.
     *
     * @param jwt $token
     */
    public function __construct(jwt $token) {
        parent::__construct($token);
    }

    /**
     * Verify the JWT.
     * Note that this function does not fully satisfy RFC 7518, RFC 7519 or RFC 7797.
     *
     * @param context $context
     * @param boolean $refresh Set true to reload the public keys
     * @return boolean
     */
    public function verify(context $context, bool $refresh = false): bool {
        if (!$this->verify_payload($context)) {
            return false;
        }
        if ($this->verify_signature($context, $refresh)) {
            return true;
        }
        if ($refresh) {
            return false;
        }
        if ($this->verify_signature($context, true)) {
            return true;
        }
        return false;
    }

    /**
     * Verify the payload.
     *
     * @param context $context
     * @return boolean
     */
    protected function verify_payload(context $context): bool {
        $payload = $this->get_jwt()->get_payload();
        /** @var \totara_msteams\botfw\internal\jwt_payload $payload */

        // 3. Compare the issuer claim against https://api.botframework.com.
        if (!isset($payload->iss) || !self::url_equals(self::ISSUER_URL, $payload->iss)) {
            $iss = $payload->iss ?? '(N/A)';
            $context->get_logger()->debug("The issuer claim is '{$iss}' instead of 'https://api.botframework.com'");
            return false;
        }

        // 4. Compare the audience claim against the app ID of the bot.
        if (!isset($payload->aud) || $payload->aud !== $context->get_storage()->get_app_id()) {
            $aud = $payload->aud ?? '(N/A)';
            $context->get_logger()->debug("The audience claim is not identical to the bot app id: {$aud}");
            return false;
        }

        // 7. Compare the serviceUrl claim against the serviceUrl of the bot.
        // NOTE: Microsoft says it's serviceUrl but it's actually serviceurl, so check both.
        $payload_serviceurl = $payload->serviceUrl ?? $payload->serviceurl ?? null;
        if ($payload_serviceurl === null || !self::url_equals($context->get_service_url(), $payload_serviceurl)) {
            $context->get_logger()->debug("The serviceUrl claim is not valid: {$payload_serviceurl}");
            return false;
        }

        return true;
    }

    /**
     * Verify the signature part of the JWT.
     *
     * @param context $context
     * @param boolean $refresh Set true to reload the public keys
     * @return boolean
     */
    protected function verify_signature(context $context, bool $refresh = false): bool {
        $data = $refresh ? null : $context->get_storage()->bot_load(self::KEY);
        if (!$data) {
            $data = $this->sync_jwks($context);
        }
        $header = $this->get_jwt()->get_header();

        /** @var \totara_msteams\botfw\internal\openid_meta $data */
        /** @var \totara_msteams\botfw\internal\jwt_header $header */

        // 6. Verify the signature.
        if (!in_array($header->alg, $data->algs)) {
            $context->get_logger()->debug("Unsupported signing algorithm: {$header->alg}");
            return false;
        }

        // Find the key used for signing the JWT.
        $signedjwk = null;
        foreach ($data->jwks->keys as $key) {
            if ($key->kid === $header->kid) {
                $signedjwk = $key;
                break;
            }
        }
        if (!$signedjwk) {
            $context->get_logger()->debug("Signed key not found: {$header->kid}");
            return false;
        }
        /** @var stdClass $signedjwk */
        return $this->verify_jwt_signature($context, $signedjwk, $header->alg);
    }

    /**
     * Verify the digital signature.
     *
     * @param context $context
     * @param jwk $jwk
     * @param string $algorithm see RFC 7518 section 3.1
     * @return boolean
     */
    private function verify_jwt_signature(context $context, stdClass $jwk, string $algorithm): bool {
        if ($algorithm === 'none') {
            // Reject a token with no digital signature.
            return false;
        }
        $signature = $this->get_jwt()->get_signature();
        // Create [header].[payload].
        $parts = explode('.', $this->get_jwt()->as_string());
        $token = implode('.', array_splice($parts, 0, 2));
        $prefix = substr($algorithm, 0, 2);
        // RSASSA PKCS #1 using SHA
        if ($prefix === 'RS') {
            $publickey = "-----BEGIN CERTIFICATE-----\n".chunk_split(current($jwk->x5c), 64, "\n")."-----END CERTIFICATE-----";
            switch ($algorithm) {
                case 'RS256': // RSASSA-PKCS1-v1_5 using SHA-256
                    return openssl_verify($token, $signature, $publickey, OPENSSL_ALGO_SHA256) === 1;
                case 'RS384': // RSASSA-PKCS1-v1_5 using SHA-384
                    return openssl_verify($token, $signature, $publickey, OPENSSL_ALGO_SHA384) === 1;
                case 'RS512': // RSASSA-PKCS1-v1_5 using SHA-512
                    return openssl_verify($token, $signature, $publickey, OPENSSL_ALGO_SHA512) === 1;
            }
            return false;
        }
        // HMAC using SHA
        if ($prefix === 'HS') {
            $context->get_logger()->debug('HMAC signature is not supported.');
            switch ($algorithm) {
                case 'HS256': // HMAC using SHA-256
                case 'HS384': // HMAC using SHA-384
                case 'HS512': // HMAC using SHA-512
                    // Even though RFC 7518 requires HMAC-SHA, we're not going to implement it at the moment as Microsoft's bot connector never uses the algorithm.
                    // $bits = substr($algorithm, 2);
                    // $realsignature = hash_hmac('sha'.$bits, $token, $context->get_storage()->get_app_secret(), true);
                    // return hash_equals($signature, $realsignature);
                    return false;
            }
            return false;
        }
        // Add ECDSA support if necessary.
        if ($prefix === 'ES') {
            $context->get_logger()->debug('ECDSA signature is not supported.');
            return false;
        }
        // Add RSASSA-PSS support if necessary.
        if ($prefix === 'PS') {
            $context->get_logger()->debug('RSASSA-PSS signature is not supported.');
            return false;
        }
        return false;
    }

    /**
     * Invalidate the cached OpenID metadata document and the public JWKs.
     *
     * @param context $context
     * @return stdClass
     * @throws http_exception
     */
    protected function sync_jwks(context $context): stdClass {
        $client = $context->get_client();

        $oidc = $client->execute(request::get(self::OIDC_URL))->get_body_as_json(false, true);
        /** @var \totara_msteams\botfw\internal\openid_config $oidc */

        $jwks = $client->execute(request::get($oidc->jwks_uri))->get_body_as_json(false, true);
        /** @var \totara_msteams\botfw\internal\jwks $jwks */

        $data = (object)[
            'algs' => $oidc->id_token_signing_alg_values_supported,
            'jwks' => $jwks
        ];
        $context->get_storage()->bot_store(self::KEY, $data);
        return $data;
    }

    /**
     * Compare two URLs.
     *
     * @param string $source
     * @param string $destination
     * @return boolean true if both URLs are identical.
     */
    private static function url_equals(string $source, string $destination): bool {
        if (substr($destination, -1) !== '/') {
            $destination .= '/';
        }
        if (substr($source, -1) !== '/') {
            $source .= '/';
        }
        return $source === $destination;
    }
}
