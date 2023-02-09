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
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use core\entity\user;
use core_user\profile\user_field_resolver;
use user_picture;

/**
 * Abstraction class for participant connected to a participant instance.
 *
 * @package mod_perform\models\activity
 *
 * @property-read int $id
 * @property-read string $fullname
 * @property-read string $email
 * @property-read string $profileimagealt
 * @property-read string $profileimageurlsmall
 */
class participant {

    /**
     * Participant user object.
     *
     * @var user|external_participant
     */
    private $user;

    /**
     * Participant source.
     *
     * @var int
     */
    private $source;

    /**
     * Fields available on a participant.
     *
     * @var array
     */
    public static $fields = [
        'id',
        'fullname',
        'email',
        'profileimagealt',
        'profileimageurlsmall',
        'source'
    ];

    /**
     * Fields that can be fetched strictly from this model.
     * Used in participant type resolver.
     *
     * @var array
     */
    public static $model_only_fields = ['source'];

    /**
     * participant constructor.
     *
     * @param user|external_participant $participant
     * @param int $participant_source
     */
    public function __construct($participant, int $participant_source = participant_source::INTERNAL) {
        if (!$participant instanceof user && !$participant instanceof external_participant) {
            throw new coding_exception(sprintf("Invalid class %s loaded into participant.", get_class($participant)));
        }

        $this->user = $participant;
        $this->source = $participant_source;
    }

    /**
     * Gets the source of the participant.
     *
     * @return string
     */
    public function get_source(): string {
        return participant_source::SOURCE_TEXT[$this->source];
    }

    /**
     * @return bool
     */
    public function is_internal(): bool {
        return $this->source === participant_source::INTERNAL;
    }

    /**
     * @return bool
     */
    public function is_external(): bool {
        return $this->source === participant_source::EXTERNAL;
    }

    /**
     * Get the user profile image alt text.
     *
     * @return string
     */
    public function get_profileimagealt(): string {
        if ($this->is_external()) {
            return $this->user->get_profileimagealt() ?? '';
        }

        $field_resolver = user_field_resolver::from_record($this->user->to_record());
        return $field_resolver->get_field_value('profileimagealt') ?? '';
    }

    /**
     * Get the user profile image url.
     *
     * @return string
     */
    public function get_profileimageurlsmall(): string {
        global $PAGE;

        return $this->source === participant_source::EXTERNAL
            ? $this->user->get_profileimageurlsmall()
            :  (new user_picture($this->user->to_record(), 0))
                ->get_url($PAGE)
                ->out(false);
    }

    /**
     * Gets the user object loaded into the participant.
     *
     * @return user|external_participant
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Magic attribute getter
     *
     * @param string $field
     * @return mixed|null
     */
    public function __get(string $field) {
        $get_method = 'get_' . $field;

        $result = method_exists($this, $get_method)
            ? $this->$get_method()
            : $this->user->$field;

        return $result ?? null;
    }
}
