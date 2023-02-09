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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recently_viewed
 */

use block_totara_recently_viewed\totara_engage\link\block_destination;
use totara_engage\link\builder;
use totara_engage\link\empty_destination;

defined('MOODLE_INTERNAL') || die();

class block_totara_recently_viewed_link_generator_testcase extends advanced_testcase {
    /**
     * Validate the source generator can create links properly
     */
    public function test_source_generator() {
        $cases = [
            ['bv', '/totara/dashboard/index.php'],
            ['bv.1', '/sample-page', ['source_url' => '/sample-page']],
        ];

        foreach ($cases as $case) {
            $generator = builder::from_source($case[0]);

            if (isset($case[2])) {
                $attributes = array_merge($generator->get_attributes(), $case[2]);
                $generator->set_attributes($attributes);
            }

            if (null !== $case[1]) {
                $this->assertSame($case[1], $generator->url()->out_as_local_url(false));
            } else {
                $this->assertInstanceOf(empty_destination::class, $generator);
            }
        }
    }

    /**
     * Validate the destination tests correctly
     */
    public function test_destination_generator() {
        $generator = builder::to('block_totara_recently_viewed');
        $this->assertInstanceOf(block_destination::class, $generator);

        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/dashboard/index.php', $url);

        $generator->set_attributes(['dashboard' => false, 'source_url' => '/abc-123']);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/abc-123', $url);
    }
}