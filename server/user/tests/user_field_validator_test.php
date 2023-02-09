<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core_use
 */

use core_phpunit\testcase;
use core_user\external\user_field_validator;

/**
 * Unit tests for the External API user_field_validator class.
 */
class core_user_user_field_validator_testcase extends testcase {
    /**
     * @var string[]
     */
    protected $auth_plugins;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->auth_plugins = [
            'cas', 'db', 'ldap', 'shibboleth',
            'approved', 'email',
            'manual', 'oauth2'
        ];
        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void {
        $this->auth_plugins = null;
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_validate_locked_fields_for_locked_field(): void {
        self::setAdminUser();
        $gen = self::getDataGenerator();

        foreach ($this->auth_plugins as $plugin) {
            $target_user = $gen->create_user(['firstname' => 'Adele', 'lastname' => 'Wert', 'auth' => $plugin]);
            set_config('field_lock_firstname','locked', 'auth_' . $plugin);
            try {
                user_field_validator::validate_locked_fields(
                    [
                        'firstname' => 'firstname',
                        'lastname' => 'lastname'
                    ],
                    user_field_validator::UPDATE,
                    $target_user->id
                );
            } catch (moodle_exception $exception) {
                self::assertEquals('The firstname is Locked and can not be updated', $exception->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function test_validate_locked_fields_for_unlockedifempty_field(): void {
        self::setAdminUser();
        $gen = self::getDataGenerator();

        foreach ($this->auth_plugins as $plugin) {
            // Lastname is empty, so the exception must not be fired
            $target_user = $gen->create_user(['lastname' => '', 'auth' => $plugin]);
            set_config('field_lock_lastname','unlockedifempty', 'auth_' . $plugin);

            user_field_validator::validate_locked_fields(
                [
                    'firstname' => 'firstname',
                    'lastname' => 'lastname'
                ],
                user_field_validator::UPDATE,
                $target_user->id
            );
        }

        foreach ($this->auth_plugins as $plugin) {
            // Firstname is not empty, so the exception must be fired
            $target_user = $gen->create_user(['firstname' => 'Adele', 'auth' => $plugin]);
            set_config('field_lock_firstname','unlockedifempty', 'auth_' . $plugin);
            try {
                user_field_validator::validate_locked_fields(
                    [
                        'firstname' => 'firstname',
                    ],
                    user_field_validator::UPDATE,
                    $target_user->id
                );
            } catch (moodle_exception $exception) {
                self::assertEquals('The firstname is Locked and can not be updated', $exception->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function test_validate_locked_fields_for_unlockedifempty_custom_fields(): void {
        self::setAdminUser();
        $gen = self::getDataGenerator();
        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0, 'shortname' => 'checkbox']);
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);


        foreach (['cas', 'db', 'ldap', 'shibboleth'] as $plugin) {
            // Custom field is empty and we can update it.
            $target_user = $gen->create_user(['firstname' => 'Adele', 'auth' => $plugin]);
            set_config('field_lock_profile_field_checkbox','unlockedifempty', 'auth_' . $plugin);
                user_field_validator::validate_locked_fields(
                    [
                        'firstname' => 'firstname',
                        'custom_fields' => [
                            [
                                'shortname' => $textarea->shortname,
                                'data' => 'textarea',
                                'data_format' => 1
                            ],
                            [
                                'shortname' => 'checkbox',
                                'data' => '1'
                            ]
                        ]
                    ],
                    user_field_validator::UPDATE,
                    $target_user->id
                );
        }

        foreach (['cas', 'db', 'ldap', 'shibboleth'] as $plugin) {
            // Custom field is not empty, we can not update it.
            $target_user = $gen->create_user(['firstname' => 'Adele', 'auth' => $plugin]);
            $this->set_profile_field_value($target_user, $checkbox, '1');
            set_config('field_lock_profile_field_checkbox','unlockedifempty', 'auth_' . $plugin);
            try {
                user_field_validator::validate_locked_fields(
                    [
                        'firstname' => 'firstname',
                        'custom_fields' => [
                            [
                                'shortname' => $textarea->shortname,
                                'data' => 'textarea',
                                'data_format' => 1
                            ],
                            [
                                'shortname' => 'checkbox',
                                'data' => '1'
                            ]
                        ]
                    ],
                    user_field_validator::UPDATE,
                    $target_user->id
                );
            } catch (moodle_exception $exception) {
                self::assertEquals('Custom field for checkbox is Locked and can not be updated', $exception->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function test_validate_locked_fields_for_locked_custom_fields(): void {
        self::setAdminUser();
        $gen = self::getDataGenerator();
        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');
        $generator->create_custom_profile_field(['datatype' => 'text', 'shortname' => 'text']);

        foreach (['cas', 'db', 'ldap', 'shibboleth'] as $plugin) {
            $target_user = $gen->create_user(['firstname' => 'Adele', 'auth' => $plugin]);
            set_config('field_lock_profile_field_text','locked', 'auth_' . $plugin);
            try {
                user_field_validator::validate_locked_fields(
                    [
                        'firstname' => 'firstname',
                        'custom_fields' => [
                            [
                                'shortname' => 'text',
                                'data' => 'text'
                            ]
                        ]
                    ],
                    user_field_validator::UPDATE,
                    $target_user->id
                );
            } catch (moodle_exception $exception) {
                self::assertEquals('Custom field for text is Locked and can not be updated', $exception->getMessage());
            }
        }
    }

    /**
     * @param stdClass $user
     * @param stdClass $field
     * @param string $data
     * @param int $dataformat
     * @return int
     */
    private function set_profile_field_value(stdClass $user, stdClass $field, string $data, int $dataformat = 0): int {
        global $DB;

        $record = new stdClass();
        $record->fieldid = $field->id;
        $record->userid = $user->id;
        $record->data = $data;
        $record->dataformat = $dataformat;

        return $DB->insert_record('user_info_data', $record);
    }
}