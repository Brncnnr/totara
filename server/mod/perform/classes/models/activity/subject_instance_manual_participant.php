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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use core\orm\collection;
use core\orm\entity\model;
use mod_perform\entity\activity\subject_instance_manual_participant as manual_participant_entity;

/**
 * A manually selected user (either inside or outside of the system) that will participate in a subject instance's activity.
 *
 * @property-read int $id
 * @property-read int $subject_instance_id
 * @property-read int $core_relationship_id
 * @property-read int $user_id Internal user
 * @property-read string $email External user email
 * @property-read string $name External user name
 * @property-read int $created_at
 * @property-read int $created_by Who selected the user
 *
 * @package mod_perform\models\activity
 */
class subject_instance_manual_participant extends model {

    /**
     * Create a manual participant record for an internal user.
     *
     * @param int $subject_instance_id
     * @param int $by_user User ID of who is making the selection
     * @param int $relationship_id
     * @param int $user_id
     * @return subject_instance_manual_participant
     */
    public static function create_for_internal(int $subject_instance_id, int $by_user, int $relationship_id, int $user_id): self {
        $entity = static::init_entity($subject_instance_id, $by_user, $relationship_id);
        $entity->user_id = $user_id;
        $entity->save();

        return self::load_by_entity($entity);
    }

    /**
     * Create a manual participant record for an external user.
     *
     * @param int $subject_instance_id
     * @param int $by_user User ID of who is making the selection
     * @param int $relationship_id
     * @param string $name
     * @param string $email
     * @return subject_instance_manual_participant
     */
    public static function create_for_external(
        int $subject_instance_id,
        int $by_user,
        int $relationship_id,
        string $email,
        string $name
    ): self {
        global $CFG;
        require_once($CFG->dirroot . '/lib/weblib.php');
        require_once($CFG->dirroot . '/lib/classes/user.php');

        // An actual email address must be specified.
        if (!validate_email($email)) {
            throw new coding_exception("Invalid email address specified: '$email'");
        }

        // Make sure there are no dodgy characters being saved.
        $validation = \core_user::validate([
            'email' => $email,
            'firstname' => $name,
        ]);
        if ($validation !== true) {
            throw new coding_exception("Invalid user properties '$name' and '$email' were provided");
        }

        $entity = static::init_entity($subject_instance_id, $by_user, $relationship_id);
        $entity->email = $email;
        $entity->name = trim($name);
        $entity->save();

        return self::load_by_entity($entity);
    }

    /**
     * Set the internal participant users for a relationships for a subject instance.
     *
     * @param int $subject_instance_id
     * @param int $by_user User ID of who is making the selection
     * @param int $relationship_id
     * @param array[] $internal_users Array of ['user_id' => ID]
     *
     * @return static[]|collection
     */
    public static function create_multiple_for_internal(
        int $subject_instance_id,
        int $by_user,
        int $relationship_id,
        array $internal_users
    ): collection {
        global $DB;
        static::validate_users_specified($internal_users, 'user_id');

        return $DB->transaction(static function () use ($subject_instance_id, $by_user, $relationship_id, $internal_users) {
            $created = new collection([]);
            foreach ($internal_users as $user) {
                $created->append(
                    static::create_for_internal($subject_instance_id, $by_user, $relationship_id, $user['user_id'])
                );
            }
            return $created;
        });
    }

    /**
     * Set the external participant users for a relationships for a subject instance.
     *
     * @param int $subject_instance_id
     * @param int $by_user User ID of who is making the selection
     * @param int $relationship_id
     * @param array[] $external_users Array of ['name' => name, 'email' => email address]
     *
     * @return static[]|collection
     */
    public static function create_multiple_for_external(
        int $subject_instance_id,
        int $by_user,
        int $relationship_id,
        array $external_users
    ): collection {
        global $DB;
        static::validate_users_specified($external_users, 'email');

        return $DB->transaction(static function () use ($subject_instance_id, $by_user, $relationship_id, $external_users) {
            $created = new collection([]);
            foreach ($external_users as $user) {
                $created->append(
                    static::create_for_external($subject_instance_id, $by_user, $relationship_id, $user['email'], $user['name'])
                );
            }
            return $created;
        });
    }

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return manual_participant_entity::class;
    }

    /**
     * Initialise a manual participant entity.
     *
     * @param int $subject_instance_id
     * @param int $by_user User ID of who is making the selection
     * @param int $relationship_id
     * @return manual_participant_entity
     */
    private static function init_entity(int $subject_instance_id, int $by_user, int $relationship_id): manual_participant_entity {
        $entity = new manual_participant_entity();
        $entity->subject_instance_id = $subject_instance_id;
        $entity->core_relationship_id = $relationship_id;
        $entity->created_by = $by_user;
        return $entity;
    }

    /**
     * Make sure the specified user array has users in it and the users are unique.
     *
     * @param array $users Array of users
     * @param string $unique_key E.g. 'email' or 'user_id'
     * @throws coding_exception
     */
    private static function validate_users_specified(array $users, string $unique_key): void {
        if (empty($users)) {
            throw new coding_exception('Must specify at least one user to create a manual participant record.');
        }

        $users_to_create = [];
        foreach ($users as $user) {
            if (in_array($user[$unique_key], $users_to_create, true)) {
                throw new coding_exception(
                    "Can not create multiple participant records for user with $unique_key {$user[$unique_key]}"
                );
            }
            $users_to_create[] = $user[$unique_key];
        }
    }

}
