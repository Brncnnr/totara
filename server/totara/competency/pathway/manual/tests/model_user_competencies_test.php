<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

use core\orm\query\builder;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use pathway_manual\models\user_competencies;
use totara_competency\expand_task;
use totara_competency\models\assignment;
use totara_competency\user_groups;
use totara_job\job_assignment;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_model_user_competencies_testcase extends pathway_manual_base_testcase {

    /**
     * Test that can_rate_competencies_for_user() checks if there are any competencies the current user
     * is allowed to rate for themselves.
     */
    public function test_can_rate_competencies_self() {
        $role = builder::table('role')->where('shortname', 'user')->one()->id;
        $context = context_user::instance($this->user1->id);

        $this->setUser($this->user1->id);

        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $manual = $this->generator->create_manual($this->competency1, [self_role::class]);

        // Has capability, has the role and has at least one assigned competency
        $this->assertTrue(user_competencies::can_rate_competencies($this->user1->id));

        unassign_capability('totara/competency:rate_own_competencies', $role);

        // No longer has capability
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));

        assign_capability('totara/competency:rate_own_competencies', CAP_ALLOW, $role, $context->id);
        $assignment = assignment::load_by_id($assignment->id);
        $assignment->archive();

        // No longer has any assigned competencies
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));

        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();
        $manual->delete();

        // No longer has any 'self' manual rating pathways
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));
    }

    /**
     * Test that can_rate_competencies() checks if there are any competencies the
     * current user is allowed to rate for someone else.
     */
    public function test_can_rate_competencies_other() {
        $role = builder::table('role')->where('shortname', 'staffmanager')->one()->id;
        $context = context_user::instance($this->user1->id);

        $this->setUser($this->user2->id);

        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $manager_ja = job_assignment::create_default($this->user2->id);
        job_assignment::create(['userid' => $this->user1->id, 'managerjaid' => $manager_ja->id, 'idnumber' => 1]);

        $manual_manager = $this->generator->create_manual($this->competency1, [manager::class]);
        $this->generator->create_manual($this->competency1, [appraiser::class]);

        // Has capability, has the role and has at least one assigned competency
        $this->assertTrue(user_competencies::can_rate_competencies($this->user1->id));

        unassign_capability('totara/competency:rate_other_competencies', $role);

        // No longer has capability
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));

        assign_capability('totara/competency:rate_other_competencies', CAP_ALLOW, $role, $context->id);
        $assignment = assignment::load_by_id($assignment->id);
        $assignment->archive();

        // No longer has any assigned competencies
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));

        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();
        $manual_manager->delete();

        // No longer has any 'manager' manual rating pathways
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));

        job_assignment::create(['userid' => $this->user1->id, 'appraiserid' => $this->user2->id, 'idnumber' => 2]);

        // Is now an appraiser
        $this->assertTrue(user_competencies::can_rate_competencies($this->user1->id));
    }

    /**
     * Test that can_rate_competencies() checks if there are any competencies an appraiser without any additional
     * capabilities is allowed to rate for someone else.
     */
    public function test_can_rate_competencies_appraiser_only() {
        $context = context_user::instance($this->user1->id);

        $this->setUser($this->user2->id);

        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $manual_appraiser = $this->generator->create_manual($this->competency1, [appraiser::class]);
        job_assignment::create(['userid' => $this->user1->id, 'appraiserid' => $this->user2->id, 'idnumber' => 2]);

        // Appraiser without any capability.
        $this->assertTrue(user_competencies::can_rate_competencies($this->user1->id));
        $assignment = assignment::load_by_id($assignment->id);
        $assignment->archive();

        // No longer has any assigned competencies
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));

        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();
        $manual_appraiser->delete();

        // No longer has any 'appraiser' manual rating pathways
        $this->assertFalse(user_competencies::can_rate_competencies($this->user1->id));
    }

}
