<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author David Curry <david.curry@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

$moduleenabled = $module->is_enabled() === false;
$ADMIN->add('root', new admin_category('modfacetofacefolder', new lang_string('modulenameplural', 'mod_facetoface'), $moduleenabled), 'courses');

// Provide a link from the legacy location to global settings.
// Under the current architecture there is not an accessible
// method to retrieve the URL of a settings page dynamically.
$url = new moodle_url('/admin/settings.php', array('section' => 'modsettingfacetoface'));
$settings = new admin_externalpage('modfacetofaceredirect', new lang_string('pluginname','mod_facetoface'), $url, 'mod/facetoface:viewallsessions');
$ADMIN->add('modsettings', $settings);

// Event reports.
$url = new moodle_url('/mod/facetoface/reports/events.php');
$settings = new admin_externalpage('modfacetofaceeventreport', new lang_string('eventsreport', 'mod_facetoface'), $url, 'mod/facetoface:viewallsessions');
$ADMIN->add('modfacetofacefolder', $settings);

// Session reports. Hidden.
$url = new moodle_url('/mod/facetoface/reports/sessions.php');
$settings = new admin_externalpage('modfacetofacesessionreport', new lang_string('sessionsreport', 'mod_facetoface'), $url, 'mod/facetoface:viewallsessions', true);
$ADMIN->add('modfacetofacefolder', $settings);

// Global settings.
$settings = new admin_settingpage($section, get_string('globalsettings', 'mod_facetoface'), 'totara/core:modconfig', $moduleenabled);
$ADMIN->add('modfacetofacefolder', $settings);
if ($ADMIN->fulltree) { // Improve performance.
    require_once "$CFG->dirroot/mod/facetoface/lib.php";

    $settings->add(new admin_setting_heading('facetoface_general_header', get_string('generalsettings', 'facetoface'), ''));

    $settings->add(new admin_setting_pickroles('facetoface_session_roles', new lang_string('setting:sessionroles_caption', 'facetoface'),
        new lang_string('setting:sessionroles', 'facetoface'), array()));

    $options = array();
    $options['approval_none'] =  new lang_string('setting:approval_none', 'facetoface');
    $options['approval_self'] =  new lang_string('setting:approval_self', 'facetoface');
    if (!during_initial_install()) {
        // Roles can only be set after installation has completed.
        $available = explode(',', get_config(null, 'facetoface_session_roles'));
        $rolenames = role_fix_names(get_all_roles());
        foreach ($available as $roleid) {
            if (!empty($roleid)) { // This makes it work when empty on installation.
                $options["approval_role_{$roleid}"] = $rolenames[$roleid]->localname;
            }
        }
    }
    $options['approval_manager'] =  new lang_string('setting:approval_manager', 'facetoface');
    $options['approval_admin'] =  new lang_string('setting:approval_admin', 'facetoface');
    $settings->add(new admin_setting_configmulticheckbox('facetoface_approvaloptions', new lang_string('setting:approvaloptions_caption', 'facetoface'),
        new lang_string('setting:approvaloptions_default', 'facetoface'), array('approval_none' => 1, 'approval_self' => 1,'approval_manager' => 1), $options));
    $settings->add(new admin_setting_users_with_capability('facetoface_adminapprovers', new lang_string('setting:adminapprovers_caption', 'facetoface'),
        new lang_string('setting:adminapprovers_format', 'facetoface'), array(), 'mod/facetoface:approveanyrequest'));

    $settings->add(new admin_setting_configcheckbox('facetoface_managerselect',
        new lang_string('setting:managerselect_caption', 'facetoface'),
        new lang_string('setting:managerselect_format', 'facetoface'), 0));

    $settings->add(new admin_setting_configtext('facetoface_export_userprofilefields', new lang_string('exportuserprofilefields', 'facetoface'), new lang_string('exportuserprofilefields_desc', 'facetoface'), 'firstname,lastname,idnumber,institution,department,email', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('facetoface_export_customprofilefields', new lang_string('exportcustomprofilefields', 'facetoface'), new lang_string('exportcustomprofilefields_desc', 'facetoface'), '', PARAM_TEXT));

    // Create array with existing custom fields (if any), empty array otherwise.
    $customfields = array();
    $eventcustomfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
    foreach ($eventcustomfields as $key => $item) {
        if ($item->datatype != 'file') {
            $customfields['sess_' . $key] = get_string('customfieldsession', 'facetoface', $item->fullname);
        }
    }
    $roomcustomfields = customfield_get_fields_definition('facetoface_room', array('hidden' => 0));
    foreach ($roomcustomfields as $key => $item) {
        if ($item->datatype != 'file') {
            $customfields['room_' . $key] = get_string('customfieldroom', 'facetoface', $item->fullname);
        }
    }

    $calendarfilters = $customfields;
    $settings->add(new admin_setting_configmultiselect('facetoface_calendarfilters', new lang_string('setting:calendarfilterscaption', 'facetoface'), new lang_string('setting:calendarfilters', 'facetoface'), array('room', 'building', 'address'), $calendarfilters));

    // Show previous event within time period.
    $settings->add(
        new admin_setting_configselect(
            'facetoface_previouseventstimeperiod',
            new lang_string('previouseventstimeperiod', 'mod_facetoface'),
            new lang_string('previouseventstimeperiod_help', 'mod_facetoface'),
            0,
            array(
                0 => new lang_string('showallpreviousevents', 'mod_facetoface'),
                1000 => new lang_string('numdays', '', 1000),
                365 => new lang_string('numdays', '', 365),
                180 => new lang_string('numdays', '', 180),
                150 => new lang_string('numdays', '', 150),
                120 => new lang_string('numdays', '', 120),
                90 => new lang_string('numdays', '', 90),
                60 => new lang_string('numdays', '', 60),
                30 => new lang_string('numdays', '', 30)
            )
        )
    );

    $settings->add(new admin_setting_heading('facetoface_notifications_header', get_string('notificationsheading', 'facetoface'), ''));

    $settings->add(new admin_setting_configcheckbox('facetoface_allow_legacy_notifications', new lang_string('setting:allow_legacy_notifications_caption', 'facetoface'),
        new lang_string('setting:allow_legacy_notifications', 'facetoface'), 0));

    $settings->add(new admin_setting_configcheckbox('facetoface_notificationdisable', new lang_string('setting:notificationdisable_caption', 'facetoface'),
        new lang_string('setting:notificationdisable', 'facetoface'), 0));

    $settings->add(new admin_setting_pickroles('facetoface_session_rolesnotify', new lang_string('setting:sessionrolesnotify_caption', 'facetoface'),
        new lang_string('setting:sessionrolesnotify', 'facetoface'), array('editingteacher')));

    $settings->add(new admin_setting_configcheckbox('facetoface_oneemailperday', new lang_string('setting:oneemailperday_caption', 'facetoface'), new lang_string('setting:oneemailperday', 'facetoface'), 0));

    $settings->add(new admin_setting_configcheckbox('facetoface_disableicalcancel', new lang_string('setting:disableicalcancel_caption', 'facetoface'), new lang_string('setting:disableicalcancel', 'facetoface'), 0));

    $settings->add(new admin_setting_heading('facetoface_additional_features_header', get_string('additionalfeaturesheading', 'facetoface'), ''));

    $setting = new admin_setting_configcheckbox('facetoface_displaysessiontimezones', new lang_string('setting:displaysessiontimezones_caption', 'facetoface'),
        new lang_string('setting:displaysessiontimezones', 'facetoface'), 1);
    $setting->set_updatedcallback('facetoface_displaysessiontimezones_updated');
    $settings->add($setting);

    $options = [];
    $options[\mod_facetoface\room::ROOM_IDENTIFIER_NAME] = new lang_string('roomidentifier_nameonly', 'facetoface');
    $options[\mod_facetoface\room::ROOM_IDENTIFIER_BUILDING] = new lang_string('roomidentifier_name_building', 'facetoface');
    $options[\mod_facetoface\room::ROOM_IDENTIFIER_LOCATION] = new lang_string('roomidentifier_name_building_address', 'facetoface');
    $settings->add(
        new admin_setting_configselect(
            'facetoface_roomidentifier',
            new lang_string('setting:roomidentifier', 'mod_facetoface'),
            new lang_string('setting:roomidentifier_help', 'mod_facetoface'),
            \mod_facetoface\room::ROOM_IDENTIFIER_NAME,
            $options
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'facetoface_selectjobassignmentonsignupglobal',
            new lang_string('setting:selectjobassignmentonsignupglobal', 'facetoface'),
            new lang_string('setting:selectjobassignmentonsignupglobal_caption', 'facetoface'),
            0
        )
    );

    $settings->add(new admin_setting_configcheckbox('facetoface_allowwaitlisteveryone',
        new lang_string('setting:allowwaitlisteveryone_caption', 'facetoface'),
        new lang_string('setting:allowwaitlisteveryone', 'facetoface'), 0));

    $settings->add(new admin_setting_configcheckbox('facetoface_lotteryenabled',
        new lang_string('setting:lotteryenabled_caption', 'facetoface'),
        new lang_string('setting:lotteryenabled', 'facetoface'), 0));

    $settings->add(new admin_setting_configcheckbox('facetoface_hidecost', new lang_string('setting:hidecost_caption', 'facetoface'), new lang_string('setting:hidecost', 'facetoface'), 0));
    $settings->add(new admin_setting_configcheckbox('facetoface_hidediscount', new lang_string('setting:hidediscount_caption', 'facetoface'), new lang_string('setting:hidediscount', 'facetoface'), 0));
}

// Activity defaults.
$settings = new admin_settingpage('modfacetofacactivitydefaults', get_string('activitydefaults', 'mod_facetoface'), 'totara/core:modconfig', $moduleenabled);
$ADMIN->add('modfacetofacefolder', $settings);
if ($ADMIN->fulltree) {
    // vvv Appearance vvv
    $settings->add(new admin_setting_heading('facetoface/appearance', new lang_string('appearanceheader', 'mod_facetoface'), ''));

    $settings->add(new admin_setting_configcheckbox('facetoface/decluttersessiontable',
        new lang_string('decluttersessiontable', 'mod_facetoface'),
        new lang_string('decluttersessiontable_help', 'mod_facetoface'), 0));

    // ^^^ Appearance ^^^

    // vvv Attendance tracking and grading vvv
    $settings->add(new admin_setting_heading('facetoface/attendancetrackingheader', new lang_string('attendancetrackingheader', 'facetoface'), ''));

    $options = [];
    $options[\mod_facetoface\seminar::EVENT_ATTENDANCE_LAST_SESSION_START] = new lang_string('eventattendancetime:laststart', 'mod_facetoface');
    $options[\mod_facetoface\seminar::EVENT_ATTENDANCE_LAST_SESSION_END] = new lang_string('eventattendancetime:lastend', 'mod_facetoface');
    $options[\mod_facetoface\seminar::EVENT_ATTENDANCE_FIRST_SESSION_START] = new lang_string('eventattendancetime:firststart', 'mod_facetoface');
    $options[\mod_facetoface\seminar::EVENT_ATTENDANCE_UNRESTRICTED] = new lang_string('eventattendancetime:any', 'mod_facetoface');
    $settings->add(new admin_setting_configselect(
        'facetoface/attendancetime',
        new lang_string('eventattendancetime', 'mod_facetoface'),
        new lang_string('eventattendancetime_help', 'mod_facetoface'),
        \mod_facetoface\seminar::EVENT_ATTENDANCE_DEFAULT,
        $options
    ));

    $options = [];
    $options[\mod_facetoface\seminar::SESSION_ATTENDANCE_DISABLED] = new lang_string('sessionattendancetime:disabled', 'mod_facetoface');
    $options[\mod_facetoface\seminar::SESSION_ATTENDANCE_START] = new lang_string('sessionattendancetime:start', 'mod_facetoface');
    $options[\mod_facetoface\seminar::SESSION_ATTENDANCE_END] = new lang_string('sessionattendancetime:end', 'mod_facetoface');
    $options[\mod_facetoface\seminar::SESSION_ATTENDANCE_UNRESTRICTED] = new lang_string('sessionattendancetime:any', 'mod_facetoface');
    $settings->add(new admin_setting_configselect(
        'facetoface/sessionattendance',
        new lang_string('sessionattendancetime', 'mod_facetoface'),
        new lang_string('sessionattendancetime_help', 'mod_facetoface'),
        \mod_facetoface\seminar::SESSION_ATTENDANCE_DEFAULT,
        $options
    ));

    $settings->add(
        new admin_setting_configcheckbox(
            'facetoface/eventgradingmanual',
            new lang_string('eventgradingmanual', 'facetoface'),
            new lang_string('eventgradingmanual_help', 'facetoface'),
            0
        )
    );

    $options = [];
    $options[\mod_facetoface\seminar::GRADING_METHOD_GRADEHIGHEST] = new lang_string('eventgradingmethod:highest', 'facetoface');
    $options[\mod_facetoface\seminar::GRADING_METHOD_GRADELOWEST] = new lang_string('eventgradingmethod:lowest', 'facetoface');
    $options[\mod_facetoface\seminar::GRADING_METHOD_EVENTFIRST] = new lang_string('eventgradingmethod:first', 'facetoface');
    $options[\mod_facetoface\seminar::GRADING_METHOD_EVENTLAST] = new lang_string('eventgradingmethod:last', 'facetoface');
    $settings->add(
        new admin_setting_configselect(
            'facetoface/eventgradingmethod',
            new lang_string('eventgradingmethod', 'facetoface'),
            new lang_string('eventgradingmethod_help', 'facetoface'),
            \mod_facetoface\seminar::GRADING_METHOD_GRADEHIGHEST,
            $options
        )
    );

    $settings->add(new admin_setting_configtext('facetoface/gradepass', new lang_string('gradepass', 'mod_facetoface'), new lang_string('gradepass_help', 'mod_facetoface'), (string)\mod_facetoface\seminar::GRADE_PASS_DEFAULT, PARAM_FLOAT));
    // ^^^ Attendance tracking and grading ^^^

    // vvv Sign-up Workflow vvv

    $settings->add(new admin_setting_heading('facetoface_signupworkflow_header', new lang_string('signupworkflowheader', 'facetoface'), ''));

    $amounts = [];
    for ($i = 1; $i <= 10; $i++) {
        $amounts[$i] = $i;
    }
    $amounts[0] = new lang_string('multisignupamount_unlimited', 'facetoface');
    $settings->add(new admin_setting_configselect('facetoface_multisignupamount',
        new lang_string('multisignupamount', 'facetoface'),
        new lang_string('multisignupamount_help', 'facetoface'),
        2,
        $amounts
    ));

    $options = [];
    $options['multisignuprestrict_fully'] = new lang_string('status_fully_attended', 'facetoface');
    $options['multisignuprestrict_partially'] = new lang_string('status_partially_attended', 'facetoface');
    $options['multisignuprestrict_noshow'] = new lang_string('status_no_show', 'facetoface');
    $options['multisignuprestrict_unableto'] = new lang_string('status_unable_to_attend', 'facetoface');
    $settings->add(
        new admin_setting_configmulticheckbox(
            'facetoface_multisignup_restrict',
            new lang_string('multisignuprestrict', 'facetoface'),
            new lang_string('multisignuprestrict_help', 'facetoface'),
            [
                'multisignuprestrict_fully' => 0,
                'multisignuprestrict_partially' => 1,
                'multisignuprestrict_noshow' => 1,
                'multisignuprestrict_unableto' => 1
            ],
            $options
        )
    );

    $settings->add(new admin_setting_configcheckbox('facetoface_waitlistautoclean',
        new lang_string('waitlistautoclean', 'mod_facetoface'),
        new lang_string('waitlistautoclean_help', 'mod_facetoface'), 1));

    $settings->add(new admin_setting_configtextarea('facetoface_termsandconditions', new lang_string('setting:termsandconditions_caption', 'facetoface'),
        new lang_string('setting:termsandconditions_format', 'facetoface'), new lang_string('setting:termsandconditions_default', 'facetoface')));

    $settings->add(new admin_setting_heading('facetoface/managerreserveheader',
        new lang_string('setting:managerreserveheader', 'mod_facetoface'), ''));

    $settings->add(new admin_setting_configcheckbox('facetoface/managerreserve',
        new lang_string('setting:managerreserve', 'mod_facetoface'),
        new lang_string('setting:managerreserve_desc', 'mod_facetoface'), 0));

    $settings->add(new admin_setting_configtext('facetoface/maxmanagerreserves',
        new lang_string('setting:maxmanagerreserves', 'mod_facetoface'),
        new lang_string('setting:maxmanagerreserves_desc', 'mod_facetoface'), 1, PARAM_INT));

    $settings->add(new admin_setting_configtext('facetoface/reservecanceldays',
        new lang_string('setting:reservecanceldays', 'mod_facetoface'),
        new lang_string('setting:reservecanceldays_desc', 'mod_facetoface'), 1, PARAM_INT));

    $settings->add(new admin_setting_configtext('facetoface/reservedays',
        new lang_string('setting:reservedays', 'mod_facetoface'),
        new lang_string('setting:reservedays_desc', 'mod_facetoface'), 2, PARAM_INT));
    // ^^^ Sign-up Workflow ^^^

    // Notifications
    if (!empty($CFG->facetoface_allow_legacy_notifications)) {
        $settings->add(new admin_setting_heading('facetoface/notification',
                new lang_string('setting:notificationsheader', 'mod_facetoface'), '')
        );
        $settings->add(new admin_setting_configcheckbox('facetoface/legacy_notifications',
                new lang_string('setting:legacy_notifications', 'mod_facetoface'),
                new lang_string('setting:legacy_notifications_desc', 'mod_facetoface'), 0)
        );
    }
}

// Event defaults.
$settings = new admin_settingpage('modfacetofacesessiondefaults', get_string('sessiondefaults', 'mod_facetoface'), 'totara/core:modconfig', $moduleenabled);
$ADMIN->add('modfacetofacefolder', $settings);
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('facetoface/defaultdaystosession', new lang_string('defaultdaystosession', 'facetoface'), new lang_string('defaultdaystosession_desc', 'facetoface'), '1', PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('facetoface/defaultdaysskipweekends', new lang_string('defaultdaysskipweekends', 'facetoface'),new lang_string('defaultdaysskipweekends_desc', 'facetoface'), 1));
    $settings->add(new admin_setting_configtime('facetoface/defaultstarttime_hours', 'defaultstarttime_minutes', new lang_string('defaultstarttime', 'facetoface'), new lang_string('defaultstarttimehelp', 'facetoface'), array('h' => 9, 'm' => 0)));
    $settings->add(new admin_setting_configtext('facetoface/defaultdaysbetweenstartfinish', new lang_string('defaultdaysbetweenstartfinish', 'facetoface'), new lang_string('defaultdaysbetweenstartfinish_desc', 'facetoface'), '0', PARAM_INT));
    $settings->add(new admin_setting_configtime('facetoface/defaultfinishtime_hours', 'defaultfinishtime_minutes', new lang_string('defaultfinishtime', 'facetoface'), new lang_string('defaultfinishtimehelp', 'facetoface'), array('h' => 10, 'm' => 0)));
    $settings->add(new admin_setting_configtext('facetoface/defaultminbookings',
            new lang_string('setting:defaultminbookings', 'facetoface'),
            new lang_string('setting:defaultminbookings_help', 'facetoface'),
            0,
            PARAM_INT
        )
    );
    require_once($CFG->dirroot.'/lib/csvlib.class.php');
    $delimiters = \mod_facetoface\import_helper::csv_get_delimiter_list();
    $settings->add(new admin_setting_configselect('facetoface/defaultcsvdelimiter',
            new lang_string('defaultcsvdelimiter', 'mod_facetoface'),
            new lang_string('defaultcsvdelimiter_desc', 'mod_facetoface'),
            'auto',
            $delimiters
        )
    );
}

// Custom fields.
$url = new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacesession'));
$settings = new admin_externalpage('modfacetofacecustomfields', new lang_string('customfieldsheading','facetoface'), $url, 'mod/facetoface:managecustomfield');
$ADMIN->add('modfacetofacefolder', $settings);

// Notification templates.
$url = new moodle_url('/mod/facetoface/notification/template/index.php');
$settings = new admin_externalpage('modfacetofacetemplates', new lang_string('notificationtemplates','facetoface'), $url, 'totara/core:modconfig');
$ADMIN->add('modfacetofacefolder', $settings);

// Assets.
$url = new moodle_url('/mod/facetoface/asset/manage.php', ['published' => 0]);
$settings = new admin_externalpage('modfacetofaceassets', new lang_string('assets','facetoface'), $url, 'mod/facetoface:managesitewideassets');
$ADMIN->add('modfacetofacefolder', $settings);

// Facilitators.
$url = new moodle_url('/mod/facetoface/facilitator/manage.php', ['published' => 0]);
$settings = new admin_externalpage('modfacetofacefacilitators', new lang_string('facilitators','mod_facetoface'), $url, 'mod/facetoface:managesitewidefacilitators');
$ADMIN->add('modfacetofacefolder', $settings);
// Rooms.
$url = new moodle_url('/mod/facetoface/room/manage.php', ['published' => 0]);
$settings = new admin_externalpage('modfacetofacerooms', new lang_string('rooms','facetoface'), $url, 'mod/facetoface:managesitewiderooms');
$ADMIN->add('modfacetofacefolder', $settings);

// Tell core we already added the settings structure.
$settings = null;
