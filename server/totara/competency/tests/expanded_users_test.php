<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @category test
 */

use totara_competency\expanded_users;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/expanded_users_testcase.php';

/**
 * @group totara_competency
 */
class core_expanded_users_testcase extends expanded_users_testcase {

    /**
     * Deliberately packing all those asserts into one test method as setting up the data
     * is quite heavy and performance costs would outweigh the readability costs here
     */
    public function test_expanded_users() {
        $data = $this->generate_data();

        // None
        $expanded_users = (new expanded_users())->fetch_paginated(0);
        $this->assertEquals(0, $expanded_users->count());

        // Combination of all
        $expanded_users = (new expanded_users())
            ->set_audience_ids([$data->cohort1->id])
            ->set_organisation_ids([$data->org2->id])
            ->set_position_ids([$data->pos1->id])
            ->set_user_ids([$data->user18->id, $data->user16->id])
            ->fetch_paginated(0);

        $this->assertEquals(10, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user1, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user2, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user3, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user14, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user15, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user6, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user7, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user8, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user16, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user18, ['Individual']);

        // Just cohorts
        $expanded_users = (new expanded_users())
            ->set_audience_ids([$data->cohort1->id])
            ->fetch_paginated(0);

        $this->assertEquals(3, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user1, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user2, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user3, [$data->cohort1->name]);

        // Just organisations
        $expanded_users = (new expanded_users())
            ->set_organisation_ids([$data->org2->id])
            ->fetch_paginated(0);

        $this->assertEquals(2, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user14, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user15, [$data->org2->fullname]);

        // Just positions
        $expanded_users = (new expanded_users())
            ->set_position_ids([$data->pos1->id])
            ->fetch_paginated(0);

        $this->assertEquals(3, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user6, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user7, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user8, [$data->pos1->fullname]);

        // Just individuals
        $expanded_users = (new expanded_users())
            ->set_user_ids([$data->user18->id, $data->user16->id])
            ->fetch_paginated(0);

        $this->assertEquals(2, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user16, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user18, ['Individual']);

        // Pos and Org
        $expanded_users = (new expanded_users())
            ->set_organisation_ids([$data->org2->id])
            ->set_position_ids([$data->pos1->id])
            ->fetch_paginated(0);

        $this->assertEquals(5, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user14, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user15, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user6, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user7, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user8, [$data->pos1->fullname]);

        // Cohort and Org
        $expanded_users = (new expanded_users())
            ->set_organisation_ids([$data->org2->id])
            ->set_audience_ids([$data->cohort1->id])
            ->fetch_paginated(0);

        $this->assertEquals(5, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user14, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user15, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user1, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user2, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user3, [$data->cohort1->name]);

        // Individual and Cohort
        $expanded_users = (new expanded_users())
            ->set_user_ids([$data->user18->id, $data->user16->id])
            ->set_audience_ids([$data->cohort1->id])
            ->fetch_paginated(0);

        $this->assertEquals(5, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user16, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user18, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user1, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user2, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user3, [$data->cohort1->name]);

        // Individual and Pos
        $expanded_users = (new expanded_users())
            ->set_user_ids([$data->user18->id, $data->user16->id])
            ->set_position_ids([$data->pos1->id])
            ->fetch_paginated(0);

        $this->assertEquals(5, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user16, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user18, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user6, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user7, [$data->pos1->fullname]);
        $this->assert_has_user($expanded_users, $data->user8, [$data->pos1->fullname]);

        // Individual and Org
        $expanded_users = (new expanded_users())
            ->set_user_ids([$data->user18->id, $data->user16->id])
            ->set_organisation_ids([$data->org2->id])
            ->fetch_paginated(0);

        $this->assertEquals(4, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user16, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user18, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user14, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user15, [$data->org2->fullname]);


        // Multiple user groups

        // add another user to cohort
        cohort_add_member($data->cohort1->id, $data->user16->id);

        $expanded_users = (new expanded_users())
            ->set_user_ids([$data->user18->id, $data->user16->id])
            ->set_audience_ids([$data->cohort1->id])
            ->fetch_paginated(0);

        $this->assertEquals(5, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user16, ['Individual', $data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user18, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user1, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user2, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user3, [$data->cohort1->name]);

        // add another user to the organisation
        job_assignment::create([
            'userid' => $data->user16->id,
            'idnumber' => 'dev6',
            'organisationid' => $data->org2->id
        ]);
        // add user to another organisation which is not part of the query
        // this organisation should not turn up
        job_assignment::create([
            'userid' => $data->user16->id,
            'idnumber' => 'dev7',
            'organisationid' => $data->org1->id
        ]);

        $expanded_users = (new expanded_users())
            ->set_user_ids([$data->user18->id, $data->user16->id])
            ->set_audience_ids([$data->cohort1->id])
            ->set_organisation_ids([$data->org2->id])
            ->fetch_paginated(0);

        $this->assertEquals(7, $expanded_users->count());
        $this->assert_has_user($expanded_users, $data->user16, ['Individual', $data->cohort1->name, $data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user18, ['Individual']);
        $this->assert_has_user($expanded_users, $data->user1, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user2, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user3, [$data->cohort1->name]);
        $this->assert_has_user($expanded_users, $data->user14, [$data->org2->fullname]);
        $this->assert_has_user($expanded_users, $data->user15, [$data->org2->fullname]);
    }

    public function test_expanded_users_filtered_by_name() {
        $data = $this->generate_data();

        // Combination of all
        $target_user = $data->user20;
        $expanded_users = (new expanded_users())
            ->set_audience_ids([$data->cohort1->id])
            ->set_organisation_ids([$data->org2->id])
            ->set_position_ids([$data->pos1->id])
            ->set_user_ids([$data->user18->id, $target_user->id])
            ->filter_by_name($target_user->firstname)
            ->fetch_paginated(0);

        foreach ($expanded_users as $user) {
            $this->assertMatchesRegularExpression("/{$target_user->firstname}/i", $user['firstname']);
        }
    }

    /**
     * Tests a bug in the previous test caused by case-sensitive matching.
     */
    public function test_expanded_users_filtered_by_name_case_sensitive() {
        $data = $this->generate_data();

        // Combination of all
        $expanded_users = (new expanded_users())
            ->set_audience_ids([$data->cohort1->id])
            ->set_organisation_ids([$data->org2->id])
            ->set_position_ids([$data->pos1->id])
            ->set_user_ids([$data->user19->id, $data->user20->id])
            ->filter_by_name($data->user19->firstname)
            ->fetch_paginated(0);

        $this->assertCount(2, $expanded_users);
        foreach ($expanded_users as $user) {
            if ($user['firstname'] == 'Tomaru') {
                $this->assertDoesNotMatchRegularExpression("/{$data->user19->firstname}/", $user['firstname']);
                $this->assertMatchesRegularExpression("/{$data->user19->firstname}/i", $user['firstname']);
            } else {
                $this->assertMatchesRegularExpression("/{$data->user19->firstname}/", $user['firstname']);
            }
        }
    }

}
