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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_tenant
 */

use totara_tenant\local\util as local_util;
use core\record\tenant;
use totara_tenant\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering tenant utility class.
 */
class totara_tenant_util_testcase extends advanced_testcase {

    public function test_two_users_sharing_same_tenant() {
        $generator = $this->getDataGenerator();

        $tenant_generator = \totara_tenant\testing\generator::instance();
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $system_user1 = $generator->create_user();
        $system_user1_context = context_user::instance($system_user1->id);

        $system_user2 = $generator->create_user();
        $system_user2_context = context_user::instance($system_user2->id);

        $participant_user1 = $generator->create_user();
        $participant_user1_context = context_user::instance($participant_user1->id);
        $participant_user2 = $generator->create_user();
        $participant_user2_context = context_user::instance($participant_user2->id);
        $participant_user3 = $generator->create_user();
        $participant_user3_context = context_user::instance($participant_user3->id);

        local_util::set_user_participation($participant_user1->id, [$tenant1->id]);
        local_util::set_user_participation($participant_user2->id, [$tenant2->id]);
        // participant 3 is in both tenants
        local_util::set_user_participation($participant_user3->id, [$tenant1->id, $tenant2->id]);

        $tenant1_user1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $tenant1_user1_context = context_user::instance($tenant1_user1->id);
        $tenant1_user2 = $generator->create_user(['tenantid' => $tenant1->id]);
        $tenant1_user2_context = context_user::instance($tenant1_user2->id);

        $tenant2_user1 = $generator->create_user(['tenantid' => $tenant2->id]);
        $tenant2_user1_context = context_user::instance($tenant2_user1->id);
        $tenant2_user2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $tenant2_user2_context = context_user::instance($tenant2_user2->id);

        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_user1_context, $tenant1_user2_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_user2_context, $tenant1_user1_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($participant_user1_context, $participant_user3_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($participant_user3_context, $participant_user1_context));

        // Participants of different tenants but both system users
        $this->assertTrue(util::do_contexts_share_same_tenant($participant_user1_context, $participant_user2_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($participant_user2_context, $participant_user1_context));

        // Both are system users only
        $this->assertTrue(util::do_contexts_share_same_tenant($system_user1_context, $system_user2_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($system_user2_context, $system_user1_context));

        // Members of different tenants
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_user1_context, $tenant2_user1_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_user1_context, $tenant2_user2_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_user2_context, $tenant2_user1_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_user2_context, $tenant2_user2_context));

        // Now try it with non-user context
        $tenant1_category_context = context_coursecat::instance($tenant1->categoryid);
        $tenant2_category_context = context_coursecat::instance($tenant2->categoryid);

        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_user1_context, $tenant1_category_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_category_context, $tenant1_user1_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_user2_context, $tenant1_category_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_category_context, $tenant1_user2_context));

        $this->assertTrue(util::do_contexts_share_same_tenant($tenant2_user1_context, $tenant2_category_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant2_category_context, $tenant2_user1_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant2_user2_context, $tenant2_category_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant2_category_context, $tenant2_user2_context));

        $this->assertTrue(util::do_contexts_share_same_tenant($participant_user1_context, $tenant1_category_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_category_context, $participant_user1_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($participant_user3_context, $tenant1_category_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant1_category_context, $participant_user3_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($participant_user3_context, $tenant2_category_context));
        $this->assertTrue(util::do_contexts_share_same_tenant($tenant2_category_context, $participant_user3_context));

        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_user1_context, $tenant2_category_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant2_category_context, $tenant1_user1_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_user2_context, $tenant2_category_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant2_category_context, $tenant1_user2_context));

        $this->assertFalse(util::do_contexts_share_same_tenant($tenant2_user1_context, $tenant1_category_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_category_context, $tenant2_user1_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant2_user2_context, $tenant1_category_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_category_context, $tenant2_user2_context));

        $this->assertFalse(util::do_contexts_share_same_tenant($participant_user1_context, $tenant2_category_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant2_category_context, $participant_user1_context));

        $this->assertFalse(util::do_contexts_share_same_tenant($participant_user2_context, $tenant1_category_context));
        $this->assertFalse(util::do_contexts_share_same_tenant($tenant1_category_context, $participant_user2_context));
    }

}
