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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\entity\activity\external_participant as external_participant_entity;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use user_picture;

/**
 * Represents a single external participant.
 *
 * @property-read int $id
 * @property-read string $fullname
 * @property-read string $email
 * @property-read string $token
 * @property-read string $profileimageurlsmall
 * @property-read participant_instance|null $participant_instance
 *
 * @package mod_perform\models\activity
 */
class external_participant extends model {
    protected $entity_attribute_whitelist = [
        'id',
        'email',
        'token'
    ];

    protected $model_accessor_whitelist = [
        'fullname',
        'profileimageurlsmall',
        'profileimagealt',
        'participant_instance'
    ];

    /**
     * {@inheritdoc}
     */
    protected static function get_entity_class(): string {
        return external_participant_entity::class;
    }

    /**
     * Creates a record for the given external participant name/email. Note:
     * it is possible to have multiple external participants with the same name
     * and email combination. The only way to distinguish between them is by the
     * id and/or token.
     *
     * @param int $participant_instance_id
     * @param string $fullname external participant name.
     * @param string $email external participant email.
     *
     * @return external_participant the newly created model.
     */
    public static function create(int $participant_instance_id, string $fullname, string $email): external_participant {
        if (empty(trim($fullname)) || empty(trim($email))) {
            throw new coding_exception('Fullname or email for external participant cannot be empty.');
        }

        $entity = builder::get_db()->transaction(function () use ($participant_instance_id, $fullname, $email) {
            $entity = new external_participant_entity();
            $entity->name = $fullname;
            $entity->email = $email;
            $entity->token = self::generate_token($participant_instance_id);
            $entity->save();

            // Now update the participant id in the instance table
            /** @var participant_instance $participant_instance */
            $participant_instance = participant_instance::repository()
                ->find_or_fail($participant_instance_id);

            if ($participant_instance->participant_source != participant_source::EXTERNAL) {
                throw new coding_exception('Something went wrong, invalid participant source detected.');
            }

            $participant_instance->participant_id = $entity->id;
            $participant_instance->save();

            return $entity;
        });


        return new external_participant($entity);
    }

    /**
     * Generates a unique token hash.
     *
     * @param int $participant_instance_id
     *
     * @return string the generated token.
     */
    private static function generate_token(int $participant_instance_id): string {
        // Even though technically not necessary hashing it with md5
        // obfuscates the quite obvious PHP password hash result
        return hash('sha256', password_hash(uniqid($participant_instance_id), PASSWORD_DEFAULT));
    }

    /**
     * Retrieves an external participant by its unique token.
     *
     * @param string $token token to look up.
     *
     * @return external_participant the participant.
     */
    public static function load_by_token(string $token): external_participant {
        $entity = external_participant_entity::repository()
            ->where('token', $token)
            ->one(true);

        return static::load_by_entity($entity);
    }

    /**
     * Get the external participant's name.
     *
     * @return string the name
    */

    public function get_fullname(): string {
        return $this->entity->name;
    }

    /**
     * Get the profile image alt text of an external participant.
     *
     * @return string
     */
    public function get_profileimagealt(): string {
        return $this->get_fullname();
    }

    /**
     * Get the profile image of an external participant.
     *
     * @return string
    */
    public function get_profileimageurlsmall(): string {
        return $this->get_default_image();
    }

    /**
     * Get associated participant instance if any.
     *
     * @return participant_instance_model the participant instance model. This can be null
     *         if it has not been created yet.
     */
    public function get_participant_instance(): ?participant_instance_model {
        return $this->entity->participant_instance ?
            participant_instance_model::load_by_entity($this->entity->participant_instance)
            : null;
    }

    /**
     * Deletes the external participant. Note: after this, the model is invalid.
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * Get default image string.
     *
     * @return string
     */
    private function get_default_image(): string {
        global $PAGE;

        return (new user_picture($this->get_record(), 0))
            ->get_url($PAGE)
            ->out(false);
    }

    /**
     * Returns a fake record
     *
     * @return \stdClass
     */
    private function get_record(): \stdClass {
        return (object) [
            'id' => $this->id,
            'picture' => '',
            'firstname' => $this->fullname,
            'lastname' => $this->fullname,
            'firstnamephonetic' => '',
            'lastnamephonetic' => '',
            'middlename' => '',
            'alternatename' => '',
            'imagealt' => '',
            'email' => $this->email
        ];
    }

}
