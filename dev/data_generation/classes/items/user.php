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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace degeneration\items;

use core\entity\user as user_entity;
use core\orm\entity\entity;
use core\orm\query\builder;
use degeneration\App;
use degeneration\Cache;

/**
 * Class user
 *
 * @method user_entity get_data()
 *
 * @package degeneration\items
 */
class user extends item {

    protected static $roles = [];

    /**
     * Array of courses user enrolled in.
     *
     * @var array
     */
    protected $courses = [];

    /**
     * Get a list of courses a user is enrolled in. This returns ONLY courses enrolled via this object
     *
     * @return array
     */
    public function get_courses(): array {
        return $this->courses;
    }

    /**
     * Add a course to the list of courses a user enrolled to. It doesn't actually check whether a user is enrolled.
     *
     * @param course $course
     * @return $this
     */
    public function add_enrolled_course(course $course) {
        $this->courses[] = $course;

        return $this;
    }

    /**
     * Table name of the item to generate
     *
     * @return string
     */
    public function get_table(): string {
        return 'user';
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function get_properties(): array {
        return [
            'description' => function () {
                return "Test user description " . App::faker()->bs;
            },
            'descriptionformat' => FORMAT_MOODLE,
            'country' => function () {
                $countries = ['AZ', 'NZ', 'AU', 'CA', 'CZ', 'US'];
                return  $countries[array_rand($countries)];
            },
            'city' => function () {
                $cities = ['Wellington', 'Christchurch', 'Auckland', 'Dunedin', 'Lower Hutt'];
                return  $cities[array_rand($cities)];
            }
        ];
    }

    /**
     * Load existing user items
     *
     * @return array
     */
    public static function load_existing() {
        $users_loaded = user_entity::repository()
            ->get_lazy();

        $users = [];
        foreach ($users_loaded as $user) {
            $users[] = (new self())->fill($user);
        }

        return $users;
    }

    /**
     * Loads existing user_ids
     *
     * @return array
     */
    public static function load_existing_ids(): array {
        $user_ids = builder::get_db()->get_fieldset_select('user', 'id', '');

        return array_combine($user_ids, $user_ids);
    }

    public function fill(entity $entity) {
        $this->data = $entity;

        Cache::get()->add($this);

        return $this;
    }

    /**
     * Save a user
     *
     * @return bool
     */
    public function save(): bool {
        $properties = [];

        foreach ($this->get_properties() as $key => $property) {
            $properties[$key] = $this->evaluate_property($property);
        }

        $this->data = new user_entity((array) App::generator()->create_user($properties));

        Cache::get()->add($this);

        return true;
    }

    /**
     * Save a user
     *
     * @return array
     */
    public function create_for_bulk(): array {
        $properties = [];

        foreach ($this->get_properties() as $key => $property) {
            $properties[$key] = $this->evaluate_property($property);
        }

        return App::generator()->create_user($properties, ['noinsert' => true]);
    }

    /**
     * Enrol user to a given course
     *
     * @param int $user_id
     * @param course $course
     * @param string $role
     * @return bool
     */
    public static function enrol(int $user_id, course $course, string $role = 'student'): bool {
        if (!isset(self::$roles[$role])) {
            self::$roles[$role] = builder::table('role')
                ->where('shortname', $role)
                ->one(true);
        }

        $role_entity = self::$roles[$role];

        return App::generator()->enrol_user($user_id, $course->get_data()->id, $role_entity->id);
    }

    /**
     * Add user to an audience
     *
     * @param audience $audience
     * @return void
     */
    public function add_to_audience(audience $audience): void {
        $member = $audience->add_member($this);
        builder::get_db()->insert_record('cohort_members', $member);
    }

}