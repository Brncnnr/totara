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

use totara_core\util\base64url;
use totara_msteams\botfw\auth\jwt;
use totara_msteams\botfw\auth\token\msteams_token;

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_botfw_auth_msteams_token_testcase extends botfw_jwks_base_testcase {
    public function test_verify_signature_payload() {
        foreach ([256, 384, 512] as $bits) {
            // Valid payload.
            $this->context->logger->reset();
            $this->mock_jwks();
            $payload = [
                'serviceUrl' => $this->context->service_url,
                'nbf' => 1,
                'exp' => 1001,
                'iss' => 'https://api.botframework.com',
                'aud' => $this->context->bot_app_id
            ];
            $jwt = $this->create_signed_jwt($bits, $payload);
            $token = new msteams_token(jwt::load($jwt, 10));
            $verified = $token->verify($this->context, true);
            $this->assertEquals([], $this->context->logger->debugs);
            $this->assertTrue($verified);

            // Valid payload.
            $this->context->logger->reset();
            $this->mock_jwks();
            $payload = [
                'serviceurl' => $this->context->service_url,
                'nbf' => 1,
                'exp' => 1001,
                'iss' => 'https://api.botframework.com',
                'aud' => $this->context->bot_app_id
            ];
            $jwt = $this->create_signed_jwt($bits, $payload);
            $token = new msteams_token(jwt::load($jwt, 10));
            $verified = $token->verify($this->context, true);
            $this->assertEquals([], $this->context->logger->debugs);
            $this->assertTrue($verified);

            // Valid payload.
            $this->context->logger->reset();
            $this->mock_jwks();
            $payload = [
                'serviceurl' => $this->context->service_url.'/',
                'nbf' => 1,
                'exp' => 1001,
                'iss' => 'https://api.botframework.com',
                'aud' => $this->context->bot_app_id
            ];
            $jwt = $this->create_signed_jwt($bits, $payload);
            $token = new msteams_token(jwt::load($jwt, 10));
            $verified = $token->verify($this->context, true);
            $this->assertEquals([], $this->context->logger->debugs);
            $this->assertTrue($verified);

            // Invalid serviceurl.
            $this->context->logger->reset();
            $this->mock_jwks();
            $payload = [
                'serviceurl' => 'http://bogus.example.com',
                'nbf' => 1,
                'exp' => 1001,
                'iss' => 'https://api.botframework.com',
                'aud' => $this->context->bot_app_id
            ];
            $jwt = $this->create_signed_jwt($bits, $payload);
            $token = new msteams_token(jwt::load($jwt, 10));
            $verified = $token->verify($this->context, true);
            $this->assertCount(1, $this->context->logger->debugs);
            $this->assertFalse($verified);

            // Invalid issuer.
            $this->context->logger->reset();
            $this->mock_jwks();
            $payload = [
                'serviceurl' => $this->context->service_url,
                'nbf' => 1,
                'exp' => 1001,
                'iss' => 'https://bogus.example.com',
                'aud' => $this->context->bot_app_id
            ];
            $jwt = $this->create_signed_jwt($bits, $payload);
            $token = new msteams_token(jwt::load($jwt, 10));
            $verified = $token->verify($this->context, true);
            $this->assertCount(1, $this->context->logger->debugs);
            $this->assertFalse($verified);

            // Invalid audience.
            $this->context->logger->reset();
            $this->mock_jwks();
            $payload = [
                'serviceurl' => $this->context->service_url,
                'nbf' => 1,
                'exp' => 1001,
                'iss' => 'https://api.botframework.com',
                'aud' => '00000000-0000-0000-C000-000000000046'
            ];
            $jwt = $this->create_signed_jwt($bits, $payload);
            $token = new msteams_token(jwt::load($jwt, 10));
            $verified = $token->verify($this->context, true);
            $this->assertCount(1, $this->context->logger->debugs);
            $this->assertFalse($verified);
        }
    }

    public function test_verify_signature_signature() {
        $payload = base64url::encode(json_encode([
            'serviceurl' => $this->context->service_url,
            'nbf' => 1,
            'exp' => 1001,
            'iss' => 'https://api.botframework.com',
            'aud' => $this->context->bot_app_id
        ], JSON_UNESCAPED_SLASHES));

        // Invalid algorithm.
        $this->mock_jwks();
        $this->context->logger->reset();
        $header = [
            'alg' => 'RS128',
            'kid' => $this->kids[256],
            'typ' => 'JWT',
            'x5t' => $this->kids[256],
        ];
        $data = base64url::encode(json_encode($header, JSON_UNESCAPED_SLASHES)) . '.' . $payload;
        openssl_sign($data, $signature, $this->resources[256], OPENSSL_ALGO_SHA256);
        $jwt = $data . '.' . base64url::encode($signature);
        $token = new msteams_token(jwt::load($jwt, 10));
        $verified = $token->verify($this->context, true);
        $this->assertEquals(['Unsupported signing algorithm: RS128'], $this->context->logger->debugs);
        $this->assertFalse($verified);

        // Invalid key id.
        $this->mock_jwks();
        $this->context->logger->reset();
        $header = [
            'alg' => 'RS256',
            'kid' => '0MgtHisIsAniNVal1dk3yiD',
            'typ' => 'JWT',
            'x5t' => '0MgtHisIsAniNVal1dk3yiD',
        ];
        $data = base64url::encode(json_encode($header, JSON_UNESCAPED_SLASHES)) . '.' . $payload;
        openssl_sign($data, $signature, $this->resources[256], OPENSSL_ALGO_SHA256);
        $jwt = $data . '.' . base64url::encode($signature);
        $token = new msteams_token(jwt::load($jwt, 10));
        $verified = $token->verify($this->context, true);
        $this->assertEquals(['Signed key not found: 0MgtHisIsAniNVal1dk3yiD'], $this->context->logger->debugs);
        $this->assertFalse($verified);

        // Signed with a wrong private key.
        $this->mock_jwks();
        $this->context->logger->reset();
        $header = [
            'alg' => 'RS256',
            'kid' => $this->kids[256],
            'typ' => 'JWT',
            'x5t' => $this->kids[256],
        ];
        $data = base64url::encode(json_encode($header, JSON_UNESCAPED_SLASHES)) . '.' . $payload;
        openssl_sign($data, $signature, $this->resources[512], OPENSSL_ALGO_SHA512);
        $jwt = $data . '.' . base64url::encode($signature);
        $token = new msteams_token(jwt::load($jwt, 10));
        $verified = $token->verify($this->context, true);
        $this->assertEquals([], $this->context->logger->debugs);
        $this->assertFalse($verified);
    }
}
