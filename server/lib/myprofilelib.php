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
 * Defines core nodes for my profile navigation tree.
 *
 * @package   core
 * @copyright 2015 onwards Ankit Agarwal
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/totara/job/lib.php');

/**
 * Defines core nodes for my profile navigation tree.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser ignored, $user->id is compared to $USER->id instead !!!
 * @param stdClass $course course object
 *
 * @return bool
 */
function core_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG, $USER, $DB, $PAGE, $OUTPUT;

    $usercontext = context_user::instance($user->id, MUST_EXIST);
    $systemcontext = context_system::instance();
    $courseorusercontext = !empty($course) ? context_course::instance($course->id) : $usercontext;
    $courseorsystemcontext = !empty($course) ? context_course::instance($course->id) : $systemcontext;
    $courseid = !empty($course) ? $course->id : SITEID;

    $iscurrentuser = ($USER->id == $user->id);

    $contactcategory = new core_user\output\myprofile\category('contact', get_string('userdetails'));
    // No after property specified intentionally. It is a hack to make administration block appear towards the end. Refer MDL-49928.
    $coursedetailscategory = new core_user\output\myprofile\category('coursedetails', get_string('coursedetails'));
    $miscategory = new core_user\output\myprofile\category('miscellaneous', get_string('miscellaneous'), 'coursedetails');
    $reportcategory = new core_user\output\myprofile\category('reports', get_string('reports'), 'miscellaneous');
    $admincategory = new core_user\output\myprofile\category('administration', get_string('administration'), 'reports');
    $loginactivitycategory = new core_user\output\myprofile\category('loginactivity', get_string('loginactivity'), 'administration');

    // Add categories.
    $tree->add_category($contactcategory);
    $tree->add_category($coursedetailscategory);
    $tree->add_category($miscategory);
    $tree->add_category($reportcategory);
    $tree->add_category($admincategory);
    $tree->add_category($loginactivitycategory);

    if (empty($course)) {
        $course = null;
    }
    $access_controller = \core_user\access_controller::for($user, $course);

    // Add core nodes.
    // Full profile node.
    if (!empty($course)) {
        // Use access controller for site (course=null) in order to show full profile.
        $url = \core_user\access_controller::for($user, null)->get_profile_url();
        if ($url) {
            $node = new core_user\output\myprofile\node('miscellaneous', 'fullprofile', get_string('fullprofile'), null, $url);
            $tree->add_node($node);
        }
    }

    // Edit profile.
    if (isloggedin() && !isguestuser($user)) {
        if (($iscurrentuser || is_siteadmin($USER) || !is_siteadmin($user)) && has_capability('moodle/user:update',
                    $systemcontext)) {
            $url = new moodle_url('/user/editadvanced.php', array('id' => $user->id, 'course' => $courseid,
                'returnto' => 'profile'));
            $node = new core_user\output\myprofile\node('contact', 'editprofile', get_string('editmyprofile'), null, $url,
                null, null, 'editprofile');
            $tree->add_node($node);
        } else if ((has_capability('moodle/user:editprofile', $usercontext) && !is_siteadmin($user))
                   || ($iscurrentuser && has_capability('moodle/user:editownprofile', $usercontext))) {
            $userauthplugin = false;
            if (!empty($user->auth)) {
                $userauthplugin = get_auth_plugin($user->auth);
            }
            if ($userauthplugin && $userauthplugin->can_edit_profile()) {
                $url = $userauthplugin->edit_profile_url($user->id);
                if (empty($url)) {
                    // Totara: 'id' is the name of parameter, Moodle messed it up during rewrite.
                    if (empty($course)) {
                        $url = new moodle_url('/user/edit.php', array('id' => $user->id, 'returnto' => 'profile'));
                    } else {
                        $url = new moodle_url('/user/edit.php', array('id' => $user->id, 'course' => $course->id,
                            'returnto' => 'profile'));
                    }
                }
                $node = new core_user\output\myprofile\node('contact', 'editprofile',
                        get_string('editmyprofile'), null, $url, null, null, 'editprofile');
                $tree->add_node($node);
            }
        }
    }

    // Totara: login management
    if (has_capability('moodle/user:managelogin', $usercontext) && (is_siteadmin() || !is_siteadmin($user))) {
        $url = new \moodle_url('/user/managelogin.php', array('id' => $user->id, 'returnto' => 'profile'));
        $node = new  core_user\output\myprofile\node('administration', 'manageuserlogin', get_string('manageuserlogin', 'totara_core'), null, $url);
        $tree->add_node($node);
    }

    // Totara: Preference page. Show it to the current user, administrators and people with the right capabilities.
    if ($iscurrentuser || $PAGE->settingsnav->can_view_user_preferences($user->id)) {
        $url = new moodle_url('/user/preferences.php', array('userid' => $user->id));
        $title = get_string('preferences', 'moodle');
        $node = new core_user\output\myprofile\node('administration', 'preferences', $title, null, $url);
        $tree->add_node($node);
    }

    //Totara: Site policy consent
    if (!empty($CFG->enablesitepolicies)) {
        if ($iscurrentuser || $PAGE->settingsnav->can_view_user_preferences($user->id)) {
            $url = new moodle_url('/admin/tool/sitepolicy/userlist.php', ['userid' => $user->id]);
            $title = get_string('userlistuserconsent', 'tool_sitepolicy');
            $node = new core_user\output\myprofile\node('administration', 'userconsent', $title, null, $url);
            $tree->add_node($node);
        }
    }

    // Login as ...
    if (\core_user\access_controller::for($user, null)->can_loginas()) {
        $url = new moodle_url('/course/loginas.php',
            array('id' => SITEID, 'user' => $user->id, 'sesskey' => sesskey()));
        $node = new  core_user\output\myprofile\node('administration', 'loginas', get_string('loginas'), null, $url);
        $tree->add_node($node);
    } else if ($access_controller->can_loginas()) {
        // NOTE: course level login-as is broken and cannot be fixed, it will be deprecated.
        $url = new moodle_url('/course/loginas.php',
                array('id' => $courseid, 'user' => $user->id, 'sesskey' => sesskey()));
        $node = new  core_user\output\myprofile\node('administration', 'loginas', get_string('loginas'), null, $url);
        $tree->add_node($node);
    }

    // Totara: user data overview
    if (has_capability('totara/userdata:viewinfo', $usercontext)) {
        $url = new \moodle_url('/totara/userdata/user_info.php', array('id' => $user->id));
        $node = new  core_user\output\myprofile\node('administration', 'userinfo', get_string('userinfo', 'totara_userdata'), null, $url);
        $tree->add_node($node);
    }

    // Totara: user data export
    if ($iscurrentuser and get_config('totara_userdata', 'selfexportenable') and has_capability('totara/userdata:exportself', $usercontext)) {
        $url = new moodle_url('/totara/userdata/export_request.php');
        $node = new  core_user\output\myprofile\node('administration', 'userdataexport', get_string('exportrequest', 'totara_userdata'), null, $url);
        $tree->add_node($node);
    }


    // Totara: add tenant info.
    if (!empty($CFG->tenantsenabled) and !isguestuser($user)) {
        $editaction = '';
        if (has_capability('totara/tenant:manageparticipants', $systemcontext)) {
            if ($DB->record_exists('tenant', [])) {
                $editurl = new \moodle_url('/totara/tenant/participant_manage.php', array('id' => $user->id));
                $editaction = $OUTPUT->action_icon($editurl, new \core\output\flex_icon('settings', array('alt' => get_string('participantmanage', 'totara_tenant'))));
            }
        }
        if ($user->tenantid) {
            $tenant = \core\record\tenant::fetch($user->tenantid);
            $node = new core_user\output\myprofile\node('contact', 'tenant', get_string('tenantmember', 'totara_tenant'), null, null, format_string($tenant->name) . $editaction);
            $tree->add_node($node);
        } else if (empty($USER->tenantid)) { // Hide participation info when tenant user looks
            $sql = 'SELECT t.id, t.name
                      FROM "ttr_tenant" t
                      JOIN "ttr_cohort" c ON c.id = t.cohortid
                      JOIN "ttr_cohort_members" cm ON cm.cohortid = c.id
                     WHERE cm.userid = :userid
                  ORDER BY t.name ASC';
            $tenants = $DB->get_records_sql_menu($sql, ['userid' => $user->id]);
            if ($tenants) {
                $tenants = implode(', ', $tenants);
                $tenants = format_string($tenants);
            } else {
                $tenants = get_string('no');
            }
            $node = new core_user\output\myprofile\node('contact', 'tenant', get_string('participant', 'totara_tenant'), null, null, $tenants . $editaction);
            $tree->add_node($node);
        }
    }

    // Contact details.
    if (has_capability('moodle/user:viewhiddendetails', $courseorusercontext)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }
    $identityfields = array_flip(explode(',', $CFG->showuseridentity));

    // If not the current user or don't have view identity permissions then unset all
    // except email (which is handled separately below)
    if (!$iscurrentuser && !has_capability('moodle/site:viewuseridentity', $courseorusercontext)) {
        $identityfields = array_intersect_key($identityfields, ['email' => 0]);
    }

    if (isset($identityfields['email']) and $access_controller->can_view_field('email')) {
        // TOTARA - Escape potential XSS in user email.
        $node = new core_user\output\myprofile\node('contact', 'email', get_string('email'), null, null,
            obfuscate_mailto(clean_string($user->email), ''));
        $tree->add_node($node);
    }

    if (!isset($hiddenfields['country']) && $user->country) {
        $node = new core_user\output\myprofile\node('contact', 'country', get_string('country'), null, null,
                get_string($user->country, 'countries'));
        $tree->add_node($node);
    }

    if (!isset($hiddenfields['city']) && $user->city) {
        // TOTARA - Escape potential XSS in extra identity fields.
        $node = new core_user\output\myprofile\node('contact', 'city', get_string('city'), null, null, s($user->city));
        $tree->add_node($node);
    }

    if (!isset($hiddenfields['timezone']) && $user->timezone) {
        $timezone = core_date::get_localised_timezone(core_date::get_user_timezone($user));
        $node = new core_user\output\myprofile\node('contact', 'timezone', get_string('timezone'), null, null, $timezone);
        $tree->add_node($node);
    }

    if (isset($identityfields['address']) && $user->address) {
        // TOTARA - Escape potential XSS in extra identity fields.
        $node = new core_user\output\myprofile\node('contact', 'address', get_string('address'), null, null, s($user->address));
        $tree->add_node($node);
    }

    if (isset($identityfields['phone1']) && $user->phone1) {
        // TOTARA - Escape potential XSS in extra identity fields.
        $node = new core_user\output\myprofile\node('contact', 'phone1', get_string('phone1'), null, null, s($user->phone1));
        $tree->add_node($node);
    }

    if (isset($identityfields['phone2']) && $user->phone2) {
        // TOTARA - Escape potential XSS in extra identity fields.
        $node = new core_user\output\myprofile\node('contact', 'phone2', get_string('phone2'), null, null, s($user->phone2));
        $tree->add_node($node);
    }

    if (isset($identityfields['institution']) && $user->institution) {
        // TOTARA - Escape potential XSS in extra identity fields.
        $node = new core_user\output\myprofile\node('contact', 'institution', get_string('institution'), null, null,
                s($user->institution));
        $tree->add_node($node);
    }

    if (isset($identityfields['department']) && $user->department) {
        // TOTARA - Escape potential XSS in extra identity fields.
        $node = new core_user\output\myprofile\node('contact', 'department', get_string('department'), null, null,
            s($user->department));
        $tree->add_node($node);
    }

    if (isset($identityfields['idnumber']) && $user->idnumber) {
        // TOTARA - Escape potential XSS in extra identity fields.
        $node = new core_user\output\myprofile\node('contact', 'idnumber', get_string('idnumber'), null, null,
            s($user->idnumber));
        $tree->add_node($node);
    }

    if ($user->url && !isset($hiddenfields['webpage'])) {
        $url = $user->url;
        if (strpos($user->url, '://') === false) {
            $url = 'http://'. $url;
        }
        $webpageurl = new moodle_url($url);
        $node = new core_user\output\myprofile\node('contact', 'webpage', get_string('webpage'), null, null,
            html_writer::link($url, $webpageurl));
        $tree->add_node($node);
    }

    // Printing tagged interests. We want this only for full profile.
    if (empty($course) && ($interests = core_tag_tag::get_item_tags('core', 'user', $user->id))) {
        // Totara TL-14103. It's better to not display 'more' and 'less' links to allow the user to control
        // how many interests tags are displayed - set the tag_list limit to 0 to display them all.
        $node = new core_user\output\myprofile\node('contact', 'interests', get_string('interests'), null, null,
                $OUTPUT->tag_list($interests, '', '', 0));
        $tree->add_node($node);
    }

    if (!isset($hiddenfields['mycourses'])) {
        $showallcourses = optional_param('showallcourses', 0, PARAM_INT);
        // TOTARA: add pagination
        if (!$showallcourses) {
            $limitnum = $CFG->navcourselimit + 1;
        } else {
            $limitnum = 0;
        }
        if ($mycourses = enrol_get_all_users_courses($user->id, true, null, 'visible DESC, sortorder ASC', 0, $limitnum)) {
            $shown = 0;
            $courselisting = html_writer::start_tag('ul');
            foreach ($mycourses as $mycourse) {
                if ($mycourse->category) {
                    context_helper::preload_from_record($mycourse);
                    $ccontext = context_course::instance($mycourse->id);
                    if (!isset($course) || $mycourse->id != $course->id) {
                        // Totara: make sure course visibility takes into account audience visibility settings.
                        if (!totara_course_is_viewable($mycourse->id)) {
                            // Be aware that enrol_get_all_users_courses only returns what the profile owner
                            // is allowed to see. This check is making sure that whoever views the profile
                            // can also see the course.
                            continue;
                        }
                        $linkattributes = array(
                            'class' => totara_get_style_visibility($mycourse)
                        );
                        $params = array('id' => $user->id, 'course' => $mycourse->id);
                        if ($showallcourses) {
                            $params['showallcourses'] = 1;
                        }
                        $url = new moodle_url('/user/profile.php', $params);
                        $courselisting .= html_writer::tag('li', html_writer::link($url, $ccontext->get_context_name(false),
                                $linkattributes));
                    } else {
                        $courselisting .= html_writer::tag('li', $ccontext->get_context_name(false));
                    }
                }
                $shown++;
                if (!$showallcourses && $shown == $CFG->navcourselimit) {
                    $url = null;
                    if (isset($course)) {
                        $url = new moodle_url('/user/profile.php',
                                array('id' => $user->id, 'course' => $course->id, 'showallcourses' => 1));
                    } else {
                        $url = new moodle_url('/user/profile.php', array('id' => $user->id, 'showallcourses' => 1));
                    }
                    $courselisting .= html_writer::tag('li', html_writer::link($url, get_string('viewmore'),
                            array('title' => get_string('viewmore'))), array('class' => 'viewmore'));
                    break;
                }
            }
            $courselisting .= html_writer::end_tag('ul');
            if (!empty($mycourses)) {
                // Add this node only if there are courses to display.
                $node = new core_user\output\myprofile\node('coursedetails', 'courseprofiles',
                    get_string('courseprofiles'), null, null, rtrim($courselisting, ', '));
                $tree->add_node($node);
            }
        }
    }

    if (!empty($course)) {

        // Show roles in this course.
        if ($rolestring = get_user_roles_in_course($user->id, $course->id)) {
            $node = new core_user\output\myprofile\node('coursedetails', 'roles', get_string('roles'), null, null, $rolestring);
            $tree->add_node($node);
        }

        // Show groups this user is in.
        if (!isset($hiddenfields['groups']) && !empty($course)) {
            $accessallgroups = has_capability('moodle/site:accessallgroups', $courseorsystemcontext);
            if ($usergroups = groups_get_all_groups($course->id, $user->id)) {
                $groupstr = '';
                foreach ($usergroups as $group) {
                    if ($course->groupmode == SEPARATEGROUPS and !$accessallgroups and $user->id != $USER->id) {
                        if (!groups_is_member($group->id, $user->id)) {
                            continue;
                        }
                    }

                    if ($course->groupmode != NOGROUPS) {
                        $groupstr .= ' <a href="'.$CFG->wwwroot.'/user/index.php?id='.$course->id.'&amp;group='.$group->id.'">'
                                     .format_string($group->name).'</a>,';
                    } else {
                        // The user/index.php shows groups only when course in group mode.
                        $groupstr .= ' '.format_string($group->name);
                    }
                }
                if ($groupstr !== '') {
                    $node = new core_user\output\myprofile\node('coursedetails', 'groups',
                            get_string('group'), null, null, rtrim($groupstr, ', '));
                    $tree->add_node($node);
                }
            }
        }

        if (!isset($hiddenfields['suspended'])) {
            if ($user->suspended) {
                $node = new core_user\output\myprofile\node('coursedetails', 'suspended',
                        null, null, null, get_string('suspended', 'auth'));
                $tree->add_node($node);
            }
        }
    }

    if ($user->skype && !isset($hiddenfields['skypeid'])) {
        $imurl = 'skype:'.urlencode($user->skype).'?call';
        $iconurl = new moodle_url('http://mystatus.skype.com/smallicon/'.urlencode($user->skype));
        if (is_https()) {
            // Bad luck, skype devs are lazy to set up SSL on their servers - see MDL-37233.
            $statusicon = '';
        } else {
            $statusicon = html_writer::empty_tag('img',
                array('src' => $iconurl, 'class' => 'icon icon-post', 'alt' => get_string('status')));
        }

        $node = new core_user\output\myprofile\node('contact', 'skypeid', get_string('skypeid'), null, null,
            html_writer::link($imurl, s($user->skype) . $statusicon));
        $tree->add_node($node);
    }

    if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, $user->id);
                    if ($formfield->is_visible() and !$formfield->is_empty()) {
                        $node = new core_user\output\myprofile\node('contact', 'custom_field_' . $formfield->field->shortname,
                            format_string($formfield->field->name), null, null, $formfield->display_data());
                        $tree->add_node($node);
                    }
                }
            }
        }
    }

    // First access. (Why only for sites ?)
    if (!isset($hiddenfields['firstaccess']) && empty($course)) {
        if ($user->firstaccess) {
            $datestring = userdate($user->firstaccess)."&nbsp; (".format_time(time() - $user->firstaccess).")";
        } else {
            $datestring = get_string("never");
        }
        $node = new core_user\output\myprofile\node('loginactivity', 'firstaccess', get_string('firstsiteaccess'), null, null,
            $datestring);
        $tree->add_node($node);
    }

    // Last access.
    if (!isset($hiddenfields['lastaccess'])) {
        if (empty($course)) {
            $string = get_string('lastsiteaccess');
            if ($user->lastaccess) {
                $datestring = userdate($user->lastaccess) . "&nbsp; (" . format_time(time() - $user->lastaccess) . ")";
            } else {
                $datestring = get_string("never");
            }
        } else {
            $string = get_string('lastcourseaccess');
            if ($lastaccess = $DB->get_record('user_lastaccess', array('userid' => $user->id, 'courseid' => $course->id))) {
                $datestring = userdate($lastaccess->timeaccess)."&nbsp; (".format_time(time() - $lastaccess->timeaccess).")";
            } else {
                $datestring = get_string("never");
            }
        }

        $node = new core_user\output\myprofile\node('loginactivity', 'lastaccess', $string, null, null,
            $datestring);
        $tree->add_node($node);
    }

    // Last ip.
    if (has_capability('moodle/user:viewlastip', $usercontext) && !isset($hiddenfields['lastip'])) {
        if ($user->lastip) {
            $iplookupurl = new moodle_url('/iplookup/index.php', array('ip' => $user->lastip, 'user' => $user->id));
            $ipstring = html_writer::link($iplookupurl, $user->lastip);
        } else {
            $ipstring = get_string("none");
        }
        $node = new core_user\output\myprofile\node('loginactivity', 'lastip', get_string('lastip'), null, null,
            $ipstring);
        $tree->add_node($node);
    }
}
