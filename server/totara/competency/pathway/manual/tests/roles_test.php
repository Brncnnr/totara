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

use core\entity\user;
use pathway_manual\models\roles;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\role_factory;
use pathway_manual\models\roles\self_role;
use totara_job\job_assignment;

/**
 * @group totara_competency
 */
class pathway_manual_roles_testcase extends advanced_testcase {

    /**
     * @var role[]|string[]
     */
    private $all_role_classes;

    protected function setUp(): void {
        parent::setUp();
        $this->all_role_classes = \core_component::get_namespace_classes(
            'models\roles',
            role::class,
            'pathway_manual'
        );
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->all_role_classes = null;
    }

    /**
     * Roles must have a unique name.
     */
    public function test_role_names_are_unique() {
        $names = array_map(function ($role) {
            /** @var role $role */
            return $role::get_name();
        }, $this->all_role_classes);

        $unique_names = array_unique($names);

        if (count($unique_names) !== count($names)) {
            $this->fail('All subclasses of pathway_manual\models\roles\role must have a unique value in get_name()!');
        }
    }

    /**
     * Roles must have a unique display order.
     */
    public function test_role_display_orders_are_unique() {
        $display_orders = array_map(function ($role) {
            /** @var role $role */
            return $role::get_display_order();
        }, $this->all_role_classes);

        $unique_display_orders = array_unique($display_orders);

        if (count($display_orders) !== count($unique_display_orders)) {
            $this->fail('All subclasses of pathway_manual\models\roles\role must have a unique value in get_display_order()!');
        }
    }

    /**
     * Roles must have a display name - a non-empty (and therefore readable & clickable) string.
     */
    public function test_role_display_names_exist() {
        $display_names = array_filter($this->all_role_classes, function ($role) {
            /** @var role $role */
            return strlen($role::get_display_name()) > 0;
        });

        if (count($this->all_role_classes) !== count($display_names)) {
            $this->fail('All subclasses of pathway_manual\models\roles\role must have a non-empty string in get_display_order()!');
        }
    }

    /**
     * All available roles must be able to be created in the role_factory class.
     */
    public function test_roles_can_be_created() {
        foreach ($this->all_role_classes as $role_class) {
            /** @var role $role_class */
            $this->assertInstanceOf($role_class, role_factory::create($role_class));
            $this->assertInstanceOf($role_class, role_factory::create($role_class::get_name()));
        }
    }

    /**
     * All available roles must be able to be created in the role_factory class.
     */
    public function test_multiple_roles_can_be_created() {
        $created_role_classes = role_factory::create_multiple($this->all_role_classes);
        $this->assertCount(3, $created_role_classes);
        foreach ($created_role_classes as $role_class) {
            $this->assertInstanceOf(role::class, $role_class);
        }

        $role_names = array_map(function ($role) {
            /** @var role $role */
            return $role::get_name();
        }, $this->all_role_classes);
        $created_role_classes = role_factory::create_multiple($role_names);
        $this->assertCount(3, $created_role_classes);
        foreach ($created_role_classes as $role_class) {
            $this->assertInstanceOf(role::class, $role_class);
        }
    }

    /**
     * Make sure that when creating multiple roles, there is only one object per role,
     * and they are sorted by display order, and that the display orders are sequential.
     */
    public function test_create_multiple_unique_and_sorted_by_display_order() {
        $roles = array_merge($this->all_role_classes, $this->all_role_classes);
        $this->assertCount(count($this->all_role_classes) * 2, $roles);

        $created_roles = role_factory::create_multiple($roles);
        $this->assertCount(count($this->all_role_classes), $created_roles);

        $previous_display_order = -9999;
        for ($i = 0; $i < count($this->all_role_classes); $i++) {
            $this->assertGreaterThan($previous_display_order, $created_roles[$i]::get_display_order());
            $previous_display_order = $created_roles[$i]::get_display_order();
        }
    }

    /**
     * Test getting a single instance of every available role, sorted by their display order.
     */
    public function test_get_all_roles() {
        $all_roles = role_factory::create_all();
        $this->assertCount(count($this->all_role_classes), $all_roles);

        $previous_display_order = -9999;
        for ($i = 0; $i < count($this->all_role_classes); $i++) {
            $this->assertGreaterThan($previous_display_order, $all_roles[$i]::get_display_order());
            $previous_display_order = $all_roles[$i]::get_display_order();
        }
    }

    /**
     * Make sure that specifying an invalid role returns false, or throws an exception when validation is on
     */
    public function test_roles_exist_with_invalid_roles() {
        $invalid_role_name = 'not_a_role';

        $this->assertFalse(role_factory::roles_exist($invalid_role_name, false));
        $this->assertFalse(role_factory::roles_exist([$invalid_role_name, $invalid_role_name], false));

        $this->expectExceptionMessageMatches("|Invalid role specified: '{$invalid_role_name}'|");
        $this->assertFalse(role_factory::roles_exist($invalid_role_name, true));
    }

    /**
     * Make sure we get a helpful exception if we call the has_role() method on a role without specifying a subject user beforehand.
     */
    public function test_has_role_without_specifying_subject_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertTrue((new self_role())->set_subject_user($user->id)->has_role());

        $this->expectExceptionMessageMatches("|Must set the subject user with set_subject_user()|");
        (new self_role())->has_role();
    }

    /**
     * Make sure that get_roles_for_competency() gets sorted list of available roles without duplicates for the given competency.
     */
    public function test_get_roles_for_competency() {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $competency_1 = $generator->create_competency();
        $generator->create_manual($competency_1, [appraiser::class, self_role::class]);
        $generator->create_manual($competency_1, [self_role::class]);

        $competency_2 = $generator->create_competency();
        $generator->create_manual($competency_2, [manager::class]);

        $competency_1_roles = roles::get_roles_for_competency($competency_1->id);
        $competency_2_roles = roles::get_roles_for_competency($competency_2->id);

        $this->assertCount(2, $competency_1_roles);
        $this->assertInstanceOf(self_role::class, $competency_1_roles[0]);
        $this->assertInstanceOf(appraiser::class, $competency_1_roles[1]);

        $this->assertCount(1, $competency_2_roles);
        $this->assertInstanceOf(manager::class, $competency_2_roles[0]);
    }

    /**
     * Make sure that get_competencies_with_role() gets sorted list of available roles without duplicates for the given competency.
     */
    public function test_get_competencies_with_role() {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $competency_1 = $generator->create_competency();
        $generator->create_manual($competency_1, [appraiser::class, self_role::class]);
        $generator->create_manual($competency_1, [self_role::class]);

        $competency_2 = $generator->create_competency();
        $generator->create_manual($competency_2, [manager::class, appraiser::class]);

        $self_competencies = roles::get_competencies_with_role(self_role::class);
        $this->assertCount(1, $self_competencies);
        $this->assertEquals($competency_1->id, $self_competencies->all()[0]->id);

        $manager_competencies = roles::get_competencies_with_role(manager::class);
        $this->assertCount(1, $manager_competencies);
        $this->assertEquals($competency_2->id, $manager_competencies->all()[0]->id);

        $appraiser_competencies = roles::get_competencies_with_role(appraiser::class);
        $this->assertCount(2, $appraiser_competencies);
        $this->assertEquals($competency_1->id, $appraiser_competencies->all()[0]->id);
        $this->assertEquals($competency_2->id, $appraiser_competencies->all()[1]->id);

        $this->assertTrue(roles::competency_has_role($competency_1->id,self_role::class));
        $this->assertFalse(roles::competency_has_role($competency_2->id,self_role::class));
        $this->assertFalse(roles::competency_has_role($competency_1->id,manager::class));
        $this->assertTrue(roles::competency_has_role($competency_2->id,manager::class));
        $this->assertTrue(roles::competency_has_role($competency_1->id,appraiser::class));
        $this->assertTrue(roles::competency_has_role($competency_2->id,appraiser::class));
    }

    /**
     * Make sure that competency_has_role() correctly checks if a competency has the specified role.
     */
    public function test_competency_has_role() {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $competency_1 = $generator->create_competency();
        $generator->create_manual($competency_1, [appraiser::class, self_role::class]);
        $generator->create_manual($competency_1, [self_role::class]);

        $competency_2 = $generator->create_competency();
        $generator->create_manual($competency_2, [manager::class, appraiser::class]);

        $this->assertTrue(roles::competency_has_role($competency_1->id,self_role::class));
        $this->assertFalse(roles::competency_has_role($competency_1->id,manager::class));
        $this->assertTrue(roles::competency_has_role($competency_1->id,appraiser::class));

        $this->assertFalse(roles::competency_has_role($competency_2->id,self_role::class));
        $this->assertTrue(roles::competency_has_role($competency_2->id,manager::class));
        $this->assertTrue(roles::competency_has_role($competency_2->id,appraiser::class));
    }

    /**
     * Make sure that get_current_user_roles() correctly gets all the roles a user has.
     */
    public function test_get_current_user_roles() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $managerja = job_assignment::create_default($user1->id);
        job_assignment::create([
            'userid' => $user2->id,
            'idnumber' => 'a',
            'managerjaid' => $managerja->id
        ]);
        job_assignment::create([
            'userid' => $user2->id,
            'idnumber' => 'b',
            'appraiserid' => $user1->id,
        ]);

        $this->setUser($user1);
        $user1_roles_for_user1 = roles::get_current_user_roles($user1->id);
        $user1_roles_for_user2 = roles::get_current_user_roles($user2->id);

        $this->assertCount(1, $user1_roles_for_user1);
        $this->assertInstanceOf(self_role::class, reset($user1_roles_for_user1));

        $this->assertCount(2, $user1_roles_for_user2);
        $this->assertInstanceOf(manager::class, reset($user1_roles_for_user2));
        $this->assertInstanceOf(appraiser::class, end($user1_roles_for_user2));
    }

    /**
     * Make sure that require_user_has_role() throws an exception if the user doesn't have the role.
     */
    public function test_require_user_has_role() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        foreach ($this->all_role_classes as $role) {
            try {
                $role::require_for_user($user2->id);
                $this->fail("Expected {$role}::require_for_user() to throw an exception but it didn't");
            } catch (moodle_exception $exception) {
                $this->assertEquals(
                    'You do not have the ' . $role::get_display_name() . ' role for this user',
                    $exception->getMessage()
                );
            }
        }
    }

    /**
     * Make sure that the self role correctly checks if the current user is logged in as the specified user
     */
    public function test_has_for_user_self() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        // Always false.
        $this->assertFalse(self_role::has_for_any());

        $this->assertTrue(self_role::has_for_user($user1->id));
        $this->assertTrue((new self_role())->set_subject_user($user1->id)->has_role());
        $this->assertFalse(self_role::has_for_user($user2->id));
        $this->assertFalse((new self_role())->set_subject_user($user2->id)->has_role());

        $this->setUser($user2);

        $this->assertFalse(self_role::has_for_user($user1->id));
        $this->assertFalse((new self_role())->set_subject_user($user1->id)->has_role());
        $this->assertTrue(self_role::has_for_user($user2->id));
        $this->assertTrue((new self_role())->set_subject_user($user2->id)->has_role());
    }

    /**
     * Make sure that the manager role correctly checks if the current user is the manager of the specified user
     */
    public function test_has_for_user_manager() {
        $manager = $this->getDataGenerator()->create_user();
        $staff = $this->getDataGenerator()->create_user();

        $this->setUser($manager);

        // Manager user is not the manager of the staff user yet
        $this->assertFalse(manager::has_for_any());
        $this->assertFalse(manager::has_for_user($staff->id));
        $this->assertFalse((new manager())->set_subject_user($staff->id)->has_role());
        $this->assertFalse(manager::has_for_user($manager->id));
        $this->assertFalse((new manager())->set_subject_user($manager->id)->has_role());

        $managerja = job_assignment::create_default($manager->id);
        job_assignment::create([
            'userid' => $staff->id,
            'idnumber' => 'a',
            'managerjaid' => $managerja->id
        ]);

        // Manager user is now the manager of the staff user
        $this->assertTrue(manager::has_for_any());
        $this->assertTrue(manager::has_for_user($staff->id));
        $this->assertTrue((new manager())->set_subject_user($staff->id)->has_role());
        $this->assertFalse(manager::has_for_user($manager->id));
        $this->assertFalse((new manager())->set_subject_user($manager->id)->has_role());

        $this->setUser($staff);

        // Staff user is not a manager
        $this->assertFalse(manager::has_for_any());
        $this->assertFalse(manager::has_for_user($staff->id));
        $this->assertFalse((new manager())->set_subject_user($staff->id)->has_role());
        $this->assertFalse(manager::has_for_user($manager->id));
        $this->assertFalse((new manager())->set_subject_user($manager->id)->has_role());
    }

    /**
     * Make sure that the appraiser role correctly checks if the current user is the appraiser of the specified user
     */
    public function test_has_for_user_appraiser() {
        $appraiser = $this->getDataGenerator()->create_user();
        $staff = $this->getDataGenerator()->create_user();

        $this->setUser($appraiser);

        // Appraiser user is not the appraiser of the staff user yet
        $this->assertFalse(appraiser::has_for_user($staff->id));
        $this->assertFalse((new appraiser())->set_subject_user($staff->id)->has_role());
        $this->assertFalse(appraiser::has_for_user($appraiser->id));
        $this->assertFalse((new appraiser())->set_subject_user($appraiser->id)->has_role());

        job_assignment::create([
            'userid' => $staff->id,
            'idnumber' => 'a',
            'appraiserid' => $appraiser->id,
        ]);

        // Appraiser user is now the appraiser of the staff user
        $this->assertTrue(appraiser::has_for_user($staff->id));
        $this->assertTrue((new appraiser())->set_subject_user($staff->id)->has_role());
        $this->assertFalse(appraiser::has_for_user($appraiser->id));
        $this->assertFalse((new appraiser())->set_subject_user($appraiser->id)->has_role());

        $this->setUser($staff);

        // Staff user is not an appraiser
        $this->assertFalse(appraiser::has_for_user($staff->id));
        $this->assertFalse((new appraiser())->set_subject_user($staff->id)->has_role());
        $this->assertFalse(appraiser::has_for_user($appraiser->id));
        $this->assertFalse((new appraiser())->set_subject_user($appraiser->id)->has_role());
    }

    public function test_apply_role_restriction_to_builder_for_manager() {
        // Delete all other users created outside of this test so we have predictable results
        user::repository()->delete();

        $high_manager = $this->getDataGenerator()->create_user();
        $middle_manager = $this->getDataGenerator()->create_user();
        $staff = $this->getDataGenerator()->create_user();
        $not_staff = $this->getDataGenerator()->create_user();

        // $high_manager is manager of $middle_manager
        $middle_ja = job_assignment::create([
            'userid' => $middle_manager->id,
            'idnumber' => '1',
            'managerjaid' => job_assignment::create_default($high_manager->id)->id,
        ]);

        // $middle_manager is manager of $staff
        job_assignment::create([
            'userid' => $staff->id,
            'idnumber' => '2',
            'managerjaid' => $middle_ja->id,
        ]);

        $user_repository = user::repository();
        $this->assertEquals(4, $user_repository->count());

        // Only $middle_manager is being managed by $high_manager
        $this->setUser($high_manager);
        manager::apply_role_restriction_to_builder($user_repository);
        $results = $user_repository->get()->all();
        $this->assertCount(1, $results);
        $this->assertEquals($middle_manager->id, $results[0]->id);

        // Only $staff is being managed by $middle_manager
        $user_repository = user::repository();
        $this->setUser($middle_manager);
        manager::apply_role_restriction_to_builder($user_repository);
        $results = $user_repository->get()->all();
        $this->assertCount(1, $results);
        $this->assertEquals($staff->id, $results[0]->id);

        // $staff has no direct reports
        $user_repository = user::repository();
        $this->setUser($staff);
        manager::apply_role_restriction_to_builder($user_repository);
        $results = $user_repository->get()->all();
        $this->assertCount(0, $results);
    }

    public function test_apply_role_restriction_to_builder_for_appraiser() {
        // Delete all other users created outside of this test so we have predictable results
        user::repository()->delete();

        $appraiser1 = $this->getDataGenerator()->create_user();
        $appraised1 = $this->getDataGenerator()->create_user();
        $appraiser2 = $this->getDataGenerator()->create_user();
        $appraised2 = $this->getDataGenerator()->create_user();

        // $appraiser1 is appraiser of $appraised1
        job_assignment::create([
            'userid' => $appraised1->id,
            'idnumber' => '1',
            'appraiserid' => $appraiser1->id,
        ]);
        // $appraiser2 is appraiser of $appraised2
        job_assignment::create([
            'userid' => $appraised2->id,
            'idnumber' => '1',
            'appraiserid' => $appraiser2->id,
        ]);

        // Only $appraised1 is being appraised by $appraiser1
        $user_repository = user::repository();
        $this->setUser($appraiser1);
        appraiser::apply_role_restriction_to_builder($user_repository);
        $results = $user_repository->get()->all();
        $this->assertCount(1, $results);
        $this->assertEquals($appraised1->id, $results[0]->id);

        // Only $appraised2 is being appraised by $appraiser2
        $user_repository = user::repository();
        $this->setUser($appraiser2);
        appraiser::apply_role_restriction_to_builder($user_repository);
        $results = $user_repository->get()->all();
        $this->assertCount(1, $results);
        $this->assertEquals($appraised2->id, $results[0]->id);
    }

}
