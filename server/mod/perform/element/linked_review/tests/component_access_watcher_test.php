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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package performelement_linked_review
 */

use core_phpunit\testcase;
use core_user\access_controller;
use mod_perform\constants;
use performelement_linked_review\testing\linked_review_test_data_trait;
use performelement_linked_review\watcher\component_access;
use totara_core\hook\component_access_check;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_core\hook\manager as hook_manager;


/**
 * @group perform
 */
class performelement_linked_review_component_access_watcher_testcase extends testcase {

    use webapi_phpunit_helper;
    use linked_review_test_data_trait;

    protected $users;

    protected function setUp(): void {
        parent::setUp();

        // Reset the hook watchers so that the tests can be more accurage.
        hook_manager::phpunit_replace_watchers([
            [
                'hookname' => component_access_check::class,
                'callback' => [component_access::class, 'check_selecting_participants']
            ]
        ]);
        access_controller::clear_instance_cache();

        $this->users = [
            'admin' => get_admin(),
            'other' => self::getDataGenerator()->create_user(['username' => 'otheruser', 'firstname' => 'Another', 'lastname' => 'User']),
            'subject' => self::getDataGenerator()->create_user(['username' => 'subjectuser', 'firstname' => 'Subject', 'lastname' => 'User']),
            'manager' => self::getDataGenerator()->create_user(['username' => 'manageruser', 'firstname' => 'Manager', 'lastname' => 'User']),
            'appraiser' => self::getDataGenerator()->create_user(['username' => 'appraiseruser', 'firstname' => 'Appraiser', 'lastname' => 'User']),
        ];
    }

    protected function tearDown(): void {
        $this->users = null;
        parent::tearDown();
    }

    public function test_component_access_check_goals(): void {
        self::setAdminUser();

        $activity_params = [
            'name' => 'Single activity with company_goal linked_review and manager selection_relationship',
            'relationships' => [
                constants::RELATIONSHIP_SUBJECT,
                constants::RELATIONSHIP_MANAGER,
                constants::RELATIONSHIP_APPRAISER,
            ],
            'elements' => [
                [
                    'plugin_type' => 'linked_review',
                    'content_type' => 'company_goal',
                    'selection_relationship' => constants::RELATIONSHIP_MANAGER,
                ],
            ]
        ];

        $this->create_activity_with_elements($this->users, 'subject', $activity_params);

        $to_test = [
            'admin' => true,
            'other' => false,
            'subject' => false,
            'manager' => true,
            'appraiser' => false,
        ];

        foreach ($to_test as $key => $expected) {
            $hook1 = new component_access_check(
                'hierarchy_goal',
                $this->users[$key]->id,
                $this->users['subject']->id,
                ['content_type' => 'company_goal']
            );

            $hook1->execute();
            self::assertSame($expected, $hook1->has_permission());

            // Only admin has permission on personal_goal
            $hook2 = new component_access_check(
                'hierarchy_goal',
                $this->users[$key]->id,
                $this->users['subject']->id,
                ['content_type' => 'personal_goal']
            );

            $hook2->execute();
            self::assertSame($key == 'admin' ? true : false, $hook2->has_permission());
        }
    }

    public function component_access_check_types_data_provider(): array {
        return [
            [
                'content_type' => 'totara_competency',
                'to_test' => [
                    'admin' => true,
                    'other' => false,
                    'subject' => false,
                    'manager' => true,
                    'appraiser' => false,
                ],
            ],
            [
                'content_type' => 'totara_evidence',
                'to_test' => [
                    'admin' => true,
                    'other' => false,
                    'subject' => false,
                    'manager' => true,
                    'appraiser' => false,
                ],
            ],
        ];
    }

    /**
     * @param string $content_type
     * @param array $to_test
     * @dataProvider component_access_check_types_data_provider
     */
    public function test_component_access_check_types(string $content_type, array $to_test): void {
        self::setAdminUser();

        $activity_params = [
            'name' => 'Single activity with linked_review and manager selection_relationship',
            'relationships' => [
                constants::RELATIONSHIP_SUBJECT,
                constants::RELATIONSHIP_MANAGER,
                constants::RELATIONSHIP_APPRAISER,
            ],
            'elements' => [
                [
                    'plugin_type' => 'linked_review',
                    'content_type' => $content_type,
                    'selection_relationship' => constants::RELATIONSHIP_MANAGER,
                ],
            ]
        ];

        $this->create_activity_with_elements($this->users, 'subject', $activity_params);

        foreach ($to_test as $key => $expected) {
            $hook1 = new component_access_check(
                $content_type,
                $this->users[$key]->id,
                $this->users['subject']->id,
                ['content_type' => 'totara_competency']
            );

            $hook1->execute();
            self::assertSame($expected, $hook1->has_permission());
        }
    }

}
