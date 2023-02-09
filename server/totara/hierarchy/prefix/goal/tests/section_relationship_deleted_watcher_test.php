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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

use mod_perform\constants;
use mod_perform\models\activity\section_relationship;
use mod_perform\section_relationship_deletion_exception;
use totara_core\relationship\relationship;

require_once __DIR__ . '/perform_linked_goals_base_testcase.php';

/**
 * @group hierarchy_goal
 */
class section_relationship_deleted_watcher_testcase extends perform_linked_goals_base_testcase {

    public function test_watcher_for_status_changer_relationship(): void {
        $data = $this->create_activity_data(goal::SCOPE_PERSONAL);

        $appraiser_relationship_id = relationship::load_by_idnumber(constants::RELATIONSHIP_APPRAISER)->id;
        $manager_relationship_id = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->id;

        // Appraiser can be removed because it's neither selector nor rater.
        self::assertTrue(
            section_relationship::delete_with_properties($data->section->id, $appraiser_relationship_id)
        );

        // Manager is status changer, so cannot be removed.
        $this->expectException(section_relationship_deletion_exception::class);
        $this->expectExceptionMessage(
            'Cannot remove relationship (One or more participant(s) cannot be removed from this section '
                . 'because they are referenced in the following question'
        );
        section_relationship::delete_with_properties($data->section->id, $manager_relationship_id);
    }
}
