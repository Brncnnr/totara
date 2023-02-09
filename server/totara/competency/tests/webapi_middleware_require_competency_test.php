<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use core_phpunit\testcase;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_competency\testing\generator as competency_generator;
use totara_competency\webapi\middleware\require_competency;

/**
 * @group totara_competency
 */
class totara_competency_webapi_middleware_require_competency_testcase extends testcase {
    /**
     * Test data for test_valid_competency
     */
    public function td_valid_competency(): array {
        return [
            '1. default values' => [null, null],
            '2. only id key' => ['abc', null],
            '3. only storage key' => [null, 'abc'],
            '4. both id and storage keys' => ['def', 'abc'],
            '5. compound id' => ['a.b.c.d', null]
        ];
    }

    /**
     * @dataProvider td_valid_competency
     */
    public function test_valid_competency(
        ?string $id_key,
        ?string $competency_key
    ): void {
        [$competency, $next] = $this->create_test_data($competency_key);

        $payload = $this->create_payload($id_key, $competency->id);
        $result = require_competency::create($id_key, $competency_key)
            ->handle($payload, $next)
            ->get_data();

        $this->assertEquals($competency, $result, 'wrong result');
    }

    /**
     * Test data for test_invalid_competency
     */
    public function td_invalid_competency(): array {
        $competency = competency_generator::instance()->create_competency()->id;
        $id_key = 'aa';
        $no_id_err = 'no competency id in payload';

        return [
            '1. payload key != id key' => ['a.b', 'a', $competency, $no_id_err],
            '2. null competency' => [$id_key, $id_key, null, $no_id_err],
            '3. invalid competency' => [
                $id_key, $id_key, 23, 'competency does not exist'
            ],
            '4. incomplete compound id key' => [
                'a.b', 'a.b.c', $competency, 'non int id in payload'
            ]
        ];
    }

    /**
     * @dataProvider td_invalid_competency
     */
    public function test_invalid_competency(
        ?string $id_key,
        ?string $payload_id_key,
        ?int $id,
        string $err
    ): void {
        $payload = $this->create_payload($payload_id_key, $id);
        $next = function (payload $payload): result {
            $this->fail('should not reach here');
            return new result(null);
        };

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage($err);
        require_competency::create($id_key)->handle($payload, $next);
    }

    /**
     * Generates test data.
     *
     * @param string $competency_key key under which a competency is stored in
     *        the payload after the middleware executes.
     *
     * @return array (competency, next handler to execute (which returns the
     *         stored competency)) tuple.
     */
    private function create_test_data(
        ?string $competency_key
    ): array {
        $this->setAdminUser();

        $competency = competency_generator::instance()->create_competency();
        $storage_key = $competency_key ?? require_competency::KEY_COMPETENCY;

        $next = function (payload $payload) use ($storage_key): result {
            $stored = $payload->get_variable($storage_key);
            return new result($stored);
        };

        return [$competency, $next];
    }

    /**
     * Creates a payload for testing.
     *
     * @param string $id_key payload competency id key.
     * @param int $id competency id to be stored in payload.
     *
     * @return payload the payload.
     */
    private function create_payload(
        ?string $id_key,
        ?int $id
    ): payload {
        $split = explode('.', $id_key ?? require_competency::KEY_ID);
        $keys = array_reverse($split);
        $last_key = array_shift($keys);

        // Given a compound key like a.b.c, creates the payload args as:
        // ['a' => ['b' => ['c' => $id]]]
        $args = array_reduce(
            $keys,
            function (array $value, string $key): array {
                return [$key => $value];
            },
            [$last_key => $id]
        );

        return payload::create($args, execution_context::create("dev"));
    }
}