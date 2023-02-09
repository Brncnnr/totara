<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;

/**
 * User entity
 *
 * @property-read int $id ID
 * @property string $auth
 * @property bool $confirmed
 * @property bool $policyagreed
 * @property bool $deleted
 * @property bool $suspended
 * @property int $mnethostid
 * @property string $username
 * @property string $password
 * @property string $idnumber
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property bool $emailstop
 * @property string $icq
 * @property string $skype
 * @property string $yahoo
 * @property string $aim
 * @property string $msn
 * @property string $phone1
 * @property string $phone2
 * @property string $institution
 * @property string $department
 * @property string $address
 * @property string $city
 * @property string $country
 * @property string $lang
 * @property string $calendartype
 * @property string $theme
 * @property string $timezone
 * @property int $firstaccess
 * @property int $lastaccess
 * @property int $lastlogin
 * @property int $currentlogin
 * @property string $lastip
 * @property string $secret
 * @property int $picture
 * @property string $url
 * @property string $description
 * @property int $descriptionformat
 * @property int $mailformat
 * @property int $maildigest
 * @property bool $maildisplay
 * @property bool $autosubscribe
 * @property bool $trackforums
 * @property int $timecreated
 * @property int $timemodified
 * @property int $trustbitmask
 * @property string $imagealt
 * @property string $lastnamephonetic
 * @property string $firstnamephonetic
 * @property string $middlename
 * @property string $alternatename
 * @property bool $totarasync
 * @property int $tenantid
 *
 * @property-read string $fullname
 * @property-read bool $is_logged_in
 * @property-read collection|user_enrolment[] $user_enrolments
 *
 * @method static user_repository repository()
 */
class user extends entity {

    public const TABLE = 'user';

    /**
     * Do not add extra attributes here, otherwise it will break legacy compatibility.
     *
     * @var array
     */
    protected $extra_attributes = [];

    /**
     * Get the logged in user
     *
     * @return user|null
     */
    public static function logged_in(): ?user {
        global $USER;

        if (empty($USER) || empty($USER->id)) {
            return null;
        }

        return new static($USER, false);
    }

    /**
     * Is this user currently logged in?
     *
     * @return bool
     */
    public function is_logged_in(): bool {
        global $USER;
        return (int) $this->id === (int) $USER->id;
    }

    /**
     * Is this user currently logged in?
     *
     * @return bool
     */
    protected function get_is_logged_in_attribute(): bool {
        return $this->is_logged_in();
    }

    /**
     * The full name of the user.
     *
     * @return string
     */
    protected function get_fullname_attribute(): string {
        return fullname($this->to_record());
    }

    /**
     * Get the plain DB record of this user.
     *
     * @return object
     * @deprecated since Totara 15.0
     */
    public function get_record(): object {
        debugging('\core\entity\user::get_record() is deprecated, please use to_record() instead.', DEBUG_DEVELOPER);
        return $this->to_record();
    }

    /**
     * User enrolments.
     *
     * @return has_many
     */
    public function user_enrolments(): has_many {
        return $this->has_many(user_enrolment::class, 'userid');
    }

}
