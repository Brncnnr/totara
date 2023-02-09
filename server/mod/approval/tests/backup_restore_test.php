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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use container_approval\approval;
use core\entity\user;
use core\entity\user as user_entity;
use core_phpunit\testcase;
use mod_approval\interactor\category_interactor;
use mod_approval\model\workflow\workflow;
use mod_approval\model\backup_restore;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\backup_restore
 */
class mod_approval_backup_restore_testcase extends testcase {

    use approval_workflow_test_setup;

    public function test_backup_restore(): void {
        $this->setAdminUser();
        list($workflow, ,) = $this->create_workflow_and_assignment();
        $category_interactor = new category_interactor(approval::get_default_category_context(), user_entity::logged_in()->id);
        $this->assertTrue($category_interactor->has_clone_workflow_capability());

        $restore = new backup_restore(user::logged_in());
        $new_course = $restore->execute(
            $workflow->course_id,
            true
        );
        // Load cloned workflow
        $new_workflow = workflow::load_by_course_id($new_course->id);
        $this->assertNotEquals($new_workflow->id, $workflow->id);
        $this->assertEquals($new_workflow->name, $workflow->name);
    }
}
