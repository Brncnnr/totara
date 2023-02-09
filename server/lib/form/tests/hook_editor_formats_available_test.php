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
 * @author  Chris Snyder <chris.snyder@totaralearning.com>
 * @package core_form
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_form_hook_editor_formats_available_testcase
 *
 * Tests the core_form hook which allows plugins to modify the list of textarea formats available.
 *
 */
class core_form_hook_editor_formats_available_testcase extends advanced_testcase {

    public function test_editor_formats_available_hook() {
        $self = $this;
        $hook = function ($hook) use ($self) {
            $self->assertInstanceOf(core_form\hook\editor_formats_available::class, $hook);

            // Test get_options().
            $options = $hook->get_options();
            $self->assertNotEmpty($options);
            $self->assertIsArray($options);
            $self->assertCount(1, $options);
            $self->assertTrue($options['option']);

            // Test get_values().
            $values = $hook->get_values();
            $self->assertNotEmpty($values);
            $self->assertIsArray($values);
            $self->assertCount(1, $values);
            $self->assertEquals('The quick brown fox jumped over the lazy dog.', $values['text']);

            // Test get_formats().
            $formats = $hook->get_formats();
            $self->assertNotEmpty($formats);
            $self->assertIsArray($formats);
            $self->assertCount(3, $formats);
            $self->assertEquals('BAR', $formats['3']);

            // Setup test of set_format().
            $hook->set_format(5, 'QUUX');

            // Setup test of remove_format()
            $hook->remove_format(7);
        };

        $watchers = array(
            array(
                'hookname' => 'core_form\hook\editor_formats_available',
                'callback' => $hook,
            ),
        );
        totara_core\hook\manager::phpunit_replace_watchers($watchers);

        $formats = ['1' => 'FOO', '3' => 'BAR', '7' => 'LEMMA'];
        $options = ['option' => true];
        $values = ['text' => 'The quick brown fox jumped over the lazy dog.'];

        $self->assertCount(3, $formats);
        $self->assertEquals('BAR', $formats['3']);
        $self->assertNotEmpty($formats[7]);

        $instance = new core_form\hook\editor_formats_available($options, $values, $formats);
        $instance->execute();
        $formats = $instance->get_formats();

        // Finish tests of set_format() and remove_format().
        $self->assertCount(3, $formats);
        $self->assertEquals('QUUX', $formats['5']);
        $self->assertTrue(empty($formats[7]));

        totara_core\hook\manager::phpunit_reset();
    }
}
