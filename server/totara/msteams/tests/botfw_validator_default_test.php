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

use totara_msteams\botfw\activity;
use totara_msteams\botfw\validator\default_validator;

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_botfw_validator_default_testcase extends botfw_jwks_base_testcase {
    /**
     * @param string $serviceurl
     * @param string $senderid
     * @param string $recipientid
     * @return stdClass
     */
    private function mock_data_template(string $serviceurl, string $recipientid): stdClass {
        $time = new DateTime();
        $tenantid = \core\uuid::generate();
        $senderid = '29:'.random_string(30);
        $conversationid = 'a:'.random_string();
        return (object)[
            'timestamp' => $time->format('Y-m-d\TH:i:s.vZ'),
            'localTimestamp' => $time->format('Y-m-d\TH:i:s.vP'),
            'id' => rand(),
            'channelId' => 'msteams',
            'serviceUrl' => $serviceurl,
            'locale' => 'en-GB',
            'channelData' => (object)[
                'tenant' => (object)[
                    'id' => $tenantid,
                ],
            ],
            'conversation' => (object)[
                'conversationType' => 'personal',
                'tenantId' => $tenantid,
                'id' => $conversationid,
            ],
            'from' => (object)[
                'id' => $senderid,
            ],
            'recipient' => (object)[
                'id' => $recipientid,
            ],
        ];
    }

    public function test_validate_activity() {
        $this->context->logger->reset();
        $activity = activity::from_object($this->mock_data_template('https://example.com/bogus/api/', '28:b0Gu5BoT1D'));
        $this->assertFalse((new default_validator())->validate_activity($this->context, $activity));
        $this->assertCount(2, $this->context->logger->debugs);

        $this->context->logger->reset();
        $activity = activity::from_object($this->mock_data_template($this->context->service_url, $this->context->bot_id));
        $this->assertTrue((new default_validator())->validate_activity($this->context, $activity));
        $this->assertFalse($this->context->logger->any());
    }

    public function test_validate_header() {
        // Valid token.
        $payload = [
            'serviceUrl' => $this->context->service_url,
            'nbf' => time() - 100,
            'exp' => time() + 1000,
            'iss' => 'https://api.botframework.com',
            'aud' => $this->context->bot_app_id
        ];
        $jwt = $this->create_signed_jwt(256, $payload);
        $this->context->logger->reset();
        $this->mock_jwks();
        $validated = (new default_validator())->validate_header($this->context, ['Authorization' => 'Bearer '.$jwt]);
        $this->assertEquals([], $this->context->logger->debugs);
        $this->assertEquals([], $this->context->logger->warns);
        $this->assertTrue($validated);

        // No token.
        $this->context->logger->reset();
        $validated = (new default_validator())->validate_header($this->context, []);
        $this->assertEquals(['An access to an MS Teams bot has been ignored due to invalid request headers.'], $this->context->logger->warns);
        $this->assertFalse($validated);

        // Invalid token.
        $this->context->logger->reset();
        $validated = (new default_validator())->validate_header($this->context, ['Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.e30.BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc']);
        $this->assertEquals(['An access to an MS Teams bot has been ignored due to invalid request headers.'], $this->context->logger->warns);
        $this->assertFalse($validated);
    }
}
