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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace core_user\totara_notification\placeholder;

use coding_exception;
use core\entity\user as user_entity;
use core_date;
use core_user\access_controller;
use html_writer;
use moodle_url;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class user extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var user_entity|null
     */
    protected $entity;

    /**
     * user constructor.
     * @param user_entity|null $entity
     */
    public function __construct(?user_entity $entity) {
        $this->entity = $entity;
    }

    /**
     * @param int  $id
     * @param bool $strict
     *
     * @return static
     */
    public static function from_id(int $id, bool $strict = false): self {
        global $DB;

        $strictness = $strict ? MUST_EXIST : IGNORE_MISSING;

        $instance = self::get_cached_instance($id);
        if (!$instance) {
            $user_record = $DB->get_record(user_entity::TABLE, ['id' => $id], '*', $strictness);
            $entity = !$user_record ? null : new user_entity($user_record);
            $instance = new static($entity);
            self::add_instance_to_cache($id, $instance);
        }
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('first_name', get_string('firstname', 'moodle')),
            option::create('last_name', get_string('lastname', 'moodle')),
            option::create('full_name', get_string('fullname', 'moodle')),
            option::create('full_name_link', get_string('full_name_linked', 'moodle')),
            option::create('username', get_string('username', 'moodle')),
            option::create('id_number', get_string('idnumber', 'moodle')),
            option::create('address', get_string('address', 'moodle')),
            option::create('description', get_string('description', 'moodle')),
            option::create('institution', get_string('institution', 'moodle')),
            option::create('lang', get_string('language', 'moodle')),
            option::create('skype', get_string('skypeid', 'moodle')),
            option::create('phone1', get_string('phone1', 'moodle')),
            option::create('phone2', get_string('phone2', 'moodle')),
            option::create('url', get_string('url', 'moodle')),
            option::create('email', get_string('email', 'moodle')),
            option::create('city', get_string('city', 'moodle')),
            option::create('country', get_string('country', 'moodle')),
            option::create('department', get_string('department', 'moodle')),
            option::create('first_name_phonetic', get_string('firstnamephonetic', 'moodle')),
            option::create('last_name_phonetic', get_string('lastnamephonetic', 'moodle')),
            option::create('middle_name', get_string('middlename', 'moodle')),
            option::create('alternate_name', get_string('alternatename', 'moodle')),
            option::create('time_zone', get_string('timezone', 'moodle'))
        ];
    }

    /**
     * We want underscores in our keys, so map them to the user DB fields.
     *
     * @return string[]
     */
    protected static function get_keys_to_entity_map(): array {
        return [
            'first_name' => 'firstname',
            'last_name' => 'lastname',
            'username' => 'username',
            'id_number' => 'idnumber',
            'address' => 'address',
            'description' => 'description',
            'institution' => 'institution',
            'lang' => 'lang',
            'skype' => 'skype',
            'phone1' => 'phone1',
            'phone2' => 'phone2',
            'url' => 'url',
            'city' => 'city',
            'country' => 'country',
            'department' => 'department',
            'first_name_phonetic' => 'firstnamephonetic',
            'last_name_phonetic' => 'lastnamephonetic',
            'middle_name' => 'middlename',
            'alternate_name' => 'alternatename',
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return null !== $this->entity;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ('full_name_link' === $key) {
            return true;
        }

        return parent::is_safe_html($key);
    }

    /**
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        if (null === $this->entity) {
            throw new coding_exception("The user entity record is empty");
        }

        switch ($key) {
            case 'full_name':
                $user_record = $this->entity->to_record();
                return fullname($user_record);
            case 'full_name_link':
                $user_record = $this->entity->to_record();
                $url = new moodle_url('/user/profile.php', ['id' => $user_record->id]);
                return html_writer::link($url, fullname($user_record));
            case 'time_zone':
                $user_record = $this->entity->to_record();
                return core_date::get_localised_timezone(
                    core_date::get_user_timezone($user_record)
                );
            case 'email':
                // Check if the recipient can see this user's email.
                if (access_controller::for($this->entity->to_record())->can_view_field('email')) {
                    return $this->entity->email;
                }
                return get_string('email_not_visible', 'moodle');
            case 'url':
                $user_record = $this->entity->to_record();
                if (empty($user_record->url)){
                    // No available data for url
                    return NULL;
                }
                $url = new moodle_url($user_record->url);
                return html_writer::link($url, $url);
            default:
                $invalid_keys = ['password'];
                $map = self::get_keys_to_entity_map();
                if (!in_array($key, $invalid_keys) && $this->entity->has_attribute($map[$key])) {
                    return (string) $this->entity->get_attribute($map[$key]);
                }
        }

        // If we follow the process from the template engine, there is no chance that the code should
        // go to this point. However it has to be here to warn developer that if they call this function
        // with the invalid $key directly from somewhere else.
        throw new coding_exception("Invalid key '{$key}' is not yet supported");
    }
}