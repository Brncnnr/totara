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

use coding_exception;
use context_system;
use context_user;
use core\entity\user;
use core\format;
use core\orm\query\builder;
use core_calendar\type_factory;
use core_component;
use core_date;
use core_text;
use core_user\profile\field\field_helper;
use moodle_exception;

/**
 * Class user_field_validator is to validate user fields being passed by users before CRUD
 */
class user_field_validator {
    /**
     * @var string
     */
    public const CREATE = 'create';

    /**
     * @var string
     */
    public const UPDATE = 'update';

    /**
     * @param array $params
     * @param string $action
     * @param int|null $targetuser_id
     *
     * @return void
     */
    public static function validate_input(array $params, string $action = self::CREATE, ?int $targetuser_id = null): void {
        if ($action === self::CREATE) {
            self::validate_required_fields($params);
        }

        self::validate_exists($params, $targetuser_id);
        self::validate_url($params);
        self::validate_format($params);
        self::validate_is_boolean($params);
        self::validate_blank($params);
        self::validate_length($params);
        self::validate_password($params);
        self::validate_email($params, $targetuser_id);
        self::validate_value_from_defined_internal_list($params);
        self::validate_custom_field_from_inputs($params, $action, $targetuser_id);
        self::validate_locked_fields($params, $action, $targetuser_id);
    }

    /**
     * @param array $params User input.
     * @param int|null $targetuser_id
     * @return void
     */
    public static function validate_exists(array $params, ?int $targetuser_id = null): void {
        $expected = ['username', 'idnumber'];
        foreach ($expected as $field) {
            if (!isset($params[$field]) || empty(trim($params[$field]))) {
                continue;
            }

            $builder = builder::table('user')->where($field, '=', $params[$field]);
            if (!empty($targetuser_id)) {
                $builder->where('id', '<>', $targetuser_id);
            }
            if ($builder->exists()) {
                throw new moodle_exception(ucfirst($field) . ' already exists: ' . $params[$field]);
            }
        }
    }

    /**
     * @param array $params User input.
     * @return void
     */
    public static function validate_url(array $params): void {
        if (isset($params['url']) && !filter_var($params['url'], FILTER_VALIDATE_URL)) {
            throw new moodle_exception('Invalid url format: ' . $params['url']);
        }
    }

    /**
     * @param array $params User input.
     * @return void
     */
    public static function validate_format(array $params): void {
        if (isset($params['descriptionformat']) && !in_array($params['descriptionformat'], format::get_available())) {
            throw new coding_exception('Unsupported descriptionformat');
        }
    }

    /**
     * @param array $params User input.
     * @return void
     */
    public static function validate_is_boolean(array $params): void {
        $expected = ['emailstop'];

        foreach ($expected as $field) {
            if (!isset($params[$field])) {
                continue;
            }

            if (!is_bool($params[$field])) {
                throw new moodle_exception(ucfirst($field) . " is not boolean type ");
            }
        }
    }

    /**
     * @param array $params User input.
     * @return void
     */
    public static function validate_required_fields(array $params): void {
        $required_fields = self::get_required_fields();
        if (isset($params['generate_password']) && $params['generate_password']) {
            $key = array_search('password', $required_fields);
            unset($required_fields[$key]);
        }

        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $params)) {
                throw new coding_exception("Required parameter - {$field} not being passed");
            }
        }
    }

    /**
     * Check user input value match the value from defined internal list that including countries, timezone
     * auth, languages, theme and calendar_types.
     *
     * @param array $params User input.
     * @return void
     */
    public static function validate_value_from_defined_internal_list(array $params): void {
        $expected = [
            'country',
            'timezone',
            'lang',
            'theme',
            'calendartype',
            'auth'
        ];

        foreach ($expected as $field) {
            if (!isset($params[$field])) {
                continue;
            }

            $value = $params[$field];
            switch ($field) {
                case 'country': {
                    if (!array_key_exists($value, get_string_manager()->get_list_of_countries(true))) {
                        throw new moodle_exception("Country does not exist: " . $value);
                    }
                    break;
                }
                case 'timezone': {
                    if (!array_key_exists($value, core_date::get_list_of_timezones(null, true))) {
                        throw new moodle_exception("Timezone does not exist: " . $value);
                    }
                    break;
                }
                case 'lang': {
                    if (!array_key_exists($value, get_string_manager()->get_list_of_languages())) {
                        throw new moodle_exception("Language does not exist: " . $value);
                    }
                    break;
                }
                case 'theme': {
                    if (!array_key_exists($value, get_list_of_themes())) {
                        throw new moodle_exception("Theme does not exist: " . $value);
                    }
                    break;
                }
                case 'auth': {
                    if (!array_key_exists($value, core_component::get_plugin_list('auth'))) {
                        throw new moodle_exception("Auth plugin does not exist: " . $value);
                    }

                    if (!is_enabled_auth($value)) {
                        // Throw exception or debugging?
                        debugging("Auth plugin - " . $value . " is not being enabled", DEBUG_NORMAL);
                    }

                    $plugin = get_auth_plugin($value);
                    if (!$plugin->can_change_password()) {
                        throw new moodle_exception('The authentication plugin dose not support password creation.');
                    }
                    break;
                }
                case 'calendartype': {
                    if (!array_key_exists($value, type_factory::get_list_of_calendar_types())) {
                        throw new moodle_exception("Calendartype does not exist: " . $value);
                    }
                    break;
                }
            }
        }
    }

    /**
     * @param array $params User input.
     * @return void
     */
    public static function validate_blank(array $params): void {
        // Do not add password field here, whitespace is a valid password.
        $expected = [
            'firstname',
            'lastname',
            'username',
            'email',
        ];

        foreach ($expected as $field) {
            if (!array_key_exists($field, $params)) {
                continue;
            }

            // This check means that we can't use this method for password field, since ' ' is a valid password.
            $value = trim($params[$field] ?? '');

            // Do not use empty() as 0 is a valid value.
            if ($value === '') {
                throw new moodle_exception(ucfirst($field) . " can not be blank");
            }
        }
    }

    /**
     * @param mixed $password
     * @return void
     */
    public static function validate_password($password): void {
        global $CFG;

        if (is_array($password)) {
            if (!array_key_exists('password', $password)) {
                return;
            }
            $password = $password['password'];
        }

        // Do this check even if there is no password policy set.
        if ($password === '' || is_null($password)) {
            throw new coding_exception('Password cannot be blank');
        }

        // At this point password must be a string.
        if (!is_string($password)) {
            throw new coding_exception('Incorrect param being passed');
        }

        $err_msg = [];
        if (!empty($CFG->passwordpolicy)) {
            if (core_text::strlen($password) < $CFG->minpasswordlength) {
                $err_msg[] = get_string('errorminpasswordlength', 'auth', $CFG->minpasswordlength);
            }
            if (preg_match_all('/[[:digit:]]/u', $password, $matches) < $CFG->minpassworddigits) {
                $err_msg[] = get_string('errorminpassworddigits', 'auth', $CFG->minpassworddigits);
            }
            if (preg_match_all('/[[:lower:]]/u', $password, $matches) < $CFG->minpasswordlower) {
                $err_msg[] = get_string('errorminpasswordlower', 'auth', $CFG->minpasswordlower);
            }
            if (preg_match_all('/[[:upper:]]/u', $password, $matches) < $CFG->minpasswordupper) {
                $err_msg[] = get_string('errorminpasswordupper', 'auth', $CFG->minpasswordupper);
            }
            if (preg_match_all('/[^[:upper:][:lower:][:digit:]]/u', $password, $matches) < $CFG->minpasswordnonalphanum) {
                $err_msg[] = get_string('errorminpasswordnonalphanum', 'auth', $CFG->minpasswordnonalphanum);
            }
            if (!check_consecutive_identical_characters($password, $CFG->maxconsecutiveidentchars)) {
                $err_msg[] = get_string('errormaxconsecutiveidentchars', 'auth', $CFG->maxconsecutiveidentchars);
            }

            if (!empty($err_msg)) {
                throw new moodle_exception(implode(' ', $err_msg));
            }
        }
    }

    /**
     * @param mixed $email
     * @param int|null $targetuser_id
     * @return void
     */
    public static function validate_email($email, ?int $targetuser_id = null): void {
        global $CFG;

        if (is_array($email)) {
            if (!isset($email['email'])) {
                return;
            }
            $email = $email['email'];
        }

        if (!is_string($email)) {
            throw new coding_exception('Incorrect param being passed');
        }

        if (!validate_email($email)) {
            throw new moodle_exception('Invalid email format: ' . $email);
        }

        $builder = builder::table('user')->where('email', '=', $email);
        if (!empty($targetuser_id)) {
            $builder->where('id', '<>', $targetuser_id);
        }
        if (empty($CFG->allowaccountssameemail) && $builder->exists()) {
            throw new moodle_exception('Email address already exists: ' . $email);
        }

        $result = email_is_not_allowed($email);
        if (is_string($result) && !empty($result)) {
            throw new moodle_exception($result);
        }
    }

    /**
     * @param array $params User input.
     * @return void
     */
    public static function validate_length(array $params): void {
        $expected = [
            'username' => 100,
            'idnumber' => 255,
            'firstname' => 100,
            'lastname' => 200,
            'email' => 100,
            'skype' => 50,
            'phone1' => 20,
            'phone2' => 20,
            'institution' => 255,
            'department' => 255,
            'address' => 255,
            'city' => 120,
            'url' => 255,
            'lastnamephonetic' => 255,
            'firstnamephonetic' => 255,
            'middlename' => 255,
            'alternatename' => 255
        ];

        foreach ($expected as $key => $value) {
            if (isset($params[$key]) && core_text::strlen($params[$key]) > $value) {
                $field = $params[$key];
                throw new moodle_exception("Field {$field} should be less than {$value} characters");
            }
        }
    }

    /**
     * @return string[]
     */
    protected static function get_required_fields(): array {
        return ['username', 'email', 'firstname', 'lastname', 'password'];
    }

    /**
     * @return string[]
     */
    protected static function get_non_required_fields(): array {
        $fields = [
            'idnumber', 'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename',
            'city', 'url', 'skype', 'institution', 'department', 'phone1', 'phone2',
            'address', 'descriptionformat', 'description', 'country', 'timezone',
            'lang', 'theme', 'auth', 'calendartype', 'emailstop', 'suspended'
        ];

        //Append all user custom profile fields.
        return array_merge($fields, self::get_custom_fields());
    }

    /**
     * Note: custom fields are never required in API, even if 'Required' option is set on the custom field,
     * as that is designed for controlling end-user interactions.
     * @return array
     */
    public static function get_custom_fields(): array {
        $fields = [];
        $custom_fields = builder::table('user_info_field')->select(['id', 'shortname'])->fetch();
        foreach ($custom_fields as $custom_field) {
            $fields[] = field_helper::format_custom_field_short_name($custom_field->shortname);
        }
        return $fields;
    }

    /**
     * @return array
     */
    public static function get_fields(): array {
        return array_merge(self::get_required_fields(), self::get_non_required_fields());
    }

    /**
     * Validate custom fields before create/update target user.
     *
     * @param array $params
     * @param string $action
     * @param int|null $targetuser_id
     * @return void
     */
    public static function validate_custom_field_from_inputs(array $params, string $action = self::CREATE, ?int $targetuser_id = null): void {
        global $CFG;

        // Custom_fields is not set, we just return.
        if (!isset($params['custom_fields'])) {
            return;
        }

        foreach ($params['custom_fields'] as $custom_field) {
            $to_do_delete = isset($custom_field['delete']) && $custom_field['delete'];
            if ($action === self::CREATE) {
                if ($to_do_delete) {
                    throw new moodle_exception('Can not delete custom field on creating.');
                }
            } else if ($action === self::UPDATE) {
                if ($to_do_delete && (isset($custom_field['data']) || isset($custom_field['data_format']))) {
                    throw new moodle_exception('Can not set data or data_format with deleting custom field on updating.');
                }
            }

            $field = builder::get_db()->get_record('user_info_field', ['shortname' => $custom_field['shortname']]);
            if ($field === false) {
                throw new moodle_exception('The custom field does not exist.');
            }

            if (in_array($field->visible, [PROFILE_VISIBLE_NONE, PROFILE_VISIBLE_PRIVATE])){
                $context_user = context_user::instance(user::logged_in()->id);
                if (!has_capability('moodle/user:viewalldetails', $context_user)) {
                    throw new moodle_exception('The custom field does not exist.');
                }
            }

            if ($action === self::UPDATE && $to_do_delete) {
                if (!builder::table('user_info_data')
                    ->where('fieldid', $field->id)
                    ->where('userid', $targetuser_id)
                    ->exists()) {
                    throw new moodle_exception('Can not delete the custom field value, as the value has not been set for target user.') ;
                }
            }

            if (!$to_do_delete) {
                require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                $new_field = field_helper::format_custom_field_short_name($field->datatype);
                $form_field = new $new_field($field->id);
                $form_field->can_edit_locked_field();
                $form_field->validate_field_from_inputs($custom_field);
            }
        }
    }

    /**
     *
     * After user input value get validated, we call this function to validate user input contains locked fields or not.
     *
     * @param array $params User input.
     * @param string $action validation state.
     * @param int|null $targetuser_id target user id.
     * @return void
     */
    public static function validate_locked_fields(array $params, string $action = self::CREATE, ?int $targetuser_id = null): void {
        $user = user::logged_in();
        // Api user can fill the locked fields for user creation and also can update locked fields with valid capability,
        // no matter whether Api user is under tenancy or not.
        if ($action === self::CREATE || has_capability('moodle/user:update', context_system::instance(), $user->id)) {
            return;
        }

        if (isset($params['auth'])) {
            $auth = $params['auth'];
        } else {
            $user_entity = user::repository()->where('id', $targetuser_id)->one(true);
            $auth = $user_entity->auth;
        }

        $auth_plugin = get_auth_plugin($auth);
        $locked_fields_map = $auth_plugin->get_locked_fields_map();

        $locked_fields = $locked_fields_map['locked'];
        $unlocked_if_empty_fields = $locked_fields_map['unlockedifempty'];
        if (empty($locked_fields) && empty($unlocked_if_empty_fields)) {
            return;
        }

        foreach (array_keys($params) as $param_key) {
            // If user input contains locked field, target user can not be updated.
            if (in_array($param_key, $locked_fields)) {
                throw new moodle_exception("The {$param_key} is Locked and can not be updated");
            }

            // If target user already has value for the field, target user can not be updated.
            if (in_array($param_key, $unlocked_if_empty_fields)) {
                if ($user_entity->{$param_key} !== '') {
                    throw new moodle_exception("The {$param_key} is Locked and can not be updated");
                }
            }
        }

        // Check custom fields, if auth plugin supports.
        if ($auth_plugin->can_support_custom_fields_for_auth_lock() && isset($params['custom_fields'])) {
            foreach ($params['custom_fields'] as $custom_field) {
                $config_variable_name = field_helper::format_custom_field_short_name($custom_field['shortname']);
                if (in_array($config_variable_name, $locked_fields)) {
                    throw new moodle_exception("Custom field for {$custom_field['shortname']} is Locked and can not be updated");
                }

                if (in_array($config_variable_name, $unlocked_if_empty_fields)) {
                    $record = builder::table('user_info_data', 'uid')
                        ->join(['user_info_field', 'uif'], 'uid.fieldid', 'uif.id')
                        ->where('uif.shortname', $custom_field['shortname'])
                        ->where('uid.userid', $targetuser_id)
                        ->select_raw('uid.data')
                        ->one();

                    if (!is_null($record) && $record->data !== '') {
                        throw new moodle_exception("Custom field for {$custom_field['shortname']} is Locked and can not be updated");
                    }
                }
            }
        }
    }
}