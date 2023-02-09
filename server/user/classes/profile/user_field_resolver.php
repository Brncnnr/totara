<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */
namespace core_user\profile;

use core\format;
use core_date;
use core_user\access_controller;

/**
 * A class to help on fetching the user's property value.
 * Note that it will resolve whether the current user is able to see the value or not.
 */
final class user_field_resolver {
    /**
     * All the fields that fare from table "ttr_user"
     * @var array
     */
    private const FIELDS_DB = [
        'id',                       // Type: bigint                 , default nextval (not null)
        'auth',                     // Type: character varying(20)  , default 'manual' (not null)
        'confirmed',                // Type: smallint               , default 0 (not null)
        'policyagreed',             // Type: smallint               , default 0 (not null)
        'deleted',                  // Type: smallint               , default 0 (not null)
        'suspended',                // Type: smallint               , default 0 (not null)
        'username',                 // Type: character varying(100) , default '' (not null)
        'password',                 // Type: character varying(255) , default '' (not null)
        'idnumber',                 // Type: character varying(255) , default '' (not null)
        'firstname',                // Type: character varying(100) , default '' (not null)
        'lastname',                 // Type: character varying(100) , default '' (not null)
        'email',                    // Type: character varying(100) , default '' (not null)
        'emailstop',                // Type: smallint               , default 0 (not null)
        'skype',                    // Type: character varying(50)  , default '' (not null)
        'phone1',                   // Type: character varying(20)  , default '' (not null)
        'phone2',                   // Type: character varying(20)  , default '' (not null)
        'institution',              // Type: character varying(255) , default '' (not null)
        'department',               // Type: character varying(255) , default '' (not null)
        'address',                  // Type: character varying(255) , default '' (not null)
        'city',                     // Type: character varying(120) , default '' (not null)
        'country',                  // Type: character varying(2)   , default '' (not null)
        'lang',                     // Type: character varying(30)  , default 'en' (not null)
        'calendartype',             // Type: character varying(30)  , default 'gregorian' (not null)
        'theme',                    // Type: character varying(50)  , default '' (not null)
        'timezone',                 // Type: character varying(100) , default '99' (not null)
        'firstaccess',              // Type: bigint                 , default 0 (not null)
        'lastaccess',               // Type: bigint                 , default 0 (not null)
        'lastlogin',                // Type: bigint                 , default 0 (not null)
        'currentlogin',             // Type: bigint                 , default 0 (not null)
        'lastip',                   // Type: character varying(45)  , default '' (not null)
        'secret',                   // Type: character varying(15)  , default '' (not null)
        'picture',                  // Type: bigint                 , default 0 (not null)
        'url',                      // Type: character varying(255) , default '' (not null)
        'description',              // Type: text                   , default  ()
        'descriptionformat',        // Type: smallint               , default 1 (not null)
        'mailformat',               // Type: smallint               , default 1 (not null)
        'maildigest',               // Type: smallint               , default 0 (not null)
        'maildisplay',              // Type: smallint               , default 2 (not null)
        'autosubscribe',            // Type: smallint               , default 1 (not null)
        'trackforums',              // Type: smallint               , default 0 (not null)
        'timecreated',              // Type: bigint                 , default 0 (not null)
        'timemodified',             // Type: bigint                 , default 0 (not null)
        'trustbitmask',             // Type: bigint                 , default 0 (not null)
        'imagealt',                 // Type: character varying(255) , default  ()
        'lastnamephonetic',         // Type: character varying(255) , default  ()
        'firstnamephonetic',        // Type: character varying(255) , default  ()
        'middlename',               // Type: character varying(255) , default  ()
        'alternatename',            // Type: character varying(255) , default  ()
        'totarasync',               // Type: smallint               , default 0 (not null)
    ];

    /**
     * All the computed field.
     * @var array
     */
    private const FIELDS_COMPUTED = [
        'fullname',
        'interests',
        'profileimagealt',
        'profileimageurl',
        'profileimageurlsmall',
        'profileurl',
        'mailtourl',
        'custom_fields',
    ];

    /**
     * List of fields which are relations rather than properties, so that they don't get caught up in profile field
     * validation.
     * @var array
     */
    private const FIELDS_RELATIONS = [
        'job_assignments'
    ];

    /**
     * The target user's record, the one that are going to be used to give information.
     * Using array to store the information because we do not want this property being affected by the
     * upstream references. Only happening if it is an object.
     *
     * @var array
     */
    private $target_user_record;

    /**
     * @var access_controller
     */
    private $access_controller;

    /**
     * A hashmap of custom field shortname and its value.
     * @var array
     */
    private $target_user_custom_record;

    /**
     * user constructor.
     * @param array             $target_user_record
     * @param access_controller $controller
     */
    protected function __construct(array $target_user_record, access_controller $controller) {
        $this->target_user_record = $target_user_record;
        $this->access_controller = $controller;
        $this->target_user_custom_record = [];
    }

    /**
     * @param int       $target_user_id
     * @param int|null  $course_id
     *
     * @return user_field_resolver
     */
    public static function from_id(int $target_user_id, ?int $course_id = null): user_field_resolver {
        $target_user_record = \core_user::get_user($target_user_id, '*', MUST_EXIST);
        $controller = access_controller::for($target_user_record, $course_id);

        // Convert to array.
        $target_user_record = get_object_vars($target_user_record);
        return new static($target_user_record, $controller);
    }

    /**
     * @param \stdClass $target_user_record
     * @param int|null  $course_id
     *
     * @return user_field_resolver
     */
    public static function from_record(\stdClass $target_user_record, ?int $course_id = null): user_field_resolver {
        $target_user_data = get_object_vars($target_user_record);
        if (!isset($target_user_data['id'])) {
            throw new \coding_exception(
                "No id property was found for user's record provided to " . __CLASS__
            );
        }

        $controller = access_controller::for($target_user_record, $course_id);
        return new static($target_user_data, $controller);
    }

    /**
     * @return void
     */
    public function load_custom_fields(): void {
        global $CFG;

        if (!empty($this->target_user_custom_record)) {
            return;
        }

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        $target_user_id = $this->target_user_record['id'];
        $custom_record = profile_user_record($target_user_id);

        $this->target_user_custom_record = get_object_vars($custom_record);
    }

    /**
     * Checking whether the given $field_name is a valid field from either DB or computed fields.
     * @param string $field_name
     * @return bool
     */
    public static function is_valid_field(string $field_name): bool {
        if (static::is_db_field($field_name)) {
            return true;
        }

        if (static::is_computed_field($field_name)) {
            return true;
        }

        if (static::is_related_field($field_name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $field_name
     * @return bool
     */
    public function has_custom_field(string $field_name): bool {
        $this->load_custom_fields();
        return isset($this->target_user_custom_record[$field_name]);
    }

    /**
     * Returning null means that the requester is not able to see it, or the field
     * does not have value at all.
     *
     * @param string $field_name
     * @return mixed|null
     */
    public function get_field_value(string $field_name) {
        if ('password' === $field_name || 'secret' === $field_name) {
            // Nope, better to not return it. External can fetch db itself.
            return null;
        }

        if (!static::is_valid_field($field_name)) {
            throw new \coding_exception("Unknown user field '{$field_name}', perhaps check for custom field");
        }

        if ('descriptionformat' === $field_name && $this->access_controller->can_view_field('descriptionformat')) {
            // Transform the format field from the constants to a core_format string.
            $descriptionformat = $this->do_get_value('descriptionformat');
            return format::from_moodle($descriptionformat);
        }

        if ('profileurl' === $field_name && $this->access_controller->get_profile_url()) {
            // Special custom computed field 'profileurl'
            return $this->do_get_value('profileurl');
        }

        if ('mailtourl' === $field_name && $this->access_controller->can_view_field('email')) {
            // Special custom computed field 'mailtourl'
            return $this->do_get_value('mailtourl');
        }

        if (in_array($field_name, ['mailtourl', 'profileurl'])) {
            // These two fields should be resolved nicely above this block of `if` code.
            // However, if the actor does not have permissions to view any of these fields
            // we will return null, as access_controller will not understand the special computed field.
            return null;
        }

        if ($this->access_controller->can_view_field($field_name)) {
            return $this->do_get_value($field_name);
        }

        return null;
    }

    /**
     * @param string $field_name
     * @return mixed|null
     */
    public function get_custom_field_value(string $field_name) {
        if (!$this->has_custom_field($field_name)) {
            return null;
        }

        return $this->target_user_custom_record[$field_name];
    }

    /**
     * @param string $field
     * @return mixed
     */
    protected function do_get_value(string $field) {
        global $PAGE, $USER;

        $user_record = (object) $this->target_user_record;

        // The following fields require special handling.
        switch ($field) {
            case 'profileimageurl':
                $picture = new \user_picture($user_record, 1);
                return $picture->get_url($PAGE)->out(false);

            case 'profileimageurlsmall':
                $picture = new \user_picture($user_record, 0);
                return $picture->get_url($PAGE)->out(false);

            case 'fullname':
                return fullname($user_record);

            case 'interests':
                $user_id = $this->target_user_record['id'];
                $interests = \core_tag_tag::get_item_tags_array(
                    'core',
                    'user',
                    $user_id,
                    \core_tag_tag::BOTH_STANDARD_AND_NOT,
                    0,
                    false
                );

                if (!empty($interests)) {
                    return implode(', ', $interests);
                }

                return null;
            case 'profileimagealt':
                return !empty($this->target_user_record['imagealt'])
                    ? $this->target_user_record['imagealt']
                    : fullname($user_record);

            case 'profileurl':
                $user_id = $this->target_user_record['id'];
                $parameters = [];

                if ($USER->id != $user_id) {
                    // Actor is viewing someone else profile.
                    $parameters['id'] = $user_id;
                }

                $url = new \moodle_url("/user/profile.php", $parameters);
                return $url->out(false);

            case 'mailtourl':
                $email = $this->get_user_property('email');
                return "mailto:{$email}";

            case 'timezone':
                return core_date::get_localised_timezone(core_date::get_user_timezone($user_record));

            case 'country':
                return !empty($this->target_user_record['country']) ? get_string($this->target_user_record['country'], 'countries') : null;

            default:
                return $this->get_user_property($field);
        }
    }

    /**
     * @param string $field_name
     * @return mixed
     */
    protected function get_user_property(string $field_name) {
        if (
            in_array($field_name, static::FIELDS_DB) &&
            !array_key_exists($field_name, $this->target_user_record)
        ) {
            $this->load_target_user_db_fields();
        }

        if (array_key_exists($field_name, $this->target_user_record)) {
            return $this->target_user_record[$field_name];
        }

        return null;
    }

    /**
     * Loading all the fields from the user table and add it to record data, if
     * it is missing. Otherwise, performing an assertion to make sure that the user's record data
     * is not modified during run-time.
     *
     * @return void
     */
    public function load_target_user_db_fields(): void {
        global $DB;

        $target_user_id = $this->target_user_record['id'];
        $user_record = $DB->get_record('user', ['id' => $target_user_id], '*', MUST_EXIST);

        $user_data = get_object_vars($user_record);
        foreach ($user_data as $field => $value) {
            if (!isset($this->target_user_record[$field])) {
                $this->target_user_record[$field] = $value;
                continue;
            }

            if ($this->target_user_record[$field] != $value) {
                throw new \coding_exception('Properties have been modified, DO NOT modify the user record.');
            }
        }
    }

    /**
     * Returning a dummy data object of target user.
     *
     * @param bool $load_db_fields
     * @return array
     */
    public function get_target_user_record(bool $load_db_fields = true): array {
        if ($load_db_fields) {
            $this->load_target_user_db_fields();
        }

        return $this->target_user_record;
    }

    /**
     * Checking whether the given $field_name is existing in the record data of
     * current user within this class.
     *
     * @param string $field_name
     * @return bool
     */
    public function field_exist_in_user_instance(string $field_name): bool {
        return array_key_exists($field_name, $this->target_user_record);
    }

    /**
     * Checking whether the field is in computed field.
     *
     * @param string $field_name
     * @return bool
     */
    public static function is_computed_field(string $field_name): bool {
        return in_array($field_name, static::FIELDS_COMPUTED);
    }

    /**
     * Checking whether the field is in db field.
     *
     * @param string $field_name
     * @return bool
     */
    public static function is_db_field(string $field_name): bool {
        return in_array($field_name, static::FIELDS_DB);
    }

    /**
     * @param string $field_name
     * @return bool
     */
    public static function is_related_field(string $field_name): bool {
        return in_array($field_name, static::FIELDS_RELATIONS);
    }
}
