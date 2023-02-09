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

defined('MOODLE_INTERNAL') || die;

/**
 * Test renderer class.
 */
class totara_msteams_renderer_testcase extends advanced_testcase {
    /**
     * Ensure that render_my_learning() succeeds.
     */
    public function test_render_my_learning() {
        global $CFG, $PAGE;
        require_once($CFG->dirroot.'/totara/msteams/renderer.php');
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $course = $gen->create_course(['fullname' => 'Akoranga Tautokorua']);
        $gen->enrol_user($user->id, $course->id);

        // The rendering result is not very important.
        // If the block_current_learning is updated to require a real instance->id,
        // this test will be able to catch a regression in the totara_msteams_renderer.
        $this->setUser($user);
        $PAGE->set_context(context_system::instance());
        $renderer = new totara_msteams_renderer($PAGE, null);
        $result = $renderer->render_my_learning();
        $this->assertStringContainsString('Akoranga Tautokorua', $result);
    }
}
