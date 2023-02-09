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
use mod_perform\constants;
use performelement_linked_review\entity\linked_review_repository;
use performelement_linked_review\testing\linked_review_test_data_trait;

/**
 * @group perform
 */
class performelement_linked_review_repository_testcase extends testcase {

    use linked_review_test_data_trait;

    public function selection_participant_data_provider(): array {
        return [
            [
                'subject_user' => 'subject',
                'activities' => [
                    [
                        'name' => 'Single activity with company_goal linked_review and subject selection_relationship',
                        'relationships' => [
                            constants::RELATIONSHIP_SUBJECT,
                            constants::RELATIONSHIP_MANAGER,
                            constants::RELATIONSHIP_APPRAISER,
                        ],
                        'elements' => [
                            [
                                'plugin_type' => 'linked_review',
                                'content_type' => 'company_goal',
                                'selection_relationship' => constants::RELATIONSHIP_SUBJECT,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'company_goal' => [
                        'subject' => true,
                        'manager' => false,
                        'appraiser' => false,
                        'other' => false,
                    ],
                    'personal_goal' => [
                        'subject' => false,
                        'manager' => false,
                        'appraiser' => false,
                        'other' => false,
                    ],
                ],
            ],

            [
                'subject_user' => 'subject',
                'activities' => [
                    [
                        'name' => 'Single activity without linked_review',
                        'relationships' => [
                            constants::RELATIONSHIP_SUBJECT,
                            constants::RELATIONSHIP_MANAGER,
                            constants::RELATIONSHIP_APPRAISER,
                        ],
                        'elements' => [
                            [
                                'plugin_type' => 'long_text',
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'company_goal' => [
                        'subject' => false,
                        'manager' => false,
                        'appraiser' => false,
                        'other' => false,
                    ],
                    'personal_goal' => [
                        'subject' => false,
                        'manager' => false,
                        'appraiser' => false,
                        'other' => false,
                    ],
                ],
            ],

            [
                'subject_user' => 'subject',
                'activities' => [
                    [
                        'name' => 'Single activity with linked_review and only manager responding',
                        'relationships' => [
                            constants::RELATIONSHIP_MANAGER,
                        ],
                        'elements' => [
                            [
                                'plugin_type' => 'linked_review',
                                'content_type' => 'company_goal',
                                'selection_relationship' => constants::RELATIONSHIP_MANAGER,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'company_goal' => [
                        'subject' => false,
                        'manager' => true,
                        'appraiser' => false,
                        'other' => false,
                    ],
                ],
            ],

            [
                'subject_user' => 'subject',
                'activities' => [
                    [
                        'name' => 'First of many activities with linked_review - subject selection_relationship',
                        'relationships' => [
                            constants::RELATIONSHIP_SUBJECT,
                            constants::RELATIONSHIP_MANAGER,
                            constants::RELATIONSHIP_APPRAISER,
                        ],
                        'elements' => [
                            [
                                'plugin_type' => 'linked_review',
                                'content_type' => 'company_goal',
                                'selection_relationship' => constants::RELATIONSHIP_SUBJECT,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Second of many activities with linked_review - appraiser selection_relationship',
                        'relationships' => [
                            constants::RELATIONSHIP_SUBJECT,
                            constants::RELATIONSHIP_MANAGER,
                            constants::RELATIONSHIP_APPRAISER,
                        ],
                        'elements' => [
                            [
                                'plugin_type' => 'linked_review',
                                'content_type' => 'company_goal',
                                'selection_relationship' => constants::RELATIONSHIP_APPRAISER,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'company_goal' => [
                        'subject' => true,
                        'manager' => false,
                        'appraiser' => true,
                        'other' => false,
                    ],
                    'personal_goal' => [
                        'subject' => false,
                        'manager' => false,
                        'appraiser' => false,
                        'other' => false,
                    ],
                ],
            ],

            [
                'subject_user' => 'subject',
                'activities' => [
                    [
                        'name' => 'Single activity with company and personal linked_review',
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
                            [
                                'plugin_type' => 'linked_review',
                                'content_type' => 'personal_goal',
                                'selection_relationship' => constants::RELATIONSHIP_APPRAISER,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'company_goal' => [
                        'subject' => false,
                        'manager' => true,
                        'appraiser' => false,
                        'other' => false,
                    ],
                    'personal_goal' => [
                        'subject' => false,
                        'manager' => false,
                        'appraiser' => true,
                        'other' => false,
                    ],
                ],
            ],

            // Although theoretically incorrect as we should not allow the selection_relationship to be
            // something other than a responding relationship, still good to verify this specific method
            [
                'subject_user' => 'other',
                'activities' => [
                    [
                        'name' => 'Single activity with other subject',
                        'relationships' => [
                            constants::RELATIONSHIP_APPRAISER,
                        ],
                        'elements' => [
                            [
                                'plugin_type' => 'linked_review',
                                'content_type' => 'personal_goal',
                                'selection_relationship' => constants::RELATIONSHIP_MANAGER,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'company_goal' => [
                        'subject' => false,
                        'manager' => false,
                        'appraiser' => false,
                        'other' => false,
                    ],
                    'personal_goal' => [
                        'subject' => false,
                        'manager' => false,
                        'appraiser' => false,
                        'other' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $subject_user
     * @param array $activities
     * @param array $expected
     * @dataProvider selection_participant_data_provider
     */
    public function test_user_is_selecting_participant(string $subject_user, array $activities, array $expected): void {
        self::setAdminUser();

        $users = [
            'other' => self::getDataGenerator()->create_user(['username' => 'otheruser', 'firstname' => 'Another', 'lastname' => 'User']),
            'subject' => self::getDataGenerator()->create_user(['username' => 'subjectuser', 'firstname' => 'Subject', 'lastname' => 'User']),
            'manager' => self::getDataGenerator()->create_user(['username' => 'manageruser', 'firstname' => 'Manager', 'lastname' => 'User']),
            'appraiser' => self::getDataGenerator()->create_user(['username' => 'appraiseruser', 'firstname' => 'Appraiser', 'lastname' => 'User']),
        ];

        foreach ($activities as $activity) {
            $this->create_activity_with_elements($users, $subject_user, $activity);
        }

        foreach ($expected as $content_type => $expected_results) {
            foreach ($expected_results as $viewing_user => $expected_outcome) {
                self::assertSame($expected_outcome, linked_review_repository::user_is_selecting_participant(
                    $content_type, $users[$viewing_user]->id, $users[$subject_user]->id)
                );
            }
        }
    }

}
