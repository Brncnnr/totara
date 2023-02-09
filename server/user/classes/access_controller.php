<?php
/*
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_user
 */

namespace core_user;

use \coding_exception;
use \context_course;
use \context_helper;
use \context_user;
use core_user\hook\allow_view_profile;
use core_user\hook\allow_view_profile_field;
use \stdClass;
use \moodle_url;

/**
 * User access controller.
 *
 * A user is a carefully handled thing.
 * There are a lot of checks that go into what one user can view about another (even themselves)
 *
 * This controller helps resolve those checks consistently by providing methods that relate to giving access
 * to specific fields and information.
 *
 * The access controller is instantiated because the checks can be expensive, and as such the results are cached
 * for the life of the instance.
 * If you change anything during execution that would lead to a different outcome then you must redirect or refresh the objects.
 * But really you should redirect if you are changing permissions, roles, etc.
 *
 * It is strongly recommended that if you are working with bulk actions then you do so by iterating user by user.
 * If going field by field is unavoidable then I suggest you cache these objects within your code.
 *
 * @since Totara 13
 */
class access_controller {

    /**
     * The user id.
     * @var int
     */
    private $userid;

    /**
     * True if the user has been deleted, false otherwise.
     * @var bool
     */
    private $userdeleted;

    /**
     * The users mail display setting.
     * @var int
     */
    private $usermaildisplay;

    /**
     * True if the target user and the current user are the same person.
     * @var bool
     */
    private $iscurrentuser = false;

    /**
     * The user context, or false if the user has been deleted.
     * @var context_user|false
     */
    private $context_user = false;

    /**
     * An array of courses the user is enrolled in.
     * Don't directly access this property, call get_enrolled_courses().
     * @var stdClass[]
     */
    private $enrolled_courses;

    /**
     * The course this access controller is specifically focus on.
     * If none are provided then all courses are taken into account.
     * @var null|int
     */
    private $courseid = null;

    /**
     * Context matching $this->>courseid
     * @var null|context_course
     */
    private $context_course = null;

    /**
     * Cached course data, to be used only if courseid set.
     * @internal
     * @var null|stdClass
     */
    private $cachedcourse = null;

    /**
     * An array of already resolved checks for quick lookup.
     * @var bool[]
     */
    private $resolutioncache = [];

    /**
     * An array of hidden fields.
     * Don't access this property directly, call get_hidden_fields()
     * @var string[]
     */
    private $hiddenfields = null;

    /**
     * An array of identity fields.
     * Don't access this property directly, call get_identity_fields()
     * @var string[]
     */
    private $identifyfields = null;

    /**
     * Current user id, if it changes then we must purge caches.
     * @var int
     */
    private static $myuserid = null;

    /**
     * Cache of instantiated instances, limited to INSTANCE_CACHE_MAX_SIZE
     * Please note this cache is not used when running unit tests.
     * @var access_controller[]
     */
    private static $instancecache = [];

    /**
     * The maximum size for the instance cache.
     */
    private const INSTANCE_CACHE_MAX_SIZE = 10;

    /**
     * Gets a managed user instance for the given user record.
     *
     * It is the responsibility of the called to make sure that all of the required fields are present on the record.
     *
     * @param stdClass|access_controller $user
     * @param int|stdClass|null $courseorid
     * @return access_controller
     */
    public static function for($user, $courseorid = null): access_controller {
        global $SITE, $USER;

        if (empty($user->id) || $user->id <= 0) {
            throw new coding_exception('User access controllers can only be used for real users.');
        }

        // Use null instead of frontpage course.
        if (is_object($courseorid)) {
            if ($courseorid->id == $SITE->id) {
                $courseorid = null;
            }
        } else if (!$courseorid or $courseorid == $SITE->id) {
            $courseorid = null;
        }

        $key = (string)$user->id;
        if ($courseorid !== null) {
            $key .= '_';
            $key .= $courseorid->id ?? $courseorid;
        }

        // Make sure the current $USER did not change, if it did throw aways all caches.
        if (self::$myuserid !== null and self::$myuserid != $USER->id) {
            self::clear_instance_cache();
        }
        self::$myuserid = $USER->id;

        if (!isset(self::$instancecache[$key])) {
            if (count(self::$instancecache) >= self::INSTANCE_CACHE_MAX_SIZE) {
                // Drop the oldest key.
                reset(self::$instancecache);
                $firstkey = key(self::$instancecache);
                unset(self::$instancecache[$firstkey]);
            }
            $controller = new access_controller($user, $courseorid);
            self::$instancecache[$key] = $controller;
        }
        return self::$instancecache[$key];
    }

    /**
     * Gets a managed user instance given a user id.
     * @param int $id
     * @param int|stdClass|null $courseorid
     * @return access_controller
     */
    public static function for_user_id(int $id, $courseorid = null): access_controller {
        global $DB;
        if ($id <= 0) {
            throw new coding_exception('Userid does not belong to a real user.');
        }
        return self::for($DB->get_record('user', ['id' => $id], '*', MUST_EXIST), $courseorid);
    }

    /**
     * Gets a managed user object for the current user.
     *
     * @param int|stdClass|null $courseorid
     * @return access_controller
     */
    public static function for_current_user($courseorid = null): access_controller {
        global $USER;
        if (!isloggedin()) {
            throw new \coding_exception('There is no current user');
        }
        return self::for($USER, $courseorid);
    }

    /**
     * Resets the static instance cache when required.
     */
    public static function clear_instance_cache(): void {
        self::$myuserid = null;
        self::$instancecache = [];
    }

    /**
     * Constructor.
     *
     * @param stdClass $user
     * @param int|stdClass|null $courseorid
     */
    private function __construct(stdClass $user, $courseorid = null) {
        global $DB, $USER, $SITE;
        if (empty($user->id)) {
            throw new coding_exception('User access controllers can only be used for real users.');
        }
        $requiredfields = [
            'id',
            'deleted',
            'maildisplay',
        ];
        $toload = array_filter($requiredfields, function($field) use ($user) {
            return !(isset($user->{$field}) || property_exists($user, $field));
        });
        if (!empty($toload)) {
            $user = $DB->get_record('user', ['id' => $user->id], join(', ', $requiredfields), MUST_EXIST);
        }
        $this->userid = (int)$user->id;
        $this->userdeleted = (bool)$user->deleted;
        $this->usermaildisplay = (int)$user->maildisplay;

        $this->iscurrentuser = ($user->id == $USER->id);
        if (!$this->userdeleted) {
            $this->context_user = context_user::instance($user->id, MUST_EXIST);
        }
        if ($courseorid) {
            if (is_object($courseorid)) {
                if ($courseorid->id and $courseorid->id != $SITE->id) {
                    $this->courseid = $courseorid->id;
                    $this->cachedcourse = $courseorid;
                }
            } else {
                if ($courseorid != $SITE->id ) {
                    $this->courseid = $courseorid;
                    // Do not fetch the course if it is not needed.
                }
            }
        }
        if ($this->courseid) {
            $this->context_course = context_course::instance($this->courseid);
        }
    }

    /**
     * Returns targeted course if specified in constructor and if it exists.
     * @return null|stdClass
     */
    private function get_course(): ?stdClass {
        global $DB;

        if (!$this->courseid) {
            return null;
        }

        if (isset($this->cachedcourse)) {
            if ($this->cachedcourse === false) {
                return null;
            }
            return $this->cachedcourse;
        }

        $this->cachedcourse = $DB->get_record('course', ['id' => $this->courseid]);

        if ($this->cachedcourse === false) {
            return null;
        }
        return $this->cachedcourse;
    }

    /**
     * Recreate original $user parameter for use in recursive calls.
     *
     * @return stdClass
     */
    private function export_user(): stdClass {
        $user = new stdClass();
        $user->id = (string)$this->userid;
        $user->deleted = (string)(int)$this->userdeleted;
        $user->maildisplay = (string)$this->usermaildisplay;

        return $user;
    }

    /**
     * Returns true if the current user can view the given field for the tracked user.
     *
     * @param string $field
     * @return bool
     */
    public function can_view_field(string $field): bool {

        // Always default to no.
        $result = false;

        switch ($field) {
            case 'id':
                $result = true;
                break;
            case 'username':
            case 'auth':
            case 'confirmed':
            case 'lang':
            case 'theme':
            case 'timezone':
            case 'timecreated':
            case 'timemodified':
            case 'lastnamephonetic':
            case 'firstnamephonetic':
            case 'middlename':
            case 'alternatename':
            case 'mailformat':
                $result = (
                    $this->iscurrentuser ||
                    $this->has_view_all_details_capability()
                );
                break;
            case 'email':
                if (!$this->userdeleted) {
                    $result = (
                        $this->usermaildisplay === 1 || // Everyone is allowed to see.
                        $this::is_current_user_an_admin() || // The admin is allowed the users email.
                        $this->iscurrentuser || // Of course the current user is as well.
                        $this->has_course_email_capability() ||  // This is a capability in course context, it will be false in usercontext.
                        $this->has_plugin_granting_view_field('email') || // Those with a plugin or component defined relationship are allowed to see.
                        in_array('email', $this->get_identify_fields()) || // It's an identify field.
                        ($this->usermaildisplay === 2 && $this->do_users_share_courses()) // It's available to those who share courses.
                    );
                }
                break;
            case 'firstname':
            case 'lastname':
                $result = (
                    $this->iscurrentuser ||
                    $this->has_view_fullnames_capability()
                );
                break;
            case 'fullname':
            case 'profileimageurl':
            case 'profileimageurlsmall':
            case 'profileimagealt': // Special case, this is an alias for imagealt.
            case 'imagealt':
                $result = (
                    $this->iscurrentuser ||
                    $this->can_view_profile()
                );
                break;
            case 'address':
                $result = (
                    $this->iscurrentuser ||
                    $this->can_view_hidden_fields()
                );
                break;
            case 'phone1':
            case 'phone2':
                $result = (
                    $this->iscurrentuser ||
                    $this->can_view_hidden_fields() ||
                    (in_array($field, $this->get_identify_fields()) && $this->can_view_profile())
                );
                break;
            case 'country':
            case 'city':
            case 'url':
            case 'skype':
            case 'suspended':
            case 'firstaccess':
            case 'lastaccess':
                $result = (
                    $this->iscurrentuser ||
                    (
                        $this->can_view_profile() && (
                            !in_array($field, $this->get_hidden_fields()) ||
                            $this->can_view_hidden_fields()
                        )
                    )
                );
                break;
            case 'idnumber':
            case 'institution':
            case 'department':
                $result = (
                    $this->iscurrentuser ||
                    $this->has_view_all_details_capability() ||
                    (
                        in_array($field, $this->get_identify_fields()) &&
                        $this->can_view_profile()
                    )
                );
                break;
            case 'description':
            case 'descriptionformat':
                if ($this->userdeleted) {
                    $result = false;
                    break;
                }
                $result = (
                    $this->iscurrentuser ||
                    $this::is_current_user_an_admin() ||
                    (
                        $this->can_view_profile() && (
                            $this->resolve_profiles_for_enrolled_users_only() &&
                            (
                                !in_array('description', $this->get_hidden_fields()) ||
                                $this->can_view_hidden_fields()
                            )
                        )
                    )
                );
                break;
            case 'customfields':
            case 'preferences':
            case 'enrolledcourses':
            case 'interests':
                $method = 'can_view_'.$field;
                $result = $this->{$method}();
                break;
            case 'lastip':
                if (!$this->iscurrentuser && !$this->can_view_profile()) {
                    $result = false;
                    break;
                }
                if (!$this->context_user || !has_capability('moodle/user:viewlastip', $this->context_user)) {
                    $result = false;
                    break;
                }
                $result = (!in_array('lastip', $this->get_hidden_fields()) || $this->can_view_hidden_fields());
                break;

            // The following fields don't have access control - but we know about them so just return false.
            // They aren't there to be displayed, predominantly they are just flags.
            case 'policyagreed':
            case 'deleted':
            case 'password':
            case 'secret':
            case 'emailstop':
            case 'calendartype':
            case 'totarasync':
            case 'lastlogin':
            case 'currentlogin':
            case 'picture':
            case 'maildigest':
            case 'maildisplay':
            case 'autosubscribe':
            case 'trackforums':
            case 'trustbitmask':
                $result = false;
                break;
            default:
                throw new coding_exception('Unknown user field', $field);
        }

        // Plugins can override to allow access but not block access if it's already been given.
        if (!$result) {
            $result = self::has_plugin_granting_view_field($field);
        }

        return $result;
    }

    /**
     * Returns true if the current user can view the tracked users profile.
     *
     * @return bool
     */
    public function can_view_profile(): bool {
        global $CFG;

        if ($this->userdeleted) {
            // Deleted users do not have profiles.
            return false;
        }

        if ($this->is_user_access_prevented()) {
            // Tenants say no.
            return false;
        }

        if (empty($CFG->forceloginforprofiles)) {
            // You don't need to be logged in for profiles. Anyone can see them.
            return true;
        } else {
            if (!isloggedin() || (isguestuser() && !$this->iscurrentuser)) {
                // User is not logged in and forceloginforprofile is set, we need to return now.
                // The exception is the guest is allowed to see their own profile.
                return false;
            }
        }

        if ($this->courseid && !$this->is_enrolled()) {
            // The user who is being viewed is not enrolled in the course. They cannot possibly have a course profile.
            // We MUST return false here. Even if it is the current user.
            // NOTE: this changed in t13, use get_profile_url() if you are creating links to profiles instead.
            return false;
        }

        return (
            // Quickest check.
            $this->iscurrentuser ||
            // Single query lookup.
            $this->has_coursecontact_role() ||
            // The last check, on capabilities is going to be slow! So allow plugins to answer first.
            $this->has_plugin_granting_view_profile() ||
            // Finally, check capabilities. This means resolving all user relationships between courses.
            $this->has_view_details_capability()
        );
    }

    /**
     * Returns profile user for target user on condition
     * user can access it.
     *
     * NOTE: if user is not enrolled in target course,
     *       system profile link is returned is accessible.
     *
     * @return moodle_url|null
     */
    public function get_profile_url(): ?moodle_url {
        if ($this->courseid) {
            if ($this->is_enrolled()) {
                if ($this->can_view_profile()) {
                    return new moodle_url('/user/profile.php', ['id' => $this->userid, 'course' => $this->courseid]);
                } else {
                    // No need to check system profile, because if they had access
                    // the course profile would be most likely accessible too.
                    return null;
                }
            }
            // User is not enrolled, so better do not link course profile page, let's try system profile instead.
            $user = $this->export_user();
            return (self::for($user))->get_profile_url();
        } else {
            if ($this->can_view_profile()) {
                return new moodle_url('/user/profile.php', ['id' => $this->userid]);
            } else {
                return null;
            }
        }
    }

    /**
     * Returns true if the current user can view the tracked users enrolled courses.
     *
     * @return bool
     */
    public function can_view_enrolledcourses(): bool {
        return (
            $this->iscurrentuser ||
            (
                $this->can_view_profile() && (
                    !in_array('mycourses', $this->get_hidden_fields()) ||
                    $this->can_view_hidden_fields()
                )
            )
        );
    }

    /**
     * Returns true if the current user can view the tracked users preferences.
     *
     * @return bool
     */
    public function can_view_preferences(): bool {
        if (isguestuser($this->userid) || $this->userdeleted) {
            return false;
        }

        if ($this->is_user_access_prevented()) {
            return false;
        }

        return (
            $this->iscurrentuser ||
            // TOTARA: TL-6675 gave the ability for others to edit a users preference.
            has_capability('moodle/user:update', $this->context_user)
        );
    }

    /**
     * Returns true if the current user can view the tracked users custom fields.
     *
     * Please be aware that each individual field still needs to be checked.
     *
     * @return bool
     */
    public function can_view_customfields(): bool {
        return (
            $this->iscurrentuser ||
            $this->can_view_profile()
        );
    }

    /**
     * Returns true if the current user can view the tracked users interests.
     *
     * Interests are tags.
     *
     * @return bool
     */
    public function can_view_interests(): bool {
        return (
            $this->iscurrentuser ||
            $this->can_view_profile()
        );
    }

    /**
     * Returns true if the current user can manage the tracked users files.
     *
     * @return bool
     */
    public function can_manage_files(): bool {
        if (isguestuser($this->userid) || $this->userdeleted) {
            return false;
        }
        return (
            $this->iscurrentuser &&
            has_capability('moodle/user:manageownfiles', $this->context_user)
        );
    }

    /**
     * Can current user login as this user in system or given course?
     *
     * NOTE: Only real courses and system contexts are supported for login-as.
     *
     * @return bool
     */
    public function can_loginas(): bool {
        global $USER;
        if ($this->userdeleted) {
            // Deleted users do not have context, there is no way for them to log in.
            return false;
        }
        if ($this->userid == $USER->id) {
            // Cannot login-as self.
            return false;
        }
        if (\core\session\manager::is_loggedinas()) {
            // Login-as cannot be chained.
            return false;
        }
        if (is_siteadmin($this->userid)) {
            // Logging in as admin could lead to privilege escalation, it is strictly forbidden.
            return false;
        }
        if (isguestuser($this->userid)) {
            // Guests not supported here, they should just open a second window in incognito mode.
            return false;
        }
        if (!empty($USER->tenantid)) {
            // Login-as feature is not available to tenant members for security reasons,
            // it could be compromising tenant isolation.
            return false;
        }

        // System level login-as is controlled via capability only.
        if (!$this->courseid) {
            return has_capability('moodle/user:loginas', \context_system::instance());
        }

        // This is a course level login-as, only real courses are allowed.
        if (!has_capability('moodle/user:loginas', $this->context_course)) {
            return false;
        }

        if ($this->courseid == SITEID) {
            // This should not happen because the frontpage course is changed to null in constructor.
            throw new \coding_exception('Tracked courseid cannot be frontpage course.');
        }

        if ($this->context_course->tenantid) {
            // No login-as in tenant courses, this could be compromising tenant isolation.
            return false;
        }

        // Load the course.
        $course = $this->get_course();
        if (!$course) {
            // This should not happen because we have course context, but for whatever reason there is no course record.
            debugging('Access_controller found course context but no matching course record.', DEBUG_DEVELOPER);
            return false;
        }

        // Ideally we should use require_login() here to make sure current user can
        // actually get into the course, but we cannot because it would change the $PAGE.
        // So instead, check that current user either has ability to view the course or is enrolled.
        if ((!has_capability('moodle/course:view', $this->context_course) || !totara_course_is_viewable($course))
            && !is_enrolled($this->context_course, $USER->id, '', true)) {
            // Current user cannot enter the course, it means that the require_login() in course/loginas.php would likely fail.
            return false;
        }

        // We cannot do require_login() for other user, so let's just
        // check active enrolment which always grants course access.
        if (!is_enrolled($this->context_course, $this->userid, '', true)) {
            // User needs to be active member of the course which also includes course visibility check,
            // if not they are likely not able to access the contents of the course.
            // We cannot rely on users course:view capability here due to the way how login as works.
            return false;
        }

        // Check if course has SEPARATEGROUPS and user is part of that group.
        if (!has_capability('moodle/site:accessallgroups', $this->context_course) && groups_get_course_groupmode($course) == SEPARATEGROUPS) {
            $samegroup = false;
            if ($groups = groups_get_all_groups($course->id, $USER->id)) {
                foreach ($groups as $group) {
                    if (groups_is_member($group->id, $this->userid)) {
                        $samegroup = true;
                        break;
                    }
                }
            }
            if (!$samegroup) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the current user can see at least one of the groups of the specified user.
     *
     * @param stdClass $course
     * @return bool
     */
    private function are_course_groups_visible_to_user(stdClass $course): bool {
        if (!isset($this->resolutioncache[__METHOD__])) {
            $this->resolutioncache[__METHOD__] = groups_user_groups_visible($course, $this->userid);
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if the current user can see the tracked users hidden fields.
     *
     * @return bool
     */
    private function can_view_hidden_fields(): bool {
        if (isset($this->resolutioncache[__METHOD__])) {
            return $this->resolutioncache[__METHOD__];
        }
        $this->resolutioncache[__METHOD__] = false;
        if (!$this->context_user) {
            return false; // No context, the user will be deleted.
        }
        if (has_capability('moodle/user:viewhiddendetails', $this->context_user)) {
            $this->resolutioncache[__METHOD__] = true;
            return true;
        }
        foreach ($this->get_enrolled_courses() as $course) {
            if (has_capability('moodle/course:viewhiddenuserfields', context_course::instance($course->id))) {
                if (!$this->are_course_groups_visible_to_user($course)) {
                    // Not a member of the same group.
                    continue;
                }
                $this->resolutioncache[__METHOD__] = true;
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the current user and the tracked user share at least one course.
     *
     * @return bool
     */
    private function do_users_share_courses(): bool {
        global $USER;
        if (!isset($this->resolutioncache[__METHOD__])) {
            $this->resolutioncache[__METHOD__] = (bool)enrol_sharing_course($this->userid, $USER->id);
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if target course specified and target user is enrolled in it.
     *
     * NOTE: suspended state is checked for current user only unless they have moodle/course:view capability.
     *
     * @return bool
     */
    private function is_enrolled(): bool {
        global $USER;
        if (!isset($this->resolutioncache[__METHOD__])) {
            if ($this->courseid) {
                if ($this->userid == $USER->id and !is_viewing($this->context_course)) {
                    $this->resolutioncache[__METHOD__] = is_enrolled($this->context_course, $this->userid, '', true);
                } else {
                    $this->resolutioncache[__METHOD__] = is_enrolled($this->context_course, $this->userid, '', false);
                }
            } else {
                $this->resolutioncache[__METHOD__] = false;
            }
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns all of the courses the tracked user is enrolled in.
     *
     * @return array|stdClass[]
     */
    private function get_enrolled_courses(): array {
        if (!$this->enrolled_courses) {
            $this->enrolled_courses = [];
            if ($this->courseid) {
                if ($this->is_enrolled()) {
                    $course = $this->get_course();
                    if ($course) {
                        $this->enrolled_courses = [$course];
                    }
                }
            } else {
                $this->enrolled_courses = enrol_get_all_users_courses($this->userid);
                array_map(function ($course) {
                    context_helper::preload_from_record($course);
                }, $this->enrolled_courses);
            }
        }
        return $this->enrolled_courses;
    }

    /**
     * Returns an array of hidden fields.
     *
     * @return array
     */
    private function get_hidden_fields(): array {
        global $CFG;
        if ($this->hiddenfields === null) {
            if (empty($CFG->hiddenuserfields)) {
                $fields = [];
            } else {
                $fields = explode(',', $CFG->hiddenuserfields);
            }
            // These maps exist because the hidden field name does not match the field on the user record.
            $maps = [
                'webpage' => 'url',
                'skypeid' => 'skype',
            ];
            foreach ($maps as $key => $value) {
                if (in_array($key, $fields, true)) {
                    $fields[] = $value;
                }
            }
            $this->hiddenfields = $fields;
        }
        return $this->hiddenfields;
    }

    /**
     * Returns an array of user fields.
     *
     * @return array
     */
    private function get_identify_fields(): array {
        if ($this->identifyfields === null) {
            $this->identifyfields = [];
            if ($this->context_user) {
                $this->identifyfields = get_extra_user_fields($this->context_user);
            }
            foreach ($this->get_enrolled_courses() as $course) {
                $fields = get_extra_user_fields(context_course::instance($course->id));
                $this->identifyfields = array_merge($this->identifyfields, $fields);
            }
            $this->identifyfields = array_unique($this->identifyfields);
        }
        return $this->identifyfields;
    }

    /**
     * Returns true if the tracked user is a course contact in at least one course.
     *
     * @return bool
     */
    private function has_coursecontact_role(): bool {
        if (!isset($this->resolutioncache[__METHOD__])) {
            $this->resolutioncache[__METHOD__] = has_coursecontact_role($this->userid);
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if the current user holds the moodle/course:useremail in either the
     * tracked course, or at least one course the tracked user is enrolled in.
     *
     * @return bool
     */
    private function has_course_email_capability(): bool {
        if (!isset($this->resolutioncache[__METHOD__])) {
            $this->resolutioncache[__METHOD__] = false;
            foreach ($this->get_enrolled_courses() as $course) {
                if (!has_capability('moodle/course:useremail', context_course::instance($course->id))) {
                    continue;
                }
                if (!$this->are_course_groups_visible_to_user($course)) {
                    // Not a member of the same group.
                    continue;
                }
                $this->resolutioncache[__METHOD__] = true;
                break;
            }
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if the current user and the target user share relationship in a plugin or component.
     *
     * @return bool
     */
    private function has_plugin_granting_view_profile(): bool {
        global $USER;
        if (!isset($this->resolutioncache[__METHOD__])) {
            // The course should not be modified when passed along
            $course = $this->get_course();
            $course = $course ? clone $course : null;
            $hook = new allow_view_profile($this->userid, $USER->id, $course, $this->context_course);
            $this->resolutioncache[__METHOD__] = $hook->execute()->has_permission();
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if the current user and the target user share relationship in a plugin or component.
     *
     * @param string $field
     * @return bool
     */
    private function has_plugin_granting_view_field(string $field): bool {
        global $USER;
        $key = __METHOD__ . '::' . $field;
        if (!isset($this->resolutioncache[$key])) {
            // The course should not be modified when passed along
            $course = $this->get_course();
            $course = $course ? clone $course : null;
            $hook = new allow_view_profile_field($field, $this->userid, $USER->id, $course, $this->context_course);
            $this->resolutioncache[$key] = $hook->execute()->has_permission();
        }
        return $this->resolutioncache[$key];
    }

    /**
     * Returns true if the current user has the view all details capability in the tracked user context.
     *
     * @return bool
     */
    private function has_view_all_details_capability(): bool {
        if (!isset($this->resolutioncache[__METHOD__])) {
            $this->resolutioncache[__METHOD__] = false;
            if (!$this->context_user) {
                return false; // No context, the user will be deleted.
            }
            if (has_capability('moodle/user:viewalldetails', $this->context_user)) {
                // If you've got it against the user then you've got it.
                $this->resolutioncache[__METHOD__] = true;
                return true;
            }
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if user is prohibited from accessing user or course context due to tenant restrictions.
     *
     * @return bool
     */
    private function is_user_access_prevented(): bool {
        global $USER;

        if (!isset($this->resolutioncache[__METHOD__])) {
            if (!$this->context_user) {
                if (!empty($USER->tenantid)) {
                    return true;
                }
                return false;
            }
            if ($this->context_user->is_user_access_prevented()) {
                $this->resolutioncache[__METHOD__] = true;
            } else if ($this->courseid) {
                if ($this->context_course->is_user_access_prevented()) {
                    $this->resolutioncache[__METHOD__] = true;
                }
            }
            if (!isset($this->resolutioncache[__METHOD__])) {
                $this->resolutioncache[__METHOD__] = false;
            }
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if the current user has the view details capability on either the tracked user,
     * the tracked course, or if no tracked course then any course the tracked user is enrolled within.
     *
     * This method also checks the viewalldetails capability at the same time.
     *
     * @return bool
     */
    private function has_view_details_capability(): bool {
        if (!isset($this->resolutioncache[__METHOD__])) {
            $this->resolutioncache[__METHOD__] = false;
            if (!$this->context_user) {
                return false; // No context, the user has been deleted.
            }
            if (has_any_capability(['moodle/user:viewdetails', 'moodle/user:viewalldetails'], $this->context_user)) {
                // If you've got it against the user then you've got it.
                $this->resolutioncache[__METHOD__] = true;
                return true;
            }
            foreach ($this->get_enrolled_courses() as $course) {
                if (!has_any_capability(['moodle/user:viewdetails'], context_course::instance($course->id))) {
                    continue;
                }
                if (!$this->are_course_groups_visible_to_user($course)) {
                    // Not a member of the same group.
                    continue;
                }
                $this->resolutioncache[__METHOD__] = true;
                return true;
            }
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Returns true if the current user the view fullnames capability on either the tracked user,
     * the tracked course, or if no tracked course then any course the tracked user is enrolled within.
     * @return bool
     */
    private function has_view_fullnames_capability(): bool {
        if (!isset($this->resolutioncache[__METHOD__])) {
            $this->resolutioncache[__METHOD__] = false;
            if (!$this->context_user) {
                return false;
            }
            if (has_capability('moodle/site:viewfullnames', $this->context_user)) {
                $this->resolutioncache[__METHOD__] = true;
                return true;
            }
            foreach ($this->get_enrolled_courses() as $course) {
                if (!has_capability('moodle/site:viewfullnames', context_course::instance($course->id))) {
                    continue;
                }
                if (!$this->are_course_groups_visible_to_user($course)) {
                    // Not a member of the same group.
                    continue;
                }
                $this->resolutioncache[__METHOD__] = true;
                break;
            }
        }
        return $this->resolutioncache[__METHOD__];
    }

    /**
     * Is the current user a site admin.
     *
     * This method is static as the relationship between the tracked user and the current user has no impact on the
     * whether the current user is a site administrator.
     *
     * @return bool
     */
    private static function is_current_user_an_admin(): bool {
        global $USER;
        return is_siteadmin($USER);
    }

    /**
     * Returns true if profilesforenrolledusersonly is off or the tracked user meets the required conditions.
     *
     * The configprofilesforenrolledusersonly string states:
     *    "To prevent misuse by spammers, profile descriptions of users who are not yet enrolled in any course are hidden.
     *     New users must enrol in at least one course before they can add a profile description."
     * @return bool
     */
    private function resolve_profiles_for_enrolled_users_only(): bool {
        global $CFG, $DB;
        if (!isset($this->resolutioncache[__METHOD__])) {
            if (empty($CFG->profilesforenrolledusersonly)) {
                $this->resolutioncache[__METHOD__] = true;
            } else {
                $this->resolutioncache[__METHOD__] = $DB->record_exists('role_assignments', array('userid' => $this->userid));
            }
        }
        return $this->resolutioncache[__METHOD__];
    }
}
