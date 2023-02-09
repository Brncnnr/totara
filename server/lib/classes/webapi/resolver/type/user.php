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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\type;

use core\date_format;
use core\format;
use core_user\profile\card_display;
use core_user\profile\custom_fields;
use core_user\profile\user_field_resolver;
use core\webapi\execution_context;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use totara_job\job_assignment as job_assignment_object;

global $CFG;
require_once("$CFG->dirroot/totara/job/lib.php");

class user extends \core\webapi\type_resolver {
    /**
     * Resolves the user fields.
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
            $user = $user->to_record();
        }

        if ($field === 'password' or $field === 'secret') {
            // Extra safety - these must never ever be exposed.
            return null;
        }

        if (!($user instanceof \stdClass)) {
            throw new \coding_exception(__METHOD__ . ' must be given a user record from the database', gettype($user));
        }

        // Giving us more spaces for checking the custom compute fields.
        $custom_computed_fields = ['card_display'];

        if (!user_field_resolver::is_valid_field($field) && !in_array($field, $custom_computed_fields)) {
            throw new \coding_exception("Unknown user field");
        }

        $course_id = null;

        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
            $context_course = $context->get_course_context(false);

            if ($context_course && SITEID != $context_course->instanceid) {
                $course_id = $context_course->instanceid;
            }
        }

        if ('custom_fields' === $field) {
            return custom_fields::create($user);
        }

        $field_resolver = user_field_resolver::from_record($user, $course_id);

        if ('card_display' === $field) {
            $field_resolver->load_custom_fields();
            return card_display::create($field_resolver);
        }

        $value = null;

        // Handling several formatting fields.
        switch ($field) {
            case 'firstaccess':
            case 'lastaccess':
                $time_stamp = $field_resolver->get_field_value($field);
                if (empty($time_stamp)) {
                    break;
                }

                $format = date_format::FORMAT_TIMESTAMP;
                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                $context_user = \context_user::instance($user->id);
                $value = (new date_field_formatter($format, $context_user))->format($time_stamp);
                break;

            case 'description':
                $description = $field_resolver->get_field_value('description');
                if (empty($description)) {
                    break;
                }

                $format = format::FORMAT_HTML;
                if (isset($args['format'])) {
                    $format = $args['format'];
                }
                $context_user = \context_user::instance($user->id);

                $formatter = new text_field_formatter($format, $context_user);
                $formatter->set_pluginfile_url_options($context_user, 'user', 'profile');

                // Don't use the detail description and format, that has already been munged for external services :(
                if ($format === format::FORMAT_RAW) {
                    $capabilities = ['moodle/user:update'];
                    $capabilities[] = ($USER->id == $user->id) ? 'moodle/user:editownprofile' : 'moodle/user:editprofile';
                    if (!has_any_capability($capabilities, $context_user)) {
                        $value = null;
                        break;
                    }
                }

                $value = $formatter->format($description);
                break;

            case 'fullname':
                // This is a temporary fix for the encoding problem for a user's
                // fullname. This section needs to be replaced by the solution
                // from TL-30744.
                $value = $field_resolver->get_field_value('fullname');
                if (empty($value)) {
                    break;
                }

                $format = $args['format'] ?? format::FORMAT_HTML;
                if ($format === format::FORMAT_PLAIN) {
                    // Note the get_field_value() call above indirectly calls
                    // clean_string() as well. Which means the fullname might
                    // have escaped HTML stuff. So need to remove all that when
                    // a plain format is needed.
                    $value = html_to_text($value, 0, false);
                }

                break;

            case 'job_assignments':
                $value = [];
                if (totara_job_can_view_job_assignments($user)) {
                    $value = job_assignment_object::get_all($user->id);
                }
                break;

            default:
                if (!user_field_resolver::is_valid_field($field)) {
                    throw new \coding_exception('Unknown user field', $field);
                }

                $value = $field_resolver->get_field_value($field);
        }

        if (null === $value && in_array($field, ['id', 'fullname'])) {
            // You got here because you did not check permissions because using this type, and now you can't
            // view this users information. The fields are required in GraphQL, but because we're kind you're
            // getting a coding_exception rather than a cryptic GraphQL exception.
            throw new \coding_exception('You did not check you can view a user before resolving them.', $user->id);
        }

        // Update the current user's data record, if the field does not existing in the record.
        // So that in the next run of resolving, we do not have to worry about the missing properties
        // nor have to re-fetching database to do so.
        if (!$field_resolver->field_exist_in_user_instance($field) && user_field_resolver::is_db_field($field)) {
            $loaded_record = $field_resolver->get_target_user_record();

            foreach ($loaded_record as $missing_field => $missing_value) {
                if (!property_exists($user, $missing_field)) {
                    $user->{$missing_field} = $missing_value;
                }
            }
        }

        return $value;
    }
}