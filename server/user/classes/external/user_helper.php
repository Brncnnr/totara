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
 * @package core_user
 */

namespace core_user\external;

use context_system;
use core\entity\user;
use core\exception\unresolved_record_reference;
use core_user\userdata\password;
use totara_tenant\exception\unresolved_tenant_reference;
use core\format;
use core\orm\query\builder;
use core\reference\user_record_reference;
use core\reference\tenant_record_reference;
use core_date;
use core_text;
use core_user\exception\delete_user_exception;
use core_user\exception\create_user_exception;
use core_user\exception\update_user_exception;
use moodle_exception;
use stdClass;
use totara_tenant\local\util;
use core_user\profile\field\field_helper;

/**
 * User helper to provide functions(CRUD) that can be used out of user plugin
 */
class user_helper {
    /**
     * @var string
     */
    public const CREATE = 'create';

    /**
     * @var string
     */
    public const UPDATE = 'update';

    /**
     * @param array $params User input.
     * @return user
     */
    public static function create_user(array $params): user {
        global $CFG;
        try {
            // Validate user inputs before user creation.
            user_field_validator::validate_input($params);
            $params = self::restructure_custom_field_inputs($params);
        } catch (moodle_exception $e) {
            throw new create_user_exception($e->getMessage());
        }

        $user = new stdClass();
        $user->confirmed = 1;
        $user->tenant_id = null;
        $user->suspended = 0;
        $user->timecreated = time();
        $user->timeupdated = time();

        foreach (user_field_validator::get_fields() as $field) {
            if (!isset($params[$field])) {
                continue;
            }

            $user->$field = $params[$field];
        }
        if (!isset($user->city)) {
            if (!empty($CFG->defaultcity)) {
                $user->city = $CFG->defaultcity;
            }
        }

        if (isset($user->descriptionformat)) {
            $user->descriptionformat = format::get_moodle_format($user->descriptionformat);
        } else {
            $user->descriptionformat = FORMAT_HTML;
        }

        if (isset($user->description)) {
            $user->description = format_text($user->description, $user->descriptionformat);
        }

        if (isset($user->country)) {
            $user->country = core_text::strtoupper($user->country);
        } else if (!empty($CFG->country)) {
            $user->country = $CFG->country;
        }

        if (isset($CFG->forcetimezone) && $CFG->forcetimezone != 99) {
            $user->timezone = $CFG->forcetimezone;
            debugging('Your input timezone has been overrided as forcetimezone is enabled', DEBUG_NORMAL);
        } else if (isset($user->timezone)) {
            $user->timezone = core_date::normalise_timezone($user->timezone);
        } else {
            $user->timezone = $CFG->timezone;
        }

        if (isset($user->lang)) {
            $user->lang = core_text::strtolower($user->lang);
        } else if (!empty($CFG->lang)) {
            $user->lang = $CFG->lang;
        }

        if (isset($user->theme)) {
            if (empty($CFG->allowuserthemes)) {
                $user->theme = '';
                debugging("User not allow to set theme", DEBUG_NORMAL);
            } else {
                $user->theme = core_text::strtolower($user->theme);
            }
        }

        // Default auth setting is manual.
        $user->auth = isset($user->auth) ? core_text::strtolower($user->auth) : 'manual';

        if (isset($user->calendartype)) {
            $user->calendartype = core_text::strtolower($user->calendartype);
        } else if (!empty($CFG->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        } else {
            $user->calendartype = 'gregorian';
        }

        if (isset($user->suspended)) {
            $user->suspended = (int) $user->suspended;
        } else {
            $user->suspended = 0;
        }

        $user->emailstop = (int) ($user->emailstop ?? false);

        require_once($CFG->dirroot . "/user/lib.php");

        $user_id = builder::get_db()->transaction(function () use ($CFG, $params, $user) {
            $user->id = user_create_user($user, true, true);
            self::save_custom_fields_data($user);

            // Buy this point we check all capabilities and variation to create user into tenancy
            if (array_key_exists('tenantid', $params)) {
                util::migrate_user_to_tenant($user->id, $params['tenantid']);
            }

            return $user->id;
        });

        $new_user = new user($user_id);
        $userauth = get_auth_plugin($new_user->auth);
        if (isset($params['force_password_change']) && $params['force_password_change']) {
            if ($userauth->can_change_password()) {
                set_user_preference('auth_forcepasswordchange', 1, $user);
            }
        }
        if (isset($params['generate_password']) && $params['generate_password']) {
            if ($userauth->can_change_password()) {
                setnew_password_and_mail($user);
            }
        }
        $new_user->refresh();
        return $new_user;
    }

    /**
     * @param stdClass $target_user
     * @param array $input
     * @return user
     */
    public static function update_user(stdClass $target_user, array $input): user {
        global $CFG;
        require_once($CFG->dirroot."/user/lib.php");

        try {
            user_field_validator::validate_input($input, user_field_validator::UPDATE, $target_user->id);
            $input = self::restructure_custom_field_inputs($input);
        } catch (moodle_exception $e) {
            throw new update_user_exception($e->getMessage());
        }

        foreach (user_field_validator::get_fields() as $field) {
            if (!isset($input[$field])) {
                continue;
            }

            $target_user->$field = $input[$field];
        }
        $old_password = $target_user->password;

        if (isset($input['descriptionformat'])) {
            $target_user->descriptionformat = format::get_moodle_format($target_user->descriptionformat);
        }

        if (isset($input['description'])) {
            // Clean the text.
            $target_user->description = format_text($target_user->description, $target_user->descriptionformat);
        }

        if (isset($input['timezone'])) {
            if (isset($CFG->forcetimezone) && $CFG->forcetimezone != 99) {
                $target_user->timezone = $CFG->forcetimezone;
                debugging('Your input timezone has been overrided as forcetimezone is enabled', DEBUG_NORMAL);
            } else {
                $target_user->timezone = core_date::normalise_timezone($target_user->timezone);
            }
        }

        if (isset($input['theme'])) {
            if (empty($CFG->allowuserthemes)) {
                $target_user->theme = '';
                debugging("User not allow to set theme", DEBUG_NORMAL);
            }
        }

        $user_id = builder::get_db()->transaction(function () use ($target_user, $input, $CFG) {
            if (isset($input['suspended'])) {
                $target_user->suspended = (int)$input['suspended'];
                // Suspend and unsuspend target user.
                $input['suspended'] === true ? user_suspend_user($target_user->id) : user_unsuspend_user($target_user->id);
            }

            user_update_user($target_user, isset($input['password']), true);

            self::update_or_delete_custom_fields($target_user);

            if (!empty($CFG->tenantsenabled)) {
                $target_user_tenant_id = null;
                if (array_key_exists('tenant', $input)) {
                    try {
                        $tenant_record_reference = new tenant_record_reference();
                        $target_tenant = $tenant_record_reference->get_record($input['tenant']);
                        unset($input['tenant']);
                        $target_user_tenant_id = $target_tenant->id;
                    } catch (unresolved_record_reference $exception) {
                        throw new unresolved_tenant_reference('Tenant reference must identify exactly one tenant.');
                    }
                }
                if (!is_null($target_user_tenant_id )) {
                    // Move target_user from the system to the tenant or to another tenant.
                    util::migrate_user_to_tenant($target_user->id, $target_user_tenant_id);
                }
            }

            return $target_user->id;
        });

        $new_user = new user($user_id);
        $userauth = get_auth_plugin($new_user->auth);
        $new_user_password = $new_user->password;
        if (isset($input['force_password_change'])) {
            if ($input['force_password_change']) {
                if ($userauth->can_change_password()) {
                    set_user_preference('auth_forcepasswordchange', 1, $target_user);
                }
            } else if ($old_password != $new_user_password) {
                unset_user_preference('auth_forcepasswordchange', $target_user);
            }
        }
        if (isset($input['generate_password']) && $input['generate_password']) {
            if ($userauth->can_change_password()) {
                setnew_password_and_mail($target_user);
            }
        }
        $new_user->refresh();
        return $new_user;
    }

    /**
     * @param stdClass $target_user
     * @return int id of deleted user
     */
    public static function delete_user(stdClass $target_user): int {
        global $CFG;
        try {
            // Cannot delete guest user.
            // Cannot delete admin user. Admin can be theoretically from different auth plugin, but we want to prevent deletion
            // of internal accounts only.
            $user_record_reference = new user_record_reference();
            $target_user_record = $user_record_reference->not_a_guest()->not_an_admin()->get_record(['id' => $target_user->id]);
        } catch (unresolved_record_reference $exception) {
            throw new delete_user_exception('For deleting a user: ' . $exception->getMessage());
        }

        require_once($CFG->dirroot . "/user/lib.php");
        delete_user($target_user);

        return $target_user->id;
    }

    /**
     * @param int $id
     * @param int|null $suspended
     * @return void
     */
    public static function validate_tenant_by_id(int $id, ?int $suspended = 0): void {
        $params = ['id' => $id,
            'suspended' => $suspended
        ];

        try {
            $tenant_record_reference = new tenant_record_reference();
            $tenant_record_reference->get_record($params);
        } catch (unresolved_record_reference $exception) {
            throw new unresolved_tenant_reference('Tenant reference must identify exactly one tenant.');
        }
    }

    /**
     * Method for restructuring the custom fields from the GraphQL input format (an array underneath a custom_fields
     * key) to the format expected by the profile code.
     *
     * @param array $params
     * @return array
     */
    public static function restructure_custom_field_inputs(array $params): array {
        if (!isset($params['custom_fields'])) {
            return $params;
        }

        $shortname = null;
        foreach ($params['custom_fields'] as $custom_field) {
            // Check the current shortname has the same name as previous one, if so, we unset previous one and keep the
            // latest shortname.
            if (!is_null($shortname) && $shortname === $custom_field['shortname']) {
                unset($params[field_helper::format_custom_field_short_name($shortname)]);
            }

            $key = field_helper::format_custom_field_short_name(($custom_field['shortname']));
            // Already Validated on user_field_validator::validate_custom_field_from_inputs(), safely restructure the input.
            if (isset($custom_field['delete']) && $custom_field['delete']) {
                $params[$key] = ['delete' => $custom_field['delete']];
            } else {
                if (isset($custom_field['data_format'])) {
                    $params[$key] = [
                        'text' => $custom_field['data'],
                        'format' => $custom_field['data_format'],
                    ];
                } else {
                    $params[$key] = $custom_field['data'];
                }
            }

            $shortname = $custom_field['shortname'];
        }

        unset($params['custom_fields']);
        return $params;
    }

    /**
     * @param stdClass $user
     * @return stdClass
     */
    public static function restructure_custom_fields_for_save(stdClass $user): stdClass {
        global $CFG;

        foreach (builder::table('user_info_field')->fetch() as $custom_field) {
            $key = field_helper::format_custom_field_short_name($custom_field->shortname);
            if (!isset($user->{$key})) {
                continue;
            }
            $value = $user->{$key};

            // Transform value from format used by external systems into internal value required by profile_save_data().
            require_once($CFG->dirroot.'/user/profile/field/'.$custom_field->datatype.'/field.class.php');
            $profile_field_class = field_helper::format_custom_field_short_name($custom_field->datatype);
            $form_field = new $profile_field_class($custom_field->id, $user->id);
            if (method_exists($form_field, 'convert_external_data')) {
                $user->{$key} = $form_field->convert_external_data($value);
            }
        }

        return $user;
    }

    /**
     * @param stdClass $user
     * @return void
     */
    public static function save_custom_fields_data(stdClass $user): void {
        global $CFG;

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        // After one user is created, before saving profile custom fields data, we need validate input again
        // with internal validation.
        $errors = profile_validation($user, []);

        // TODO TL-35065 Ideally we would support returning multiple errors in a request via the GraphQL errors array
        //      so each error (along with other validation errors) could be returned individually in the error response.
        if (!empty($errors)) {
            $message = "Custom field errors: \n";
            foreach ($errors as $field => $error) {
                $shortname = substr($field, 14);
                $message .= "{$shortname}: $error\n";
            }
            throw new moodle_exception($message);
        }

        $user = self::restructure_custom_fields_for_save($user);
        profile_save_data($user);
    }

    /**
     * @param stdClass $user
     * @return void
     */
    public static function update_or_delete_custom_fields(stdClass $user): void {
        global $CFG;

        $custom_fields = builder::table('user_info_data', 'uid')
            ->join(['user_info_field', 'uif'], 'uid.fieldid', 'uif.id')
            ->where('uid.userid', $user->id)
            ->select_raw('uif.id, uif.shortname, uif.datatype')
            ->fetch();

        foreach ($custom_fields as $custom_field) {
            $key = field_helper::format_custom_field_short_name($custom_field->shortname);
            if (!isset($user->{$key})) {
                continue;
            }
            $value = $user->{$key};
            if (isset($value['delete']) && $value['delete']) {
                $user->{$key} = '';
                require_once($CFG->dirroot.'/user/profile/field/'.$custom_field->datatype.'/field.class.php');
                $field = field_helper::format_custom_field_short_name($custom_field->datatype);
                $field_obj = new $field($custom_field->id, $user->id);
                // Skip profile_validation on deleting the custom value, otherwise date and datetime can not be removed
                // from the 'user_info_data' table.
                $field_obj->edit_save_data($user);
                unset($user->{$key});
            }
        }

        // Keep updating custom fields.
        self::save_custom_fields_data($user);
    }
}