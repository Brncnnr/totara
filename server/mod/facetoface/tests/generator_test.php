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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 * @category test
 */

use \mod_facetoface\testing\generator_util;
use \mod_facetoface\testing\generator;

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_generator_testcase extends \advanced_testcase {
    /**
     * Test get_event_id_from_detail() with obnoxious event details.
     * @covers generator_util::get_event_id_from_detail
     */
    public function test_get_event_id_from_detail() {
        $method = new ReflectionMethod(generator_util::class, 'get_event_id_from_detail');
        $method->setAccessible(true);
        $gen = $this->getDataGenerator();
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        /** @var generator $f2fgen */
        $course = $gen->create_course()->id;
        $f2f = $f2fgen->create_instance([
            'name' => 'Test seminar',
            'course' => $course
        ]);
        // JSON.
        $details = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","marks":[{"type":"link","attrs":{"href":"https://www.totaralearning.com/products"}}],"text":"Test JSON"}]}]}';
        $evtid = $f2fgen->add_session(['facetoface' => $f2f->id, 'sessiondates' => [], 'details' => $details]);
        $this->assertEquals($evtid, $method->invoke(null, $details));
        // Emojis in BMP.
        $details = "\u{266a}\u{266b}\u{2669}\u{269e}\u{263a}\u{269f}\u{2669}\u{266b}\u{266a}";
        $evtid = $f2fgen->add_session(['facetoface' => $f2f->id, 'sessiondates' => [], 'details' => $details]);
        $this->assertEquals($evtid, $method->invoke(null, $details));
        // Does not exist.
        try {
            $method->invoke(null, 'he who must not exist');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString("event 'he who must not exist' does not exist", $ex->getMessage());
        }
    }
}
