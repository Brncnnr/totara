<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\resolver\type\user as core_user;

class user extends core_user {

    /**
     * Allows mobile overrides of user fields.
     *
     * @param string $field
     * @param \stdClass $user A user record from the database
     * @param array $args
     * @param execution_context $ec
     * @return mixed|string|null
     * @throws \coding_exception If the requested field does not exist, or the current user cannot see the given user.
     */
    public static function resolve(string $field, $user, array $args, execution_context $ec) {
        global $CFG, $USER;

        if ($user instanceof \core\entity\user) {
            $user = $user->get_record();
        }

        switch ($field) {
            case 'profileimageurlsmall':
            case 'profileimageurl':
                // Mobile override to return null instead of the default image url.

                // First check if login information is correct.
                if ((!empty($CFG->forcelogin) && !isloggedin()) ||
                    (!empty($CFG->forceloginforprofileimage) && (!isloggedin() || isguestuser()))) {
                    return null;
                }

                // Then double check the user isn't deleted.
                if (!empty($user->deleted) || empty($user->email) || strpos($user->email, '@') === false) {
                    return null;
                }

                // Finally check if the user actually has a picture and isn't using gravatar.
                if (empty($user->picture) && empty($CFG->enablegravatar)) {
                    return null;
                } else {
                    $value = parent::resolve($field, $user, $args, $ec);
                    $value = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $value);
                }

                break;
            case 'url':
            case 'description':
                // Mobile override to return URLs via mobile pluginfile.php landing page.
                if (!$value = parent::resolve($field, $user, $args, $ec)) {
                    return null; // We haven't gotten a string back, don't do replacements.
                }

                $value = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $value);
                break;
            default:
                // We aren't overriding this field for mobile, so just use the core_user resolver.
                $value = parent::resolve($field, $user, $args, $ec);
        }

        return $value;
    }
}
