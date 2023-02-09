<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use totara_competency\entity\assignment;
use totara_competency\models\assignment_actions;
use totara_competency\user_groups;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/assignment_actions_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_actions_create_testcase extends totara_competency_assignment_actions_testcase {

    /**
     * @dataProvider previously_archived_assignments_can_be_recreated_provider
     * @param int $recreate_with_status
     * @throws coding_exception
     */
    public function test_previously_archived_assignments_can_be_recreated(int $recreate_with_status): void {
        $competency = $this->generator()->create_competency();
        $user = $this->generator()->assignment_generator()->create_user();
        $model = new assignment_actions();

        // Create a fresh assignment.
        $affected_ids = $model->create_from_competencies(
            [$competency->id],
            [user_groups::USER => [$user->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        )->pluck('id');

        $original_assignment = end($affected_ids);

        static::assertCount(1, $affected_ids);

        // We should not be able to create the exact same assignment again.
        $affected_ids = $model->create_from_competencies(
            [$competency->id],
            [user_groups::USER => [$user->id]],
            assignment::TYPE_ADMIN
        )->pluck('id');

        // Note this fails silently, returning 0 affected ids rather than throwing an exception.
        // This is due to the assignment actions class being designed to work on partially failing batch operations.
        static::assertCount(0, $affected_ids);

        // Archive the original assignment.
        $affected_ids = $model->archive($original_assignment);
        static::assertCount(1, $affected_ids);

        // Now that the original assignment is archived, we should be able to create the exact same assignment again.
        $affected_ids = $model->create_from_competencies(
            [$competency->id],
            [user_groups::USER => [$user->id]],
            assignment::TYPE_ADMIN,
            $recreate_with_status
        )->pluck('id');

        static::assertCount(1, $affected_ids);
    }

    public function previously_archived_assignments_can_be_recreated_provider(): array {
        return [
            'Recreate with draft status' => [assignment::STATUS_DRAFT],
            'Recreate with active status' => [assignment::STATUS_ACTIVE],
        ];
    }

}