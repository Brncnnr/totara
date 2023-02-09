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
 * @package totara_playlist
 */

use totara_engage\link\builder;
use totara_engage\link\empty_destination;
use totara_playlist\totara_engage\link\playlist_destination;

defined('MOODLE_INTERNAL') || die();

class totara_playlist_link_generator_testcase extends advanced_testcase {
    /**
     * Validate the source generator can create links properly
     */
    public function test_source_generator() {
        $cases = [
            ['pl.1', '/totara/playlist/index.php?id=1'],
            ['pl.1.l', '/totara/playlist/index.php?id=1&libraryView=1'],
            ['pl.55.l', '/totara/playlist/index.php?id=55&libraryView=1'],
            ['pl1', null],
        ];

        foreach ($cases as $case) {
            $generator = builder::from_source($case[0]);

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
        $generator = builder::to('totara_playlist', ['id' => 123]);
        $this->assertInstanceOf(playlist_destination::class, $generator);

        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/playlist/index.php?id=123', $url);

        $generator->set_attributes(['id' => 5]);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/playlist/index.php?id=5', $url);

        $generator->set_attributes([]);
        $generator->url()->out_as_local_url(false);
        $this->assertDebuggingCalled("Required URL param 'id' was not provided");

        $generator->set_attributes(['id' => 55, 'library' => true]);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/playlist/index.php?id=55&libraryView=1', $url);

        $generator->from('totara_playlist', ['id' => 5555]);
        $generator->set_attributes(['id' => 3, 'library' => true]);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/playlist/index.php?id=3&libraryView=1&source=pl.5555', $url);

        $generator->set_attributes(['id' => 3, 'library' => false]);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/playlist/index.php?id=3&source=pl.5555', $url);

        $generator->from('totara_playlist', ['id' => 2222, 'library' => true]);
        $generator->set_attributes(['id' => 3, 'library' => false]);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/playlist/index.php?id=3&source=pl.2222.l', $url);
    }
}