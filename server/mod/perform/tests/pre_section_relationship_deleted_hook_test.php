<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\hook\pre_section_relationship_deleted;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_relationship;
use totara_core\relationship\relationship;
use mod_perform\testing\generator as mod_perform_generator;

/**
 * @group perform
 */
class pre_section_relationship_deleted_hook_testcase extends advanced_testcase {

    public function test_hook_is_triggered() {
        self::setAdminUser();

        /** @var mod_perform_generator $perform_generator*/
        $perform_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $activity = $perform_generator->create_activity_in_container();

        /** @var section $section*/
        $section = $activity->get_sections()->first();
        $core_relationship_id = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT)->id;
        $section->update_relationships([
            [
                'core_relationship_id' => $core_relationship_id,
                'can_view' => 1,
                'can_answer' => 1
            ]
        ]);
        $hook_sink = $this->redirectHooks();
        section_relationship::delete_with_properties($section->id, $core_relationship_id);

        $hooks = $hook_sink->get_hooks();
        $this->assertCount(1, $hooks);

        $hook = reset($hooks);
        $this->assertTrue($hook instanceof pre_section_relationship_deleted);
    }
}