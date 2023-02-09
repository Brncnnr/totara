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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @category test
 */

use core\webapi\execution_context;
use core\webapi\middleware\rate_limiter;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @coversDefaultClass rate_limiter
 *
 * @group core_webapi
 */
class core_webapi_middleware_rate_limiter_testcase extends advanced_testcase {

    /**
     * @covers ::handle
     */
    public function test_rate_limit_with_fail(): void {
        $resolve_info_mock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolve_info_mock->fieldName = 'test_rate_limiter';

        $next = function (payload $payload): result {
            return new result('success');
        };

        $context = execution_context::create("dev");
        $context->set_resolve_info($resolve_info_mock);

        $payload = payload::create([], $context);

        $start_time = time();
        $limit_time = 3;
        $rate_limit = 3;

        // This is a tricky one to test as it depends a bit on the speed ot the machine this test runs on.
        // We can only hope that it runs fast enough that the limit is actually reached and the test
        // does cover the rate limit exceeded.
        for ($i = 1; $i <= $rate_limit + 1; $i++) {
            try {
                // Test rate limit with exception
                $middleware = new rate_limiter($rate_limit, $limit_time, true);
                $result = $middleware->handle($payload, $next);
                if (time() - $start_time <= $limit_time && $i > $rate_limit) {
                    $this->fail('This should have been failed due to the rate limit reached');
                }
                $this->assertNotEmpty($result->get_data());
            } catch (coding_exception $exception) {
                $this->assertStringContainsString('failed due to rate limit exceeded', $exception->getMessage());
                $time_elapsed = time() - $start_time;
                if ($time_elapsed < $limit_time && $i < $rate_limit) {
                    $this->fail('Rate limit not yet reached (currently '.$i.' / '.$time_elapsed.') so shouldn\'t have failed');
                }
            }
        }

        while ($start_time + $limit_time >= time()) {
            $this->waitForSecond();
        }

        // Should work again
        $middleware = new rate_limiter($rate_limit, $limit_time, true);
        $result = $middleware->handle($payload, $next);
        $this->assertNotEmpty($result->get_data());
    }

    /**
     * @covers ::handle
     */
    public function test_rate_limit_with_empty_result(): void {
        $resolve_info_mock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolve_info_mock->fieldName = 'test_rate_limiter';

        $next = function (payload $payload): result {
            return new result('success');
        };

        $context = execution_context::create("dev");
        $context->set_resolve_info($resolve_info_mock);

        $payload = payload::create([], $context);

        $start_time = time();
        $limit_time = 3;
        $rate_limit = 3;

        // This is a tricky one to test as it depends a bit on the speed ot the machine this test runs on.
        // We can only hope that it runs fast enough that the limit is actually reached and the test
        // does cover the rate limit exceeded.
        for ($i = 1; $i <= $rate_limit + 1; $i++) {
            // Test rate limit with exception
            $middleware = new rate_limiter($rate_limit, $limit_time, false);
            $result = $middleware->handle($payload, $next);
            if (time() - $start_time <= $limit_time) {
                if ($i > $rate_limit) {
                    $this->assertEmpty($result->get_data());
                } else {
                    $this->assertNotEmpty($result->get_data());
                }
            } else {
                $this->assertNotEmpty($result->get_data());
            }
        }

        while ($start_time + $limit_time >= time()) {
            $this->waitForSecond();
        }

        // Should work again
        $middleware = new rate_limiter($rate_limit, $limit_time, true);
        $result = $middleware->handle($payload, $next);
        $this->assertNotEmpty($result->get_data());
    }


}
