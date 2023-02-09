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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/source_csv_testcase.php');

/**
 * Test to ensure sanity check of the email field works correctly for csv source.
 *
 * We will be testing the following setups.
 *
 * |----|--------|----------------------------|---------------------|------------------|---------------------|
 * | #  | Create | Update | empty string skip | empty string delete | Allow duplicates | Default email empty |
 * |----|--------|----------------------------|---------------------|------------------|---------------------|
 * | 1  |   1    |   0    |        1          |          0          |        1         |          0          |
 * | 2  |   1    |   0    |        0          |          1          |        1         |          0          |
 * | 3  |   1    |   0    |        1          |          0          |        1         |          1          |
 * | 4  |   1    |   0    |        0          |          1          |        1         |          1          |
 * | 5  |   1    |   0    |        1          |          0          |        0         |          0          |
 * | 6  |   1    |   0    |        0          |          1          |        0         |          0          |
 * | 7  |   1    |   0    |        1          |          0          |        0         |          1          |
 * | 8  |   1    |   0    |        0          |          1          |        0         |          1          |
 * | 9  |   0    |   1    |        1          |          0          |        1         |          0          |
 * | 10 |   0    |   1    |        0          |          1          |        1         |          0          |
 * | 11 |   0    |   1    |        1          |          0          |        1         |          1          |
 * | 12 |   0    |   1    |        0          |          1          |        1         |          1          |
 * | 13 |   0    |   1    |        1          |          0          |        0         |          0          |
 * | 14 |   0    |   1    |        0          |          1          |        0         |          0          |
 * | 15 |   0    |   1    |        1          |          0          |        0         |          1          |
 * | 16 |   0    |   1    |        0          |          1          |        0         |          1          |
 * |----|--------|----------------------------|---------------------|------------------|---------------------|
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_user_csv_check_email_sanity_testcase extends totara_sync_csv_testcase {

    protected $filedir      = null;
    protected $configcsv    = [];
    protected $config       = [];

    protected $elementname  = 'user';
    protected $sourcename   = 'totara_sync_source_user_csv';
    protected $source       = null;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;

        require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
        require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_user_csv.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
    }

    protected function tearDown(): void {
        $this->filedir      = null;
        $this->configcsv    = null;
        $this->config       = null;
        $this->importdata   = null;
        $this->source       = null;
        parent::tearDown();
    }

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();

        $this->source = new $this->sourcename();
        $this->filedir = $this->create_filedir();

        set_config('element_user_enabled', 1, 'totara_sync');
        set_config('source_user', 'totara_sync_source_user_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        $this->configcsv = [
            'csvuserencoding'           => 'UTF-8',
            'delimiter'                 => ',',
            'csvsaveemptyfields'        => true,
            'import_deleted'            => '1',
            'import_firstname'          => '1',
            'import_idnumber'           => '1',
            'import_lastname'           => '1',
            'import_timemodified'       => '1',
            'import_username'           => '1',
            'import_email'              => '1',
        ];

        $this->config = [
            'allow_create'              => '0',
            'allow_delete'              => '0',
            'allow_update'              => '0',
            'allowduplicatedemails'     => '0',
            'defaultsyncemail'          => '',
            'forcepwchange'             => '0',
            'undeletepwreset'           => '0',
            'ignoreexistingpass'        => '0',
            'sourceallrecords'          => '0',
        ];
    }

    /**
     * Data provider for user creation
     */
    public function data_provider_user_creation() {
        $data = [
            [
                1, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    3 => 'idnum003',
                    4 => 'idnum004',
                ]

            ],
            [
                2, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    3 => 'idnum003',
                    4 => 'idnum004',
                ]

            ],
            [
                3, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [], // Expected results.

            ],
            [
                4, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [], // Expected results.
            ],
            [
                5, // Test number.
                [ // Source config
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    1 => 'idnum001',
                    3 => 'idnum003',
                    4 => 'idnum004',
                ]
            ],
            [
                6, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    1 => 'idnum001',
                    3 => 'idnum003',
                    4 => 'idnum004',
                ]
            ],
            [
                7, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [ // Expected results.
                    1 => 'idnum001'
                ]
            ],
            [
                8, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [ // Expected results.
                    1 => 'idnum001'
                ]
            ],
        ];

        return $data;
    }

    /**
     * Data provider for user update
     */
    public function data_provider_user_update() {
        $data = [
            [
                9, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    3 => 'idnum003',
                ]

            ],
            [
                10, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    3 => 'idnum003',
                ]

            ],
            [
                11, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config.
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [], // Expected results.

            ],
            [
                12, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config.
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [], // Expected results.

            ],
            [
                13, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    1 => 'idnum001',
                    3 => 'idnum003',
                ]
            ],
            [
                14, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config.
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    1 => 'idnum001',
                    3 => 'idnum003',
                ]
            ],
            [
                15, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => false,
                ],
                [ // Element config.
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [ // Expected results.
                    1 => 'idnum001'
                ]


            ],
            [
                16, // Test number.
                [ // Source config.
                    'csvsaveemptyfields' => true,
                ],
                [ // Element config
                    'allow_update'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [ // Expected results.
                    1 => 'idnum001'
                ]
            ],
        ];

        return $data;
    }

    /**
     * @dataProvider data_provider_user_creation
     */
    public function test_email_field_user_creation($testnum, $sourceconfig, $elementconfig, $expectedresults) {
        // Create a user for duplicate email test.
        $this->getDataGenerator()->create_user(['email' => 'user1@email.com']);

        // Set the config.
        $this->set_source_config(array_merge($this->configcsv, $sourceconfig));
        $this->set_element_config(array_merge($this->config, $elementconfig));

        // Add the CSV source file.
        $this->add_csv('user_email_1.csv');

        $invalididnumbers = $this->check_sanity();
        ksort($invalididnumbers);

        $this->assertEquals($expectedresults, $invalididnumbers, 'Failed for test #' . $testnum);
    }

    /**
     * @dataProvider data_provider_user_update
     */
    public function test_email_field_user_update($testnum, $sourceconfig, $elementconfig, $expectedresults) {
        // Create a user for duplicate email test.
        $this->getDataGenerator()->create_user(['email' => 'user1@email.com']);

        // Create other users for the sanity check for updating users.
        $this->getDataGenerator()->create_user(['totarasync' => 1, 'idnumber' => 'idnum002', 'email' => 'user2@email.com']);
        $this->getDataGenerator()->create_user(['totarasync' => 1, 'idnumber' => 'idnum003', 'email' => 'user3@email.com']);
        $this->getDataGenerator()->create_user(['totarasync' => 1, 'idnumber' => 'idnum004', 'email' => 'user4@email.com']);

        // Set the config.
        $this->set_source_config(array_merge($this->configcsv, $sourceconfig));
        $this->set_element_config(array_merge($this->config, $elementconfig));

        // Add the CSV source file.
        $this->add_csv('user_email_1.csv');

        $invalididnumbers = $this->check_sanity();
        ksort($invalididnumbers);

        $this->assertEquals($expectedresults, $invalididnumbers, 'Failed for test #' . $testnum);
    }
}
