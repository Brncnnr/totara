<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core_user
 */

namespace core_user\formatter;

use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

class user_formatter extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'auth' => null,
            'confirmed' => null,
            'policyagreed' => null,
            'deleted' => null,
            'suspended' => null,
            'username' => null,
            'idnumber' => null,
            'firstname' => string_field_formatter::class,
            'lastname' => string_field_formatter::class,
            'lastnamephonetic' => string_field_formatter::class,
            'firstnamephonetic' => string_field_formatter::class,
            'middlename' => null,
            'alternatename' => null,
            'email' => null,
            'emailstop' => null,
            'skype' => string_field_formatter::class,
            'phone1' => string_field_formatter::class,
            'phone2' => string_field_formatter::class,
            'institution' => string_field_formatter::class,
            'department' => string_field_formatter::class,
            'address' => string_field_formatter::class,
            'city' => string_field_formatter::class,
            'country' => null,
            'lang' => null,
            'calendartype' => null,
            'theme' => null,
            'timezone' => null,
            'firstaccess' => date_field_formatter::class,
            'lastaccess' => date_field_formatter::class,
            'lastlogin' => date_field_formatter::class,
            'currentlogin' => date_field_formatter::class,
            'lastip' => null,
            'picture' => string_field_formatter::class,
            'url' => string_field_formatter::class,
            'description' => function ($value, text_field_formatter $formatter) {
                $component = 'user';
                $filearea = 'profile';
                $itemid = $this->object->id;

                return $formatter
                    ->set_pluginfile_url_options($this->context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'descriptionformat' => null,
            'mailformat' => null,
            'maildigest' => null,
            'maildisplay' => null,
            'autosubscribe' => null,
            'trackforums' => null,
            'timecreated' => date_field_formatter::class,
            'timemodified' => date_field_formatter::class,
            'trustbitmask' => null,
            'imagealt' => string_field_formatter::class,
            'totarasync' => null,
            'fullname' => string_field_formatter::class,
            'interests' => string_field_formatter::class,
            'profileimagealt' => string_field_formatter::class,
            'profileimageurl' => null,
            'profileimageurlsmall' => null,
        ];
    }
}
