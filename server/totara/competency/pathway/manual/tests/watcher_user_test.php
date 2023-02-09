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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 * @subpackage test
 */

use core_user\access_controller;
use core_user\hook\allow_view_profile_field;
use pathway_manual\models\roles\manager;
use totara_competency\watcher\core_user;
use totara_core\hook\manager as hook_manager;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class pathway_manual_watcher_user_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * @var \totara_competency\testing\generator
     */
    protected $generator;

    /**
     * @var stdClass
     */
    protected $staff_user;

    /**
     * @var stdClass
     */
    protected $manager_user;

    protected function setUp(): void {
        parent::setUp();

        // Reset the hook watchers so that the tests can be more accurage.
        hook_manager::phpunit_replace_watchers([
            [
                'hookname' => allow_view_profile_field::class,
                'callback' => [core_user::class, 'allow_view_profile_field']
            ]
        ]);
        access_controller::clear_instance_cache();

        $this->generator = self::getDataGenerator()->get_plugin_generator('totara_competency');
        $this->staff_user = self::getDataGenerator()->create_user();
        $this->manager_user = self::getDataGenerator()->create_user();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->generator = null;
        $this->staff_user = null;
        $this->manager_user = null;
    }

    public function data_provider(): array {
        return [
            "View a manager's fullname as a staff member" => [
                'fullname', 'staff_user', 'manager_user',
            ],
            "View a manager's profileimageurl as a staff member" => [
                'profileimageurl', 'staff_user', 'manager_user',
            ],
            "View a staff members fullname as a manager" => [
                'fullname', 'manager_user', 'staff_user',
            ],
            "View a staff members profileimageurl as a manager" => [
                'profileimageurl', 'manager_user', 'staff_user',
            ],
        ];
    }

    /**
     * @dataProvider data_provider
     * @param string $field
     * @param string $as_user
     * @param string $for_user
     */
    public function test_allow_viewing_of_fields_when_rating_exists($field, $as_user, $for_user): void {
        self::setUser($this->{$as_user});

        $competency = $this->generator->create_competency();
        $manager_rating = $this->generator->create_manual_rating(
            $competency, $this->staff_user, $this->manager_user, manager::class
        );

        $this->assertNotNull($this->resolve_graphql_type('core_user', $field, $this->{$for_user}));
    }

    public function test_prevent_viewing_of_fullname_when_no_rating_exists(): void {
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        self::setUser($user1);

        $competency = $this->generator->create_competency();
        $invalid_rating1 = $this->generator->create_manual_rating($competency, $user2, $user3, manager::class);
        $invalid_rating2 = $this->generator->create_manual_rating($competency, $user3, $user2, manager::class);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/You did not check you can view a user before resolving them./');
        $this->resolve_graphql_type('core_user', 'fullname', $user2);
    }

    public function test_prevent_viewing_of_profileimageurl_when_no_rating_exists(): void {
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        self::setUser($user1);

        $competency = $this->generator->create_competency();
        $invalid_rating1 = $this->generator->create_manual_rating($competency, $user2, $user3, manager::class);
        $invalid_rating2 = $this->generator->create_manual_rating($competency, $user3, $user2, manager::class);

        $this->assertNull($this->resolve_graphql_type('core_user', 'profileimageurl', $user2));
    }

    public function allowed_fields_data_provider(): array {
        return [
            ['id', true],
            ['fullname', true],
            ['profileimageurl', true],
            ['address', false],
            ['email', false],
            ['phone1', false],
        ];
    }

    /**
     * @dataProvider allowed_fields_data_provider
     * @param string $field
     * @param bool $allowed
     */
    public function test_correct_fields_are_allowed(string $field, bool $allowed): void {
        self::setUser($this->staff_user);

        $competency = $this->generator->create_competency();
        $manager_rating = $this->generator->create_manual_rating(
            $competency, $this->staff_user, $this->manager_user, manager::class
        );

        if ($allowed) {
            $this->assertNotNull($this->resolve_graphql_type('core_user', $field, $this->manager_user));
        } else {
            $this->assertNull($this->resolve_graphql_type('core_user', $field, $this->manager_user));
        }
    }

}
