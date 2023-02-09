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
use totara_msteams\botfw\exception\jwt_exception;

class totara_msteams_botfw_auth_jwt_testcase extends advanced_testcase {
    /**
     * Make JWT.
     *
     * @param string $header header in JSON format
     * @param string|null $signature
     * @return string JWT
     */
    private static function jwt_make(string $header, ?string $signature = null): string {
        return implode('.', [base64url::encode($header), 'e30', $signature ?? 'bG9yZW1pcHN1bSE_']);
    }

    public function data_load_success(): array {
        return [
            [self::jwt_make('{"typ":"JWT","alg":"HS256"}', 'BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc')],
            [self::jwt_make('{"typ":"jwt","alg":"HS256"}', 'BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc')],
            ['eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsIng1dCI6IjdfWnVmMXR2a3dMeFlhSFMzcTZsVWpVWUlHdyIsImtpZCI6IjdfWnVmMXR2a3dMeFlhSFMzcTZsVWpVWUlHdyJ9.eyJhdWQiOiJiMTRhNzUwNS05NmU5LTQ5MjctOTFlOC0wNjAxZDBmYzljYWEiLCJpc3MiOiJodHRwczovL3N0cy53aW5kb3dzLm5ldC9mYTE1ZDY5Mi1lOWM3LTQ0NjAtYTc0My0yOWYyOTU2ZmQ0MjkvIiwiaWF0IjoxMTExMTExMTExLCJuYmYiOjExMTExMTExMTEsImV4cCI6OTk5OTk5OTk5OSwiYWlvIjoiQVhRQWkvOElBQUFBcXhzdUIrUjREMnJGUXFPRVRPNFlkWGJMRDlrWjh4ZlhhZGVBTTBRMk5rTlQ1aXpmZzN1d2JXU1hodVNTajZVVDVoeTJENldxQXBCNWpLQTZaZ1o5ay9TVTI3dVY5Y2V0WGZMT3RwTnR0Z2s1RGNCdGsrTExzdHovSmcrZ1lSbXY5YlVVNFhscGhUYzZDODZKbWoxRkN3PT0iLCJhbXIiOlsicnNhIl0sImVtYWlsIjoiYWJlbGlAbWljcm9zb2Z0LmNvbSIsImZhbWlseV9uYW1lIjoiTGluY29sbiIsImdpdmVuX25hbWUiOiJBYmUiLCJpZHAiOiJodHRwczovL3N0cy53aW5kb3dzLm5ldC83MmY5ODhiZi04NmYxLTQxYWYtOTFhYi0yZDdjZDAxMWRiNDcvIiwiaXBhZGRyIjoiMTMxLjEwNy4yMjIuMjIiLCJuYW1lIjoiYWJlbGkiLCJub25jZSI6IjEyMzUyMyIsIm9pZCI6IjA1ODMzYjZiLWFhMWQtNDJkNC05ZWMwLTFiMmJiOTE5NDQzOCIsInJoIjoiSSIsInN1YiI6IjVfSjlyU3NzOC1qdnRfSWN1NnVlUk5MOHhYYjhMRjRGc2dfS29vQzJSSlEiLCJ0aWQiOiJmYTE1ZDY5Mi1lOWM3LTQ0NjAtYTc0My0yOWYyOTU2ZmQ0MjkiLCJ1bmlxdWVfbmFtZSI6IkFiZUxpQG1pY3Jvc29mdC5jb20iLCJ1dGkiOiJMeGVfNDZHcVRrT3BHU2ZUbG40RUFBIiwidmVyIjoiMS4wIn0.UJQrCA6qn2bXq57qzGX_-D3HcPHqBMOKDPx4su1yKRLNErVD8xkxJLNLVRdASHqEcpyDctbdHccu6DPpkq5f0ibcaQFhejQNcABidJCTz0Bb2AbdUCTqAzdt9pdgQvMBnVH1xk3SCM6d4BbT4BkLLj10ZLasX7vRknaSjE_C5DI7Fg4WrZPwOhII1dB0HEZ_qpNaYXEiy-o94UJ94zCr07GgrqMsfYQqFR7kn-mn68AjvLcgwSfZvyR_yIK75S_K37vC3QryQ7cNoafDe9upql_6pB2ybMVlgWPs_DmbJ8g0om-sPlwyn74Cc1tW3ze-Xptw_2uVdPgWyqfuWAfq6Q'],
        ];
    }

    /**
     * @dataProvider data_load_success
     */
    public function test_load_success(string $input) {
        jwt::load($input);
    }

    public function data_load_failure_header(): array {
        return [
            [''],
            ['kia ora'],
            ['192.168.123.456'],
            ['a2lh.b3Jh.'],
            // header & payload are not JSON
            ['a2lh.b3Jh.bG9yZW1pcHN1bSE_'],
            // No alg parameter
            [self::jwt_make('{"kia":"ora"}')],
            // Unsecured JWT
            [self::jwt_make('{"alg":"none"}')],
            // Invalid 'typ'
            [self::jwt_make('{"typ":"OMG","alg":"HS256"}')],
        ];
    }

    /**
     * @param string $input
     * @dataProvider data_load_failure_header
     */
    public function test_load_failure_header(string $input) {
        try {
            jwt::load($input);
            $this->fail('jwt_exception expected');
        } catch (jwt_exception $ex) {
        }
        $this->assertNull(jwt::try_load($input));
    }

    public function data_load_failure_payload() {
        $time = time();
        return [
            // Expiration time is in the past
            'exp' => [['exp' => $time - HOURSECS], $time],
            // Not before is in the future
            'nbf' => [['nbf' => $time + HOURSECS], $time],
            // Issued at is in the future
            'iat' => [['iat' => $time + HOURSECS], $time],
        ];
    }

    /**
     * @param array $input
     * @param integer $time
     * @dataProvider data_load_failure_payload
     */
    public function test_load_failure_payload($input, int $time) {
        $payload = json_encode($input);
        $input = implode('.', [base64url::encode('{"typ":"JWT","alg":"HS256"}'), base64url::encode($payload), 'BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc']);
        try {
            jwt::load($input, $time);
            $this->fail('jwt_exception expected');
        } catch (jwt_exception $ex) {
        }
        $this->assertNull(jwt::try_load($input, $time));
    }
}
