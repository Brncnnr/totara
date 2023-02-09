<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External user API
 *
 * @package   core_user
 * @copyright 2009 Moodle Pty Ltd (http://moodle.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates a user
 *
 * @throws moodle_exception
 * @param stdClass $user user to create
 * @param bool $updatepassword if true, authentication plugin will update password.
 * @param bool $triggerevent set false if user_created event should not be triggred.
 *             This will not affect user_password_updated event triggering.
 * @return int id of the newly created user
 */
function user_create_user($user, $updatepassword = true, $triggerevent = true) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/cohort/lib.php');

    // Set the timecreate field to the current time.
    if (!is_object($user)) {
        $user = (object) $user;
    }

    // Check username.
    if (trim($user->username) === '') {
        throw new moodle_exception('invalidusernameblank');
    }

    if ($user->username !== core_text::strtolower($user->username)) {
        throw new moodle_exception('usernamelowercase');
    }

    if ($user->username !== core_user::clean_field($user->username, 'username')) {
        throw new moodle_exception('invalidusername');
    }

    // Save the password in a temp value for later.
    if ($updatepassword && isset($user->password)) {

        // Check password toward the password policy.
        if (!check_password_policy($user->password, $errmsg)) {
            throw new moodle_exception($errmsg);
        }

        $userpassword = $user->password;
        unset($user->password);
    }

    // Apply default values for user preferences that are stored in users table.
    if (!isset($user->calendartype)) {
        $user->calendartype = core_user::get_property_default('calendartype');
    }
    if (!isset($user->maildisplay)) {
        $user->maildisplay = core_user::get_property_default('maildisplay');
    }
    if (!isset($user->mailformat)) {
        $user->mailformat = core_user::get_property_default('mailformat');
    }
    if (!isset($user->maildigest)) {
        $user->maildigest = core_user::get_property_default('maildigest');
    }
    if (!isset($user->autosubscribe)) {
        $user->autosubscribe = core_user::get_property_default('autosubscribe');
    }
    if (!isset($user->trackforums)) {
        $user->trackforums = core_user::get_property_default('trackforums');
    }
    if (!isset($user->lang)) {
        $user->lang = core_user::get_property_default('lang');
    }

    foreach (core_user::REMOVED_FIELDS as $fieldname => $unusedname) {
        if (!property_exists($user, $fieldname)) {
            continue;
        }
        debugging("User field '$fieldname' is not avaialble any more, use custom user profile field instead", DEBUG_DEVELOPER);
        unset($user->$fieldname);
    }

    $user->timecreated = time();
    $user->timemodified = $user->timecreated;

    // Validate user data object.
    $uservalidation = core_user::validate($user);
    if ($uservalidation !== true) {
        foreach ($uservalidation as $field => $message) {
            debugging("The property '$field' has invalid data and has been cleaned.", DEBUG_DEVELOPER);
            $user->$field = core_user::clean_field($user->$field, $field);
        }
    }

    // Insert the user into the database.
    $newuserid = $DB->insert_record('user', $user);

    // Create USER context for this user.
    $usercontext = context_user::instance($newuserid);

    // Get full database user row.
    $newuser = $DB->get_record('user', array('id' => $newuserid));

    // Update user password if necessary.
    if (isset($userpassword)) {
        $authplugin = get_auth_plugin($newuser->auth);
        $authplugin->user_update_password($newuser, $userpassword);
        $newuser = $DB->get_record('user', array('id' => $newuserid));
    }

    // Trigger event If required.
    if ($triggerevent) {
        $event = \core\event\user_created::create_from_userid($newuserid);
        $event->add_record_snapshot('user', $newuser);
        $event->trigger();
    }

    // Totara: add tenant cohort membership for all tenant users.
    if ($newuser->tenantid) {
        $tenant = $DB->get_record('tenant', ['id' => $user->tenantid], '*', MUST_EXIST);
        cohort_add_member($tenant->cohortid, $newuserid);
    }

    return $newuserid;
}

/**
 * Update a user with a user object (will compare against the ID)
 *
 * @throws moodle_exception
 * @param stdClass $user the user to update
 * @param bool $updatepassword if true, authentication plugin will update password.
 * @param bool $triggerevent set false if user_updated event should not be triggred.
 *             This will not affect user_password_updated event triggering.
 */
function user_update_user($user, $updatepassword = true, $triggerevent = true) {
    global $DB;

    if (!is_object($user)) {
        $user = (object) $user;
    }

    foreach (core_user::REMOVED_FIELDS as $fieldname => $unusedname) {
        if (!property_exists($user, $fieldname)) {
            continue;
        }
        debugging("User field '$fieldname' is not avaialble any more, use custom user profile field instead", DEBUG_DEVELOPER);
        unset($user->$fieldname);
    }

    // Totara: prevent tenantid changes.
    $olduser = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
    if (property_exists($user, 'tenantid')) {
        if ($olduser->tenantid != $user->tenantid) {
            throw new coding_exception('tenantid cannot be changed via user_update_user()');
        }
    }

    // Check username.
    if (isset($user->username) and $user->username !== $olduser->username) { // Totara: previous username IS ok!
        if ($user->username !== core_text::strtolower($user->username)) {
            throw new moodle_exception('usernamelowercase');
        } else {
            if ($user->username !== core_user::clean_field($user->username, 'username')) {
                throw new moodle_exception('invalidusername');
            }
        }
    }

    // Unset password here, for updating later, if password update is required.
    if ($updatepassword && isset($user->password)) {

        // Check password toward the password policy.
        if (!check_password_policy($user->password, $errmsg)) {
            throw new moodle_exception($errmsg);
        }

        $passwd = $user->password;
        unset($user->password);
    }

    // Make sure calendartype, if set, is valid.
    if (empty($user->calendartype)) {
        // Unset this variable, must be an empty string, which we do not want to update the calendartype to.
        unset($user->calendartype);
    }

    $user->timemodified = time();

    // Validate user data object.
    $uservalidation = core_user::validate($user);
    if ($uservalidation !== true) {
        foreach ($uservalidation as $field => $message) {
            debugging("The property '$field' has invalid data and has been cleaned.", DEBUG_DEVELOPER);
            $user->$field = core_user::clean_field($user->$field, $field);
        }
    }

    $DB->update_record('user', $user);

    if ($updatepassword) {
        // Get full user record.
        $updateduser = $DB->get_record('user', array('id' => $user->id));

        // If password was set, then update its hash.
        if (isset($passwd)) {
            $authplugin = get_auth_plugin($updateduser->auth);
            if ($authplugin->can_change_password()) {
                $authplugin->user_update_password($updateduser, $passwd);
            }
        }
    }
    // Trigger event if required.
    if ($triggerevent) {
        \core\event\user_updated::create_from_userid($user->id)->trigger();
    }
}

/**
 * Marks user deleted in internal user database and notifies the auth plugin.
 * Also unenrols user from all roles and does other cleanup.
 *
 * @todo Decide if this transaction is really needed (look for internal TODO:)
 * @param object $user Userobject before delete    (without system magic quotes)
 * @return boolean success
 */
function user_delete_user($user) {
    return delete_user($user);
}

/**
 * Suspend user account.
 *
 * @since Totara 13
 *
 * @param int $userid
 * @return bool
 */
function user_suspend_user(int $userid) {
    global $DB;

    $user = $DB->get_record('user', ['id' => $userid]);
    if (!$user or isguestuser($user)) {
        return false;
    }
    if ($user->deleted != 0) {
        return false;
    }
    if ($user->suspended == 1) {
        // Already suspended, nothing to do.
        return true;
    }

    // Do not use user_update_user() here!
    $user->suspended = '1';
    $user->timemodified = (string)time();
    $DB->set_fields('user', ['suspended' => $user->suspended, 'timemodified' => $user->timemodified], ['id' => $user->id]);

    // Force logout.
    \core\session\manager::kill_user_sessions($user->id);

    $event = \core\event\user_updated::create_from_userid($user->id);
    $event->add_record_snapshot('user', $user);
    $event->trigger();

    // DO NOT ABUSE THIS EVENT!
    // No data should be deleted when user gets suspended, use userdata purging instead of event observers.
    // The removal of bookings of suspended users in Seminar is a data loss bug.
    $event = \totara_core\event\user_suspended::create_from_user($user);
    $event->add_record_snapshot('user', $user);
    $event->trigger();

    return true;
}

/**
 * Unsuspend user account.
 *
 * @since Totara 13
 *
 * @param int $userid
 * @return bool
 */
function user_unsuspend_user(int $userid) {
    global $DB, $CFG;
    require_once("$CFG->dirroot/lib/authlib.php");

    $user = $DB->get_record('user', ['id' => $userid]);
    if (!$user or isguestuser($user)) {
        return false;
    }
    if ($user->deleted != 0) {
        return false;
    }
    if ($user->suspended == 0) {
        // Already suspended, nothing to do.
        return true;
    }

    // Do not use user_update_user() here!
    $user->suspended = '0';
    $user->timemodified = (string)time();
    $DB->set_fields('user', ['suspended' => $user->suspended, 'timemodified' => $user->timemodified], ['id' => $user->id]);

    $event = \core\event\user_updated::create_from_userid($user->id);
    $event->add_record_snapshot('user', $user);
    $event->trigger();

    // Make sure user is not locked out.
    login_unlock_account($user);

    return true;
}

/**
 * Change user password.
 *
 * @since Totara 13
 * @param int $userid
 * @param string $newpassword
 * @param array $options
 * @return bool
 */
function user_change_password(int $userid, string $newpassword, array $options = []) {
    global $DB, $CFG;
    require_once("$CFG->dirroot/lib/authlib.php");

    $defaultoptions = [
        'forcepasswordchange' => false,
        'signoutofotherservices' => false,
    ];
    $options = array_merge($defaultoptions, $options);

    $user = $DB->get_record('user', ['id' => $userid]);
    if (!$user or isguestuser($user)) {
        return false;
    }
    if ($user->deleted != 0) {
        return false;
    }

    if (!exists_auth_plugin($user->auth)) {
        return false;
    }
    $authplugin = get_auth_plugin($user->auth);
    if (!$authplugin->can_change_password()) {
        return false;
    }
    if ($authplugin->change_password_url()) {
        // Cannot change password if external page is used.
        return false;
    }

    if (!$authplugin->user_update_password($user, $newpassword)) {
        return false;
    }

    // Prevent cron from generating initial password if not done yet.
    unset_user_preference('create_password', $user);

    if ($options['forcepasswordchange']) {
        set_user_preference('auth_forcepasswordchange', 1, $user);
    }

    if (!empty($CFG->passwordchangelogout)) {
        // We can use SID of other user safely here because they are unique,
        // the problem here is we do not want to logout admin here when changing own password.
        \core\session\manager::kill_user_sessions($user->id, session_id());
    }

    if ($options['signoutofotherservices']) {
        // Delete external WS tokens, Moodle mobile is not supported in Totara.
        webservice::delete_user_ws_tokens($user->id);
    }

    // Always force users to login again with new password after closing browser or normal session timeout.
    \totara_core\persistent_login::kill_user($user->id);

    // Make sure user is not locked out.
    login_unlock_account($user);

    // Add to list of used passwords so that we can prevent reuse.
    user_add_password_history($user->id, $newpassword);

    return true;
}

/**
 * Get users by id
 *
 * @param array $userids id of users to retrieve
 * @return array
 */
function user_get_users_by_id($userids) {
    global $DB;
    return $DB->get_records_list('user', 'id', $userids);
}

/**
 * Returns the list of default 'displayable' fields
 *
 * Contains database field names but also names used to generate information, such as enrolledcourses
 *
 * @return array of user fields
 */
function user_get_default_fields() {
    return array( 'id', 'username', 'fullname', 'firstname', 'lastname', 'email',
        'address', 'phone1', 'phone2', 'skype', 'department',
        'institution', 'interests', 'firstaccess', 'lastaccess', 'auth', 'confirmed',
        'idnumber', 'lang', 'theme', 'timezone', 'mailformat', 'description', 'descriptionformat',
        'city', 'url', 'country', 'profileimageurlsmall', 'profileimageurl', 'imagealt', 'customfields',
        'groups', 'roles', 'preferences', 'enrolledcourses', 'suspended'
    );
}

/**
 *
 * Give user record from mdl_user, build an array contains all user details.
 *
 * Warning: description file urls are 'webservice/pluginfile.php' is use.
 *          it can be changed with $CFG->moodlewstextformatlinkstoimagesfile
 *
 * @throws moodle_exception
 * @param stdClass $user user record from mdl_user
 * @param stdClass $course moodle course
 * @param array $userfields required fields
 * @return array|null
 */
function user_get_user_details($user, $course = null, array $userfields = array()) {
    $controller = \core_user\access_controller::for($user, $course);
    if (!$controller->can_view_profile()) {
        return null;
    }

    $defaultfields = user_get_default_fields();
    if (!empty($userfields)) {
        foreach ($userfields as $thefield) {
            if (!in_array($thefield, $defaultfields)) {
                throw new moodle_exception('invaliduserfield', 'error', '', $thefield);
            }
        }
    } else {
        $userfields = $defaultfields;
    }

    // Make sure id and fullname are included.
    foreach (['id', 'fullname'] as $field) {
        if (!in_array($field, $userfields)) {
            $userfields[] = $field;
        }
    }

    // This will be the array that we return.
    $return = array();

    // TOTARA: Rewritten so that checks are consistent and functional.
    $resolver_isset = function ($field, $user) use (&$return) {
        if (isset($user->{$field})) {
            $return[$field] = $user->{$field};
        }
    };
    $resolver_notempty = function ($field, $user) use (&$return) {
        if (!empty($user->{$field})) {
            $return[$field] = $user->{$field};
        }
    };

    $resolvers = [
        'id' => $resolver_isset,
        'imagealt' => $resolver_isset,
        'firstname' => $resolver_isset,
        'lastname' => $resolver_isset,
        'username' => $resolver_isset,
        'email' => $resolver_isset,
        'institution' => $resolver_notempty,
        'idnumber' => $resolver_isset,
        'skype' => $resolver_notempty,
        'city' => $resolver_notempty,
        'country' => $resolver_notempty,
        'auth' => $resolver_isset,
        'confirmed' => $resolver_isset,
        'lang' => $resolver_isset,
        'theme' => $resolver_isset,
        'timezone' => $resolver_isset,
        'mailformat' => $resolver_isset,
        'department' => $resolver_isset,
        'phone1' => $resolver_notempty,
        'phone2' => $resolver_notempty,
        'address' => $resolver_notempty,

        // Fullname needs to be generated by the fullname function.
        'fullname' => function ($field, $user) use (&$return) {
            $return[$field] = fullname($user);
        },

        // The following do something separate for each field.
        'description' => function ($field, $user) use (&$return) {
            global $CFG;
            require_once($CFG->dirroot . "/lib/filelib.php");
            if (!isset($user->description)) {
                return;
            }
            // This is terrible practice, don't ever copy this code!
            require_once($CFG->libdir . '/externallib.php');
            // Always return the descriptionformat if description is requested.
            $usercontext = context_user::instance($user->id);
            list($return['description'], $return['descriptionformat']) =
                external_format_text($user->description, $user->descriptionformat, $usercontext->id, 'user', 'profile', null);
        },

        'url' => function ($field, $user) use (&$return) {
            $url = $user->url;
            if (strpos($user->url, '://') === false) {
                $url = 'http://'. $url;
            }
            $user->url = clean_param($url, PARAM_URL);
            $return['url'] = $user->url;
        },

        'suspended' => function ($field, $user) use (&$return) {
            $return['suspended'] = (bool)$user->suspended;
        },

        'firstaccess' => function ($field, $user) use (&$return) {
            $return['firstaccess'] = ($user->firstaccess) ? $user->firstaccess : 0;
        },

        'lastaccess' => function ($field, $user) use (&$return) {
            $return['lastaccess'] = ($user->lastaccess) ? $user->lastaccess : 0;
        },

        'profileimageurl' => function ($field, $user) use (&$return) {
            global $PAGE;
            $return['profileimageurl'] = (new user_picture($user, 1))->get_url($PAGE)->out(false);
        },

        'profileimageurlsmall' => function ($field, $user) use (&$return) {
            global $PAGE;
            $return['profileimageurlsmall'] = (new user_picture($user, 0))->get_url($PAGE)->out(false);
        },

        'interests' => function ($field, $user) use (&$return) {
            $interests = core_tag_tag::get_item_tags_array(
                'core',
                'user',
                $user->id,
                core_tag_tag::BOTH_STANDARD_AND_NOT,
                0,
                false
            );
            if ($interests) {
                $return['interests'] = join(', ', $interests);
            }
        },

        'enrolledcourses' => function ($field, $user) use (&$return) {
            $enrolledcourses = array();
            $mycourses = enrol_get_users_courses($user->id, true);
            if ($mycourses) {
                foreach ($mycourses as $mycourse) {
                    if ($mycourse->category) {
                        $coursecontext = context_course::instance($mycourse->id);
                        $enrolledcourses[] = [
                            'id' => $mycourse->id,
                            'fullname' => format_string($mycourse->fullname, true, array('context' => $coursecontext)),
                            'shortname' => format_string($mycourse->shortname, true, array('context' => $coursecontext))
                        ];
                    }
                }
                $return['enrolledcourses'] = $enrolledcourses;
            }
        },

        'preferences' => function ($field, $user) use (&$return) {
            $preferences = array();
            $userpreferences = get_user_preferences();
            foreach ($userpreferences as $prefname => $prefvalue) {
                $preferences[] = array('name' => $prefname, 'value' => $prefvalue);
            }
            $return['preferences'] = $preferences;
        },

        'customfields' => function ($field, $user) use (&$return) {
            global $CFG, $DB;
            require_once($CFG->dirroot . "/user/profile/lib.php");
            $fields = $DB->get_recordset_sql("SELECT f.*
                                            FROM {user_info_field} f
                                            JOIN {user_info_category} c
                                                 ON f.categoryid=c.id
                                        ORDER BY c.sortorder ASC, f.sortorder ASC");
            $return['customfields'] = array();
            foreach ($fields as $field) {
                require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                $newfield = 'profile_field_'.$field->datatype;
                $formfield = new $newfield($field->id, $user->id);
                if ($formfield->is_visible() and !$formfield->is_empty()) {
                    // TODO: Part of MDL-50728, this conditional coding must be moved to
                    // proper profile fields API so they are self-contained.
                    // We only use display_data in fields that require text formatting.
                    if ($field->datatype == 'text' or $field->datatype == 'textarea') {
                        $fieldvalue = $formfield->display_data();
                    } else {
                        // Cases: datetime, checkbox and menu.
                        $fieldvalue = $formfield->data;
                    }

                    $return['customfields'][] = [
                        'name' => $formfield->field->name,
                        'value' => $fieldvalue,
                        'type' => $field->datatype,
                        'shortname' => $formfield->field->shortname
                    ];
                }
            }
            $fields->close();
            // Unset customfields if it's empty.
            if (empty($return['customfields'])) {
                unset($return['customfields']);
            }
        }
    ];
    foreach ($resolvers as $field => $callback) {
        if (in_array($field, $userfields) && $controller->can_view_field($field)) {
            $callback($field, $user);
        }
    }

    // TOTARA: The following are only available if we are looking at a course.
    if (in_array('roles', $userfields) && !empty($course)) {
        // Not a big secret.
        $context = context_course::instance($course->id);
        $roles = get_user_roles($context, $user->id, false);
        $return['roles'] = array();
        foreach ($roles as $role) {
            $return['roles'][] = array(
                'roleid'       => $role->roleid,
                'name'         => $role->name,
                'shortname'    => $role->shortname,
                'sortorder'    => $role->sortorder
            );
        }
    }
    if (in_array('groups', $userfields) && !empty($course)) {
        // If groups are in use and enforced throughout the course, then make sure we can meet in at least one course level group.
        $context = context_course::instance($course->id);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            $usergroups = groups_get_all_groups(
                $course->id,
                $user->id,
                $course->defaultgroupingid,
                'g.id, g.name,g.description,g.descriptionformat'
            );
            $return['groups'] = array();
            foreach ($usergroups as $group) {
                list($group->description, $group->descriptionformat) =
                    external_format_text(
                        $group->description,
                        $group->descriptionformat,
                        $context->id,
                        'group',
                        'description',
                        $group->id
                    );
                $return['groups'][] = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'descriptionformat' => $group->descriptionformat
                ];
            }
        }
    }

    // Clean lang and auth fields for external functions (it may content uninstalled themes or language packs).
    if (isset($return['lang'])) {
        $return['lang'] = clean_param($return['lang'], PARAM_LANG);
    }
    if (isset($return['theme'])) {
        $return['theme'] = clean_param($return['theme'], PARAM_THEME);
    }

    return $return;
}

/**
 * Tries to obtain user details, either recurring directly to the user's system profile
 * or through one of the user's course enrollments (course profile).
 *
 * @param stdClass $user The user.
 * @return array if unsuccessful or the allowed user details.
 */
function user_get_user_details_courses($user) {
    // TOTARA: The code from Moodle here would try this first at the system, and then by courses one by one.
    // However there was a bug in the code whereit it would iterate all courses, but not return, break or merge.
    // It was basically just wasting cycles.
    // We refactored user_get_user_details and it already does what this function is trying to do.
    return user_get_user_details($user);
}

/**
 * Check if $USER have the necessary capabilities to obtain user details.
 *
 * @param stdClass $user
 * @param stdClass $course if null then only consider system profile otherwise also consider the course's profile.
 * @return bool true if $USER can view user details.
 */
function can_view_user_details_cap($user, $course = null) {
    // Totara: use standard API for profile accesss.
    return user_can_view_profile($user, $course);
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function user_page_type_list($pagetype, $parentcontext, $currentcontext) {
    return array('user-profile' => get_string('page-user-profile', 'pagetype'));
}

/**
 * Count the number of failed login attempts for the given user, since last successful login.
 *
 * @param int|stdclass $user user id or object.
 * @param bool $reset Resets failed login count, if set to true.
 *
 * @return int number of failed login attempts since the last successful login.
 */
function user_count_login_failures($user, $reset = true) {
    global $DB;

    if (!is_object($user)) {
        $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
    }
    if ($user->deleted) {
        // Deleted user, nothing to do.
        return 0;
    }
    $count = get_user_preferences('login_failed_count_since_success', 0, $user);
    if ($reset) {
        set_user_preference('login_failed_count_since_success', 0, $user);
    }
    return $count;
}

/**
 * Converts a string into a flat array of menu items, where each menu items is a
 * stdClass with fields type, url, title, pix, and imgsrc.
 *
 * @param string $text the menu items definition
 * @param moodle_page $page the current page
 * @return array
 */
function user_convert_text_to_menu_items($text, $page) {
    global $OUTPUT, $CFG;

    $lines = explode("\n", $text);
    $items = array();
    $lastchild = null;
    $lastdepth = null;
    $lastsort = 0;
    $children = array();
    foreach ($lines as $line) {
        $line = trim($line);
        $bits = explode('|', $line, 3);
        $itemtype = 'link';
        if (preg_match("/^#+$/", $line)) {
            $itemtype = 'divider';
        } else if (!array_key_exists(0, $bits) or empty($bits[0])) {
            // Every item must have a name to be valid.
            continue;
        } else {
            $bits[0] = ltrim($bits[0], '-');
        }

        // Create the child.
        $child = new stdClass();
        $child->itemtype = $itemtype;
        if ($itemtype === 'divider') {
            // Add the divider to the list of children and skip link
            // processing.
            $children[] = $child;
            continue;
        }

        // Name processing.
        $namebits = explode(',', $bits[0], 2);
        if (count($namebits) == 2) {
            // Check the validity of the identifier part of the string.
            if (clean_param($namebits[0], PARAM_STRINGID) !== '') {
                // Treat this as a language string.
                $child->title = get_string($namebits[0], $namebits[1]);
                $child->titleidentifier = implode(',', $namebits);
            }
        }
        if (empty($child->title)) {
            // Use it as is, don't even clean it.
            $child->title = $bits[0];
            $child->titleidentifier = str_replace(" ", "-", $bits[0]);
        }

        // URL processing.
        if (!array_key_exists(1, $bits) or empty($bits[1])) {
            // Set the url to null, and set the itemtype to invalid.
            $bits[1] = null;
            $child->itemtype = "invalid";
        } else {
            // Nasty hack to replace the grades with the direct url.
            if (strpos($bits[1], '/grade/report/mygrades.php') !== false) {
                $bits[1] = user_mygrades_url();
            }

            // Make sure the url is a moodle url.
            $bits[1] = new moodle_url(trim($bits[1]));
        }
        $child->url = $bits[1];

        // PIX processing.
        $pixpath = "t/edit";
        if (!array_key_exists(2, $bits) or empty($bits[2])) {
            // Use the default.
            $child->pix = $pixpath;
        } else {
            // Check for the specified image existing.
            $pixpath = "t/" . $bits[2];
            if ($page->theme->resolve_image_location($pixpath, 'moodle', true)) {
                // Use the image.
                $child->pix = $pixpath;
            } else {
                // Treat it like a URL.
                $child->pix = null;
                $child->imgsrc = $bits[2];
            }
        }

        // Add this child to the list of children.
        $children[] = $child;
    }
    return $children;
}

/**
 * Get a list of essential user navigation items.
 *
 * @param stdclass $user user object.
 * @param moodle_page $page page object.
 * @param array $options associative array.
 *     options are:
 *     - avatarsize=35 (size of avatar image)
 * @return stdClass $returnobj navigation information object, where:
 *
 *      $returnobj->navitems    array    array of links where each link is a
 *                                       stdClass with fields url, title, and
 *                                       pix
 *      $returnobj->metadata    array    array of useful user metadata to be
 *                                       used when constructing navigation;
 *                                       fields include:
 *
 *          ROLE FIELDS
 *          asotherrole    bool    whether viewing as another role
 *          rolename       string  name of the role
 *
 *          USER FIELDS
 *          These fields are for the currently-logged in user, or for
 *          the user that the real user is currently logged in as.
 *
 *          userid         int        the id of the user in question
 *          userfullname   string     the user's full name
 *          userprofileurl moodle_url the url of the user's profile
 *          useravatar     string     a HTML fragment - the rendered
 *                                    user_picture for this user
 *          userloginfail  string     an error string denoting the number
 *                                    of login failures since last login
 *
 *          "REAL USER" FIELDS
 *          These fields are for when asotheruser is true, and
 *          correspond to the underlying "real user".
 *
 *          asotheruser        bool    whether viewing as another user
 *          realuserid         int        the id of the user in question
 *          realuserfullname   string     the user's full name
 *          realuserprofileurl moodle_url the url of the user's profile
 *          realuseravatar     string     a HTML fragment - the rendered
 *                                        user_picture for this user
 */
function user_get_user_navigation_info($user, $page, $options = array()) {
    global $OUTPUT, $DB, $SESSION, $CFG;

    $returnobject = new stdClass();
    $returnobject->navitems = array();
    $returnobject->metadata = array();

    $course = $page->course;

    // Query the environment.
    $context = context_course::instance($course->id);

    // Get basic user metadata.
    $returnobject->metadata['userid'] = $user->id;
    $returnobject->metadata['userfullname'] = fullname($user);
    $returnobject->metadata['userprofileurl'] = new moodle_url('/user/profile.php', array(
        'id' => $user->id
    ));

    $avataroptions = array('link' => false, 'visibletoscreenreaders' => false);
    if (!empty($options['avatarsize'])) {
        $avataroptions['size'] = $options['avatarsize'];
    }
    $returnobject->metadata['useravatar'] = $OUTPUT->user_picture (
        $user, $avataroptions
    );
    // Build a list of items for a regular user.

    // Did the user just log in?
    if (isset($SESSION->justloggedin)) {
        // Don't unset this flag as login_info still needs it.
        if (!empty($CFG->displayloginfailures)) {
            // Don't reset the count either, as login_info() still needs it too.
            if ($count = user_count_login_failures($user, false)) {

                // Get login failures string.
                $a = new stdClass();
                $a->attempts = $count;
                $returnobject->metadata['userloginfail'] =
                    get_string('failedloginattempts', '', $a);

            }
        }
    }

    // TOTARA: Removed the links to Moodle dashboards.
    // Links: Dashboard.
    // $myhome = new stdClass();
    // $myhome->itemtype = 'link';
    // $myhome->url = new moodle_url('/my/');
    // $myhome->title = get_string('mymoodle', 'admin');
    // $myhome->titleidentifier = 'mymoodle,admin';
    // $myhome->pix = "i/course";
    // $returnobject->navitems[] = $myhome;

    // Links: My Profile.
    $myprofile = new stdClass();
    $myprofile->itemtype = 'link';
    $myprofile->url = new moodle_url('/user/profile.php', array('id' => $user->id));
    $myprofile->title = get_string('profile');
    $myprofile->titleidentifier = 'profile,moodle';
    $myprofile->pix = "i/user";
    $returnobject->navitems[] = $myprofile;

    // Links: Role-return or logout link.
    $lastobj = null;
    $buildlogout = true;
    $returnobject->metadata['asotherrole'] = false;
    if (is_role_switched($course->id)) {
        if ($role = $DB->get_record('role', array('id' => $user->access['rsw'][$context->path]))) {
            // Build role-return link instead of logout link.
            $rolereturn = new stdClass();
            $rolereturn->itemtype = 'link';
            $rolereturn->url = new moodle_url('/course/switchrole.php', array(
                'id' => $course->id,
                'sesskey' => sesskey(),
                'switchrole' => 0,
                'returnurl' => $page->url->out_as_local_url(false)
            ));
            $rolereturn->pix = "a/logout";
            $rolereturn->title = get_string('switchrolereturn');
            $rolereturn->titleidentifier = 'switchrolereturn,moodle';
            $lastobj = $rolereturn;

            $returnobject->metadata['asotherrole'] = true;
            $returnobject->metadata['rolename'] = role_get_name($role, $context);

            $buildlogout = false;
        }
    }

    if ($returnobject->metadata['asotheruser'] = \core\session\manager::is_loggedinas()) {
        $realuser = \core\session\manager::get_realuser();

        // Save values for the real user, as $user will be full of data for the
        // user the user is disguised as.
        $returnobject->metadata['realuserid'] = $realuser->id;
        $returnobject->metadata['realuserfullname'] = fullname($realuser, true);
        $returnobject->metadata['realuserprofileurl'] = new moodle_url('/user/profile.php', array(
            'id' => $realuser->id
        ));
        $returnobject->metadata['realuseravatar'] = $OUTPUT->user_picture($realuser, $avataroptions);

        // Build a user-revert link.
        $userrevert = new stdClass();
        $userrevert->itemtype = 'link';
        $userrevert->url = new moodle_url('/course/loginas.php', array(
            'id' => $course->id,
            'sesskey' => sesskey()
        ));
        $userrevert->pix = "a/logout";
        $userrevert->title = get_string('logout');
        $userrevert->titleidentifier = 'logout,moodle';
        $lastobj = $userrevert;

        $buildlogout = false;
    }

    if ($buildlogout) {
        // Build a logout link.
        $logout = new stdClass();
        $logout->itemtype = 'link';
        $logout->url = new moodle_url('/login/logout.php', array('sesskey' => sesskey()));
        $logout->pix = "a/logout";
        $logout->title = get_string('logout');
        $logout->titleidentifier = 'logout,moodle';
        $lastobj = $logout;
    }

    // Before we add the last item (usually a logout link), add any
    // custom-defined items.
    $customitems = user_convert_text_to_menu_items($CFG->customusermenuitems, $page);
    foreach ($customitems as $item) {
        $returnobject->navitems[] = $item;
    }

    // Load languages
    $langs = get_string_manager()->get_list_of_translations();

    if (!empty($CFG->langmenu) && count($langs) > 1) {
        $divider = new stdClass();
        $divider->itemtype = 'divider';
        $returnobject->navitems[] = $divider;

        foreach ($langs as $lang_code => $lang_text) {
            $lang_menuoption = new stdClass();
            $lang_menuoption->itemtype = 'link';
            $lang_menuoption->url = new moodle_url($page->url, array('lang' => $lang_code) );
            $lang_menuoption->title = s($lang_text);
            $returnobject->navitems[] = $lang_menuoption;
        }
    }

    // Add the last item to the list.
    if (!is_null($lastobj)) {
        $returnobject->navitems[] = $lastobj;
    }

    return $returnobject;
}

/**
 * Add password to the list of used hashes for this user.
 *
 * This is supposed to be used from:
 *  1/ change own password form
 *  2/ password reset process
 *  3/ user signup in auth plugins if password changing supported
 *
 * @param int $userid user id
 * @param string $password plaintext password
 * @return void
 */
function user_add_password_history($userid, $password) {
    global $CFG, $DB;

    if (empty($CFG->passwordreuselimit) or $CFG->passwordreuselimit < 0) {
        return;
    }

    // Note: this is using separate code form normal password hashing because
    //       we need to have this under control in the future. Also the auth
    //       plugin might not store the passwords locally at all.

    $record = new stdClass();
    $record->userid = $userid;
    $record->hash = password_hash($password, PASSWORD_DEFAULT);
    $record->timecreated = time();
    $DB->insert_record('user_password_history', $record);

    $i = 0;
    $records = $DB->get_records('user_password_history', array('userid' => $userid), 'timecreated DESC, id DESC');
    foreach ($records as $record) {
        $i++;
        if ($i > $CFG->passwordreuselimit) {
            $DB->delete_records('user_password_history', array('id' => $record->id));
        }
    }
}

/**
 * Was this password used before on change or reset password page?
 *
 * The $CFG->passwordreuselimit setting determines
 * how many times different password needs to be used
 * before allowing previously used password again.
 *
 * @param int $userid user id
 * @param string $password plaintext password
 * @return bool true if password reused
 */
function user_is_previously_used_password($userid, $password) {
    global $CFG, $DB;

    if (empty($CFG->passwordreuselimit) or $CFG->passwordreuselimit < 0) {
        return false;
    }

    $reused = false;

    $i = 0;
    $records = $DB->get_records('user_password_history', array('userid' => $userid), 'timecreated DESC, id DESC');
    foreach ($records as $record) {
        $i++;
        if ($i > $CFG->passwordreuselimit) {
            $DB->delete_records('user_password_history', array('id' => $record->id));
            continue;
        }
        // NOTE: this is slow but we cannot compare the hashes directly any more.
        if (password_verify($password, $record->hash)) {
            $reused = true;
        }
    }

    return $reused;
}

/**
 * Remove a user device from the Moodle database (for PUSH notifications usually).
 *
 * @param string $uuid The device UUID.
 * @param string $appid The app id. If empty all the devices matching the UUID for the user will be removed.
 * @return bool true if removed, false if the device didn't exists in the database
 * @since Moodle 2.9
 */
function user_remove_user_device($uuid, $appid = "") {
    global $DB, $USER;

    $conditions = array('uuid' => $uuid, 'userid' => $USER->id);
    if (!empty($appid)) {
        $conditions['appid'] = $appid;
    }

    if (!$DB->count_records('user_devices', $conditions)) {
        return false;
    }

    $DB->delete_records('user_devices', $conditions);

    return true;
}

/**
 * Trigger user_list_viewed event.
 *
 * @param stdClass  $course course  object
 * @param stdClass  $context course context object
 * @since Moodle 2.9
 */
function user_list_view($course, $context) {

    $event = \core\event\user_list_viewed::create(array(
        'objectid' => $course->id,
        'courseid' => $course->id,
        'context' => $context,
        'other' => array(
            'courseshortname' => $course->shortname,
            'coursefullname' => $course->fullname
        )
    ));
    $event->trigger();
}

/**
 * Returns the url to use for the "Grades" link in the user navigation.
 *
 * @param int $userid The user's ID.
 * @param int $courseid The course ID if available.
 * @return mixed A URL to be directed to for "Grades".
 */
function user_mygrades_url($userid = null, $courseid = SITEID) {
    global $CFG, $USER;
    $url = null;
    if (isset($CFG->grade_mygrades_report) && $CFG->grade_mygrades_report != 'external') {
        if (isset($userid) && $USER->id != $userid) {
            // Send to the gradebook report.
            $url = new moodle_url('/grade/report/' . $CFG->grade_mygrades_report . '/index.php',
                    array('id' => $courseid, 'userid' => $userid));
        } else {
            $url = new moodle_url('/grade/report/' . $CFG->grade_mygrades_report . '/index.php');
        }
    } else if (isset($CFG->grade_mygrades_report) && $CFG->grade_mygrades_report == 'external'
            && !empty($CFG->gradereport_mygradeurl)) {
        $url = $CFG->gradereport_mygradeurl;
    } else {
        $url = $CFG->wwwroot;
    }
    return $url;
}

// Totara: user_can_view_profile() was moved to lib/moodlelib.php in order to eliminate unnecessary includes of this file.

/**
 * Totara: Add my private files to my profile page,
 * because it is not linked from anywhere else by default.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 *
 * @return bool
 */
function core_user_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG;

    if (\core_user\access_controller::for($user, $course)->can_manage_files()) {
        $url = new moodle_url('/user/files.php', array('returnurl' => $CFG->wwwroot. '/user/profile.php'));
        $title = get_string('privatefilesmanage') . '...';
        $notesnode = new core_user\output\myprofile\node('administration', 'privatefiles', $title, null, $url);
        $tree->add_node($notesnode);
    }
}

/**
 * Returns users tagged with a specified tag.
 *
 * @param core_tag_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \core_tag\output\tagindex
 */
function user_get_tagged_users($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
    global $PAGE;

    if ($ctx && $ctx != context_system::instance()->id) {
        $usercount = 0;
    } else {
        // Users can only be displayed in system context.
        $usercount = $tag->count_tagged_items('core', 'user',
                'it.deleted=:notdeleted', array('notdeleted' => 0));
    }
    $perpage = $exclusivemode ? 24 : 5;
    $content = '';
    $totalpages = ceil($usercount / $perpage);

    if ($usercount) {
        $userlist = $tag->get_tagged_items('core', 'user', $page * $perpage, $perpage,
                'it.deleted=:notdeleted', array('notdeleted' => 0));
        $renderer = $PAGE->get_renderer('core', 'user');
        $content .= $renderer->user_list($userlist, $exclusivemode);
    }

    return new core_tag\output\tagindex($tag, 'core', 'user', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
}
