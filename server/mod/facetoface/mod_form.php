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
 * @package modules
 * @subpackage facetoface
 */

use mod_facetoface\seminar;

require_once "$CFG->dirroot/course/moodleform_mod.php";
require_once "$CFG->dirroot/mod/facetoface/lib.php";

require_once($CFG->libdir . '/pear/HTML/QuickForm/Rule.php');

/**
 * Provide numeric range rule.
 * This class could be moved to its own file, but it is only used here.
 */
class mod_facetoface_rule_numeric_range extends HTML_QuickForm_Rule {
    /**
     * Validates a numeric value.
     *
     * Provided $options must include min and max values.
     * If $options['int'] is true, the value must be an integer.
     *
     * @param mixed $value
     * @param null|array $options contains 'min', 'max', and optionally 'int'
     * @return mixed
     */
    public function validate($value, $options = null) {
        $float = unformat_float($value, true);
        if ($float === false) {
            return false;
        }
        if (!empty($options['int']) && ($float != (int)$float)) {
            return false;
        }
        if (isset($options['min']) && $options['min'] > $float) {
            return false;
        }
        if (isset($options['max']) && $options['max'] < $float) {
            return false;
        }
        return true;
    }
}

class mod_facetoface_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $PAGE;

        $mform =& $this->_form;
        $renderer = $PAGE->get_renderer('mod_facetoface');

        $this->setup_custom_js($mform);

        // GENERAL
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        if (empty($CFG->facetoface_notificationdisable)) {
            $mform->addElement('text', 'thirdparty', get_string('thirdpartyemailaddress', 'facetoface'), array('size' => '64'));
            $mform->setType('thirdparty', PARAM_NOTAGS);
            $mform->addHelpButton('thirdparty', 'thirdpartyemailaddress', 'facetoface');

            $mform->addElement('checkbox', 'thirdpartywaitlist', get_string('thirdpartywaitlist', 'facetoface'));
            $mform->addHelpButton('thirdpartywaitlist', 'thirdpartywaitlist', 'facetoface');
            $mform->setType('thirdpartywaitlist', PARAM_INT);
        } else {
            if (isset($this->_customdata['thirdparty'])) {
                $mform->addElement('hidden', 'thirdparty', $this->_customdata['thirdparty']);
                $mform->setType('thirdparty', PARAM_NOTAGS);
            }

            if (isset($this->_customdata['thirdpartywaitlist'])) {
                $mform->addElement('hidden', 'thirdpartywaitlist', $this->_customdata['thirdpartywaitlist']);
                $mform->setType('thirdpartywaitlist', PARAM_INT);
            }
        }

        $display = array();
        for ($i = 0; $i <= MDL_F2F_MAX_EVENTS_ON_COURSE; $i += 2) {
            $display[$i] = $i;
        }
        $mform->addElement('select', 'display', get_string('sessionsoncoursepage', 'facetoface'), $display);
        $mform->setDefault('display', MDL_F2F_DEFAULT_EVENTS_ON_COURSE);
        $mform->addHelpButton('display', 'sessionsoncoursepage', 'facetoface');

        $mform->addElement('checkbox', 'decluttersessiontable', get_string('decluttersessiontable', 'mod_facetoface'));
        $mform->addHelpButton('decluttersessiontable', 'decluttersessiontable', 'mod_facetoface');
        $mform->setType('decluttersessiontable', PARAM_BOOL);
        $mform->setDefault('decluttersessiontable', get_config('facetoface', 'decluttersessiontable'));

        if (has_capability('mod/facetoface:configurecancellation', $this->context)) {
            // User cancellation settings.
            $radioarray = array();
            $radioarray[] = $mform->createElement('radio', 'allowcancellationsdefault', '', get_string('allowcancellationanytime', 'facetoface'), 1);
            $radioarray[] = $mform->createElement('radio', 'allowcancellationsdefault', '', get_string('allowcancellationnever', 'facetoface'), 0);
            $radioarray[] = $mform->createElement('radio', 'allowcancellationsdefault', '', get_string('allowcancellationcutoff', 'facetoface'), 2);
            $mform->addGroup($radioarray, 'allowcancellationsdefault', get_string('allowbookingscancellationsdefault', 'facetoface'), array('<br/>'), false);
            $mform->setType('allowcancellationsdefault', PARAM_INT);
            $mform->setDefault('allowcancellationsdefault', 1);
            $mform->addHelpButton('allowcancellationsdefault', 'allowbookingscancellationsdefault', 'facetoface');

            // Cancellation cutoff.
            $cutoffnotegroup = array();
            $cutoffnotegroup[] =& $mform->createElement('duration', 'cancellationscutoffdefault', '', array('defaultunit' => HOURSECS, 'optional' => false));
            $cutoffnotegroup[] =& $mform->createElement('static', 'cutoffnote', null, get_string('cutoffnote', 'facetoface'));
            $mform->addGroup($cutoffnotegroup, 'cutoffgroup', '', '&nbsp;', false);
            $mform->setDefault('cancellationscutoffdefault', DAYSECS);
            $mform->disabledIf('cancellationscutoffdefault[number]', 'allowcancellationsdefault', 'notchecked', 2);
            $mform->disabledIf('cancellationscutoffdefault[timeunit]', 'allowcancellationsdefault', 'notchecked', 2);
        }

        $options = [];
        $options[seminar::EVENT_ATTENDANCE_LAST_SESSION_START] = new lang_string('eventattendancetime:laststart', 'mod_facetoface');
        $options[seminar::EVENT_ATTENDANCE_LAST_SESSION_END] = new lang_string('eventattendancetime:lastend', 'mod_facetoface');
        $options[seminar::EVENT_ATTENDANCE_FIRST_SESSION_START] = new lang_string('eventattendancetime:firststart', 'mod_facetoface');
        $options[seminar::EVENT_ATTENDANCE_UNRESTRICTED] = new lang_string('eventattendancetime:any', 'mod_facetoface');
        $mform->addElement('select', 'attendancetime', get_string('eventattendancetime', 'mod_facetoface'), $options);
        $mform->addHelpButton('attendancetime', 'eventattendancetime', 'mod_facetoface');
        $mform->setDefault('attendancetime', get_config('facetoface', 'attendancetime'));

        $options = [];
        $options[seminar::SESSION_ATTENDANCE_DISABLED] = new lang_string('sessionattendancetime:disabled', 'mod_facetoface');
        $options[seminar::SESSION_ATTENDANCE_START] = new lang_string('sessionattendancetime:start', 'mod_facetoface');
        $options[seminar::SESSION_ATTENDANCE_END] = new lang_string('sessionattendancetime:end', 'mod_facetoface');
        $options[seminar::SESSION_ATTENDANCE_UNRESTRICTED] = new lang_string('sessionattendancetime:any', 'mod_facetoface');
        $mform->addElement('select', 'sessionattendance', get_string('sessionattendancetime', 'mod_facetoface'), $options);
        $mform->addHelpButton('sessionattendance', 'sessionattendancetime', 'mod_facetoface');
        $mform->setDefault('sessionattendance', get_config('facetoface', 'sessionattendance'));

        $mform->addElement('header', 'gradesheader', get_string('gradesheader', 'facetoface'));

        $mform->addElement('checkbox', 'eventgradingmanual', get_string('eventgradingmanual', 'facetoface'));
        $mform->addHelpButton('eventgradingmanual', 'eventgradingmanual', 'facetoface');
        $mform->setType('eventgradingmanual', PARAM_BOOL);
        $mform->setDefault('eventgradingmanual', get_config('facetoface', 'eventgradingmanual'));

        $options = [];
        $options[seminar::GRADING_METHOD_GRADEHIGHEST] = new lang_string('eventgradingmethod:highest', 'facetoface');
        $options[seminar::GRADING_METHOD_GRADELOWEST] = new lang_string('eventgradingmethod:lowest', 'facetoface');
        $options[seminar::GRADING_METHOD_EVENTFIRST] = new lang_string('eventgradingmethod:first', 'facetoface');
        $options[seminar::GRADING_METHOD_EVENTLAST] = new lang_string('eventgradingmethod:last', 'facetoface');
        $mform->addElement('select', 'eventgradingmethod', get_string('eventgradingmethod', 'facetoface'), $options);
        $mform->addHelpButton('eventgradingmethod', 'eventgradingmethod', 'facetoface');
        $mform->setDefault('eventgradingmethod', get_config('facetoface', 'eventgradingmethod'));

        // In order to update {grade_items}.gradepass, the "Grade to pass" field must be named 'gradepass'.
        $mform->addElement('text', 'gradepass', get_string('gradepass', 'mod_facetoface'));
        $mform->setType('gradepass', PARAM_RAW);
        $mform->registerRule('f2f_numericrange', 'rule', 'mod_facetoface_rule_numeric_range');
        $minmax = ['min' => seminar::GRADE_PASS_MINIMUM, 'max' => seminar::GRADE_PASS_MAXIMUM];
        $mform->addRule('gradepass', get_string('err_numeric', 'form'), 'required');
        $mform->addRule('gradepass', get_string('gradepass_error', 'mod_facetoface', $minmax), 'f2f_numericrange', $minmax);
        $mform->setDefault('gradepass', get_config('facetoface', 'gradepass'));
        $mform->addHelpButton('gradepass', 'gradepass', 'mod_facetoface');

        $mform->addElement('header', 'approvaloptionsheader', get_string('signupworkflowheader', 'facetoface'));

        $options = array();
        for ($i = 1; $i <= 10; $i++) {
            $options[$i] = $i;
        }
        $options[0] = get_string('multisignupamount_unlimited', 'facetoface');
        $mform->addElement('select', 'multisignupamount', get_string('multisignupamount', 'facetoface'), $options);
        $mform->addHelpButton('multisignupamount', 'multisignupamount', 'facetoface');
        $mform->setDefault('multisignupamount', get_config(null, 'facetoface_multisignupamount'));

        $cbarray = array();
        $cbarray[] = $mform->createElement('checkbox', 'multisignuprestrictfully', '', get_string('status_fully_attended', 'mod_facetoface'), 0);
        $cbarray[] = $mform->createElement('checkbox', 'multisignuprestrictpartly', '', get_string('status_partially_attended', 'mod_facetoface'), 1);
        $cbarray[] = $mform->createElement('checkbox', 'multisignuprestrictnoshow', '', get_string('status_no_show', 'mod_facetoface'), 2);
        $cbarray[] = $mform->createElement('checkbox', 'multisignuprestrictunableto', '', get_string('status_unable_to_attend', 'mod_facetoface'), 3);
        $mform->addGroup($cbarray, 'multisignuprestrictions', get_string('multisignuprestrict', 'mod_facetoface'), ['<br/>'], false);
        $mform->disabledIf('multisignuprestrictions', 'multisignup_amount', 'eq', 1);
        $mform->setType('multisignuprestrictions', PARAM_INT);
        $mform->addHelpButton('multisignuprestrictions', 'multisignuprestrict', 'facetoface');
        $restrictdefaults = explode(',', get_config(null, 'facetoface_multisignup_restrict'));
        $mform->setDefault('multisignuprestrictfully', in_array('multisignuprestrict_fully', $restrictdefaults));
        $mform->setDefault('multisignuprestrictpartly', in_array('multisignuprestrict_partially', $restrictdefaults));
        $mform->setDefault('multisignuprestrictnoshow', in_array('multisignuprestrict_noshow', $restrictdefaults));
        $mform->setDefault('multisignuprestrictunableto', in_array('multisignuprestrict_unableto', $restrictdefaults));

        $mform->addElement('advcheckbox', 'waitlistautoclean', get_string('waitlistautoclean', 'mod_facetoface'), '', array('group' => 1), array(0, 1));
        $mform->setType('waitlistautoclean', PARAM_BOOL);
        $mform->addHelpButton('waitlistautoclean', 'waitlistautoclean', 'facetoface');
        $autocleandefault = get_config(null, 'facetoface_waitlistautoclean') ? 1 : 0;
        $mform->setDefault('waitlistautoclean', $autocleandefault);

        $declareinterestops = array(
            0 => get_string('declareinterestnever', 'facetoface'),
            2 => get_string('declareinterestnoupcoming', 'facetoface'),
            1 => get_string('declareinterestalways', 'facetoface')
        );
        $mform->addElement('select', 'declareinterest', get_string('declareinterestenable', 'facetoface'), $declareinterestops);
        $mform->addHelpButton('declareinterest', 'declareinterest', 'mod_facetoface');

        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        if (!empty($selectjobassignmentonsignupglobal)) {
            $mform->addElement('checkbox', 'selectjobassignmentonsignup', get_string('selectjobassignmentsignup', 'facetoface'));
            $mform->addElement('checkbox', 'forceselectjobassignment', get_string('forceselectjobassignment', 'facetoface'));
        }

        $conf = get_config('facetoface');

        $currentapprovaltype = null;
        $currentapprovalrole = null;
        if (!empty($this->current->id)) {
            $currentapprovaltype = $this->current->approvaltype;
            if ($currentapprovaltype == seminar::APPROVAL_ROLE) {
                $currentapprovalrole = $this->current->approvalrole;
            }
        }

        $settingsoptions = get_config(null, 'facetoface_approvaloptions');
        if (!empty($settingsoptions) || $currentapprovaltype !== null) {
            $options = explode(',', $settingsoptions);

            // A list of selected approvers to assign.
            $mform->addElement('hidden', 'selectedapprovers', '');
            $mform->setType('selectedapprovers', PARAM_SEQUENCE);
            if (isset($this->current->approvaladmins)) {
                $mform->getElement('selectedapprovers')->setValue($this->current->approvaladmins);
            }

            $radiogroup = array();
            $default = '';

            // No approval.
            if (in_array('approval_none', $options) || $currentapprovaltype === seminar::APPROVAL_NONE) {
                $radiogroup[] =& $mform->createElement('radio', 'approvaloptions', '', get_string('approval_none', 'mod_facetoface'), 'approval_none');
                $default = 'approval_none';
            }

            // Self approval.
            if (in_array('approval_self', $options) || $currentapprovaltype === seminar::APPROVAL_SELF) {
                $radiogroup[] =& $mform->createElement('radio', 'approvaloptions', '', get_string('approval_self', 'mod_facetoface'), 'approval_self');
                $radiogroup[] =& $mform->createElement('textarea', 'approval_termsandconds', '', '', 'approval_termsandconds');
                $default = empty($default) ? 'approval_self' : $default;
            }

            // Role approvals.

            $rolenames = role_fix_names(get_all_roles());
            $currentfound = false;
            foreach ($options as $option) {
                if (preg_match('/approval_role_/', $option)) {
                    $split = explode('_', $option);
                    $roleid = $split[2];

                    $radiogroup[] =& $mform->createElement('radio', 'approvaloptions', '', $rolenames[$roleid]->localname, $option);
                    $default = empty($default) ? $option : $default;

                    if ($roleid == $currentapprovalrole) {
                        $currentfound = true;
                    }
                }
            }

            //This is only happening when the option is being
            //disabled from the global settings, however,
            //the seminar module's settings still need to support it
            if ($currentapprovaltype == seminar::APPROVAL_ROLE && !$currentfound && isset($rolenames[$currentapprovalrole])) {
                if (!isset($roleid)) {
                    $roleid = $currentapprovalrole;
                }

                $option = "approval_role_{$roleid}";
                $radiogroup[] =& $mform->createElement('radio', 'approvaloptions', '', $rolenames[$roleid]->localname, $option);
                $default = empty($default) ? $option : $default;
            }

            // Manager approval.
            if (in_array('approval_manager', $options) || $currentapprovaltype === seminar::APPROVAL_MANAGER) {
                $radiogroup[] =& $mform->createElement('radio', 'approvaloptions', '', get_string('approval_manager', 'mod_facetoface'), 'approval_manager');
                $default = empty($default) ? 'approval_manager' : $default;
            }

            // Two step approval.
            if (in_array('approval_admin', $options) || $currentapprovaltype === seminar::APPROVAL_ADMIN) {
                $radiogroup[] =& $mform->createElement('radio', 'approvaloptions', '', get_string('approval_admin', 'mod_facetoface'), 'approval_admin');
                $default = empty($default) ? 'approval_admin' : $default;

                $systemapprovers = html_writer::start_tag('div', array('id' => 'systemapproverbox', 'class' => 'system_approvers'));
                $approvers = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
                foreach ($approvers as $approver) {
                    if (!empty($approver)) { // This makes it work when no one is selected.
                        $systemapprovers .= $renderer->display_approver($approver, false);
                    }
                }
                $systemapprovers .= html_writer::end_tag('div');

                $radiogroup[] = $mform->createElement('static', "siteapprovers", '', $systemapprovers)->set_allow_xss(true);

                $activityapprovers = html_writer::start_tag('div', array('id' => 'activityapproverbox', 'class' => 'activity_approvers'));
                $approvers = array();
                if (isset($this->current->approvaladmins)) {
                   $approvers = explode(',', $this->current->approvaladmins);
                }
                foreach ($approvers as $approverid) {
                    if (!empty($approverid)) {
                        $approver = core_user::get_user($approverid);
                        $activityapprovers .= $renderer->display_approver($approver, true);
                    }
                }
                $activityapprovers .= html_writer::end_tag('div');

                $radiogroup[] = $mform->createElement('static', "activityapprovers", '', $activityapprovers)->set_allow_xss(true);

                $radiogroup[] =& $mform->createElement('submit', 'addapprovaladmins', get_string('approval_addapprover', 'mod_facetoface'),
                        array('id' => 'show-addapprover-dialog'));
            }
            $mform->addGroup($radiogroup, 'approvaloptions', get_string('approvaloptions', 'mod_facetoface'), html_writer::empty_tag('br'), false);

            $mform->setDefault('approvaloptions', $default);

            $mform->setDefault('approval_termsandconds', get_config(null, 'facetoface_termsandconditions'));
            $mform->disabledIf('approval_termsandconds', 'approvaloptions', 'noteq', 'approval_self');

            $mform->disabledIf('addapprovaladmins', 'approvaloptions', 'noteq', 'approval_admin');

            $mform->addHelpButton('approvaloptions', 'approvaloptions', 'mod_facetoface');
        } else {
            // There are no approval options enabled, default to approval_none.
            $mform->addElement('hidden', 'approvaloptions', 'approval_none');
            $mform->setType('approvaloptions', PARAM_ALPHANUMEXT);
            $mform->setConstant('approvaloptions', 'approval_none');
        }

        // Manager reservations.
        $mform->addElement('header', 'managerreserveheader', get_string('managerreserveheader', 'mod_facetoface'));

        $mform->addElement('selectyesno', 'managerreserve', get_string('managerreserve', 'mod_facetoface'));
        $mform->setDefault('managerreserve', $conf->managerreserve);
        $mform->addHelpButton('managerreserve', 'managerreserve', 'mod_facetoface');

        $mform->addElement('text', 'maxmanagerreserves', get_string('maxmanagerreserves', 'mod_facetoface'));
        $mform->setType('maxmanagerreserves', PARAM_INT);
        $mform->setDefault('maxmanagerreserves', $conf->maxmanagerreserves);
        $mform->addHelpButton('maxmanagerreserves', 'maxmanagerreserves', 'mod_facetoface');
        $mform->disabledIf('maxmanagerreserves', 'managerreserve', 'eq', 0);

        $mform->addElement('selectyesno', 'reservecancel', get_string('reservecancel', 'mod_facetoface'));
        $mform->disabledIf('reservecancel', 'managerreserve', 'eq', 0);

        $mform->addElement('text', 'reservecanceldays', get_string('reservecanceldays', 'mod_facetoface'));
        $mform->setType('reservecanceldays', PARAM_INT);
        $mform->setDefault('reservecanceldays', $conf->reservecanceldays);
        $mform->addHelpButton('reservecanceldays', 'reservecanceldays', 'mod_facetoface');
        $mform->disabledIf('reservecanceldays', 'managerreserve', 'eq', 0);
        $mform->disabledIf('reservecanceldays', 'reservecancel', 'eq', 0);

        $mform->addElement('text', 'reservedays', get_string('reservedays', 'mod_facetoface'));
        $mform->setType('reservedays', PARAM_INT);
        $mform->setDefault('reservedays', $conf->reservedays);
        $mform->addHelpButton('reservedays', 'reservedays', 'mod_facetoface');
        $mform->disabledIf('reservedays', 'managerreserve', 'eq', 0);

        // Calendar options.
        $mform->addElement('header', 'calendaroptions', get_string('calendaroptions', 'facetoface'));

        $calendarOptions = array(
            F2F_CAL_NONE    =>  get_string('none'),
            F2F_CAL_COURSE  =>  get_string('course'),
            F2F_CAL_SITE    =>  get_string('site')
        );
        $mform->addElement('select', 'showoncalendar', get_string('showoncalendar', 'facetoface'), $calendarOptions);
        $mform->setDefault('showoncalendar', F2F_CAL_COURSE);
        $mform->addHelpButton('showoncalendar', 'showoncalendar', 'facetoface');

        $mform->addElement('advcheckbox', 'usercalentry', get_string('usercalentry', 'facetoface'));
        $mform->setDefault('usercalentry', true);
        $mform->addHelpButton('usercalentry', 'usercalentry', 'facetoface');

        $mform->addElement('text', 'shortname', get_string('shortname'), array('size' => 32, 'maxlength' => 32));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addHelpButton('shortname', 'shortname', 'facetoface');

        // Notification options.
        if (facetoface_site_allows_legacy_notifications()) {
            $mform->addElement('header', 'notification_options', get_string('notification_options', 'facetoface'));
            $mform->addElement('advcheckbox', 'legacy_notifications', get_string('legacy_notifications', 'facetoface'));
            $mform->addHelpButton('legacy_notifications', 'legacy_notifications', 'facetoface');
            $mform->setDefault('legacy_notifications', $conf->legacy_notifications ?? 0);
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    // Setup custom javascript.
    function setup_custom_js($mform) {
        global $PAGE;

        local_js(array(
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW
        ));
        $PAGE->requires->strings_for_js(array('chooseapprovers', 'error:approvernotselected', 'currentlyselected'), 'mod_facetoface');
        $jsmodule = array(
            'name' => 'facetoface_approver',
            'fullpath' => '/mod/facetoface/js/approver.js',
            'requires' => array('json'));

        $args = array(
            'course' => $this->current->course,
            'sesskey' => sesskey()
        );

        $PAGE->requires->js_init_call('M.facetoface_approver.init', $args, false, $jsmodule);
    }

    function data_preprocessing(&$default_values) {
        if (empty($default_values['reminderinstrmngr'])) {
            $default_values['reminderinstrmngr'] = null;
        } else {
            $default_values['emailmanagerreminder'] = 1;
        }

        if (empty($default_values['cancellationinstrmngr'])) {
            $default_values['cancellationinstrmngr'] = null;
        } else {
            $default_values['emailmanagercancellation'] = 1;
        }

        // Set some completion default data.
        if (!empty($default_values['completionstatusrequired']) && !is_array($default_values['completionstatusrequired'])) {
            // Unpack values.
            $cvalues = json_decode($default_values['completionstatusrequired'], true);
            $default_values['completionstatusrequired'] = $cvalues;
        }

        $conf = get_config('facetoface');

        if (isset($default_values['reservecanceldays'])) {
            if ($default_values['reservecanceldays'] == 0) {
                $default_values['reservecanceldays'] = $conf->reservecanceldays;
                $default_values['reservecancel'] = 0;
            } else {
                $default_values['reservecancel'] = 1;
            }
        }
    }

    public function definition_after_data() {
        $mform =& $this->_form;

        parent::definition_after_data();

        // The 'completionpass' may not exist when activity completion is disabled.
        if ($mform->elementExists('completionpass')) {
            $value = $mform->getElementValue('completionpass');
            $completionpass = isset($value) ? (int)$value[0] : null;
            $completionusegrade = $mform->getElement('completionusegrade');
            /** @var MoodleQuickForm_checkbox $completionusegrade */
            if ($completionpass == seminar::COMPLETION_PASS_ANY) {
                $completionusegrade->setValue('1');
            } else {
                $completionusegrade->setValue('0');
            }
        }
    }

    function add_completion_rules() {

        $mform =& $this->_form;
        $items = array();

        // Replace the existing completionusegrade with a hidden placeholder. (Yes, we need it!)
        // Then display our own completionpass instead.
        $mform->removeElement('completionusegrade');
        $mform->addElement('hidden', 'completionusegrade', 0);
        $mform->setType('completionusegrade', PARAM_INT);

        $options = array(
            seminar::COMPLETION_PASS_DISABLED   => get_string('completionpass:no', 'mod_facetoface'),
            seminar::COMPLETION_PASS_ANY        => get_string('completionpass:any', 'mod_facetoface'),
            seminar::COMPLETION_PASS_GRADEPASS  => get_string('completionpass:pass', 'mod_facetoface'),
        );
        $mform->addElement('select', 'completionpass', get_string('completionpass', 'mod_facetoface'), $options);
        $mform->disabledIf('completionpass', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
        $mform->addHelpButton('completionpass', 'completionpass', 'mod_facetoface');
        $items[] = 'completionpass';

        // Require status.
        $first = true;
        $firstkey = null;
        $states = [\mod_facetoface\signup\state\partially_attended::class, \mod_facetoface\signup\state\fully_attended::class];
        foreach ($states as $state) {
            $value = $state::get_string();
            $name = null;
            $key = $state::get_code();
            $key = 'completionstatusrequired['.$key.']';
            if ($first) {
                $name = get_string('completionstatusrequired', 'facetoface');
                $first = false;
                $firstkey = $key;
            }
            $mform->addElement('checkbox', $key, $name, $value);
            $mform->setType($key, PARAM_BOOL);
            $items[] = $key;
        }
        $mform->addHelpButton($firstkey, 'completionstatusrequired', 'facetoface');

        // Require event over for x days (completiondelay).
        $enabled_input = $mform->createElement('checkbox', 'completiondelayenabled', 1);
        $days_input = $mform->createElement('text', 'completiondelay');
        $days_input->setSize(3);
        $days_text = $mform->createElement('static', null, null, get_string('completiondelaydays', 'facetoface'));
        $mform->addGroup([$enabled_input, $days_input, $days_text], 'completiondelay', get_string('completiondelay', 'facetoface'), '&nbsp;', false);
        $mform->setType('completiondelayenabled', PARAM_BOOL);
        $mform->setType('completiondelay', PARAM_RAW);
        $mform->setDefault('completiondelay', 0);
        $minmax = ['min' => seminar::COMPLETION_DELAY_MINIMUM, 'max' => seminar::COMPLETION_DELAY_MAXIMUM, 'int' => true];
        $mform->addRule('completiondelay', get_string('completiondelay_error', 'mod_facetoface', $minmax), 'f2f_numericrange', $minmax);
        $mform->disabledIf('completiondelay', 'completiondelayenabled', 'notchecked');
        $mform->addHelpButton('completiondelay', 'completiondelay', 'facetoface');
        $items[] = 'completiondelay';

        return $items;
    }

    function completion_rule_enabled($data) {
        return (!empty($data['completionpass']) || !empty($data['completionstatusrequired']));
    }

    function get_data($slashed = true) {
        $data = parent::get_data($slashed);

        if (!$data) {
            return false;
        }

        // Convert completionstatusrequired to a proper integer, if any.
        if (isset($data->completionstatusrequired) && is_array($data->completionstatusrequired)) {
            $data->completionstatusrequired = json_encode($data->completionstatusrequired);
        }

        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = isset($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;

            if (isset($data->completionstatusrequired) && $autocompletion) {
                // Do nothing: completionstatusrequired has been already converted
                //             into a correct integer representation.
            } else {
                $data->completionstatusrequired = null;
            }

            if (!empty($data->completionscoredisabled) || !$autocompletion) {
                $data->completionscorerequired = null;
            }
        }

        // Convert interest option to flags for stroing in db.
        if ($data->declareinterest == 2) {
            $data->interestonlyiffull =  1;
            $data->declareinterest = 1;
        }

        // Approval settings
        if ($data->approvaloptions == 'approval_none') {
            $data->approvaltype = seminar::APPROVAL_NONE;
        } else if ($data->approvaloptions == 'approval_self') {
            $data->approvaltype = seminar::APPROVAL_SELF;
        } else if (preg_match('/approval_role_/', $data->approvaloptions)) {
            $split = explode('_', $data->approvaloptions);
            $data->approvaltype = seminar::APPROVAL_ROLE;
            $data->approvalrole = $split[2];
        } else if ($data->approvaloptions == 'approval_manager') {
            $data->approvaltype = seminar::APPROVAL_MANAGER;
        } else if ($data->approvaloptions == 'approval_admin') {
            $data->approvaltype = seminar::APPROVAL_ADMIN;
            $selected = empty($data->selectedapprovers) ? array() : explode(',', $data->selectedapprovers);
            $data->approvaladmins = implode(',', $selected);
        }

        if (isset($data->approval_termsandconds)) {
            $data->approvalterms = $data->approval_termsandconds;
        }

        // Fix settings.
        if (empty($data->completionstatusrequired)) {
            $data->completionstatusrequired = null;
        }
        if (empty($data->reservecancel)) {
            $data->reservecanceldays = 0;
        }
        if (empty($data->emailmanagerreminder)) {
            $data->reminderinstrmngr = null;
        }
        if (empty($data->emailmanagercancellation)) {
            $data->cancellationinstrmngr = null;
        }
        if (empty($data->usercalentry)) {
            $data->usercalentry = 0;
        }
        if (empty($data->thirdpartywaitlist)) {
            $data->thirdpartywaitlist = 0;
        }
        if (empty($data->sessionattendance)) {
            $data->sessionattendance = 0;
        }
        if (empty($data->eventgradingmanual)) {
            $data->eventgradingmanual = 0;
        }
        if (!empty($data->shortname)) {
            // This needs to match the actual database field size in mod/facetoface/db/install.xml file.
            $data->shortname = core_text::substr($data->shortname, 0, 32);
        }
        if (empty($data->declareinterest)) {
            $data->declareinterest = 0;
        }
        if (empty($data->interestonlyiffull) || !$data->declareinterest) {
            $data->interestonlyiffull = 0;
        }
        if (empty($data->selectjobassignmentonsignup) || !$data->selectjobassignmentonsignup) {
            $data->selectjobassignmentonsignup = 0;
        }
        if (empty($data->forceselectjobassignment) || !$data->forceselectjobassignment) {
            $data->forceselectjobassignment = 0;
        }

        // Multiple sign-up settings.
        $data->multisignupfully = empty($data->multisignuprestrictfully) ? 0 : 1;
        $data->multisignuppartly = empty($data->multisignuprestrictpartly) ? 0 : 1;
        $data->multisignupnoshow = empty($data->multisignuprestrictnoshow) ? 0 : 1;
        $data->multisignupunableto = empty($data->multisignuprestrictunableto) ? 0 : 1;

        // Slight hack caused by bad planning.
        $data->multiplesessions = $data->multisignupamount != 1;
        $data->multisignupmaximum = $data->multisignupamount;

        $data->waitlistautoclean = empty($data->waitlistautoclean) ? 0 : 1;

        // Set completionusegrade based on completionpass as below.
        // These fields are used by the system.
        //
        // completionpass      DISABLED ANY              GRADEPASS
        // ---------------     -------- ---              ---------
        // completionusegrade  0        1                0

        switch ($data->completionpass ?? seminar::COMPLETION_PASS_DISABLED) {
            case seminar::COMPLETION_PASS_DISABLED:
                $data->completionusegrade = 0;
                break;
            case seminar::COMPLETION_PASS_ANY:
                $data->completionusegrade = 1;
                break;
            case seminar::COMPLETION_PASS_GRADEPASS:
                $data->completionusegrade = 0;
                break;
        }

        // Completion delay should be set to null if disabled.
        if (!isset($data->completiondelayenabled)) {
            $data->completiondelay = null;
        }

        if (empty($data->decluttersessiontable)) {
            $data->decluttersessiontable = 0;
        }

        return $data;
    }

    // Need to translate the "options" and "reference" field.
    public function set_data($defaultvalues) {

        $defaultvalues = (array)$defaultvalues;

        if (!empty($defaultvalues['id'])) {
            // This is an existing facetoface, get the approval type for the options.
            $defaultvalues['approvaloptions'] = array();
            switch($defaultvalues['approvaltype']) {
                case seminar::APPROVAL_NONE:
                    $defaultvalues['approvaloptions'] = 'approval_none';
                    break;
                case seminar::APPROVAL_SELF:
                    $defaultvalues['approvaloptions'] = 'approval_self';
                    break;
                case seminar::APPROVAL_ROLE:
                    $defaultvalues['approvaloptions'] = 'approval_role_' . $defaultvalues['approvalrole'];
                    break;
                case seminar::APPROVAL_MANAGER:
                    $defaultvalues['approvaloptions'] = 'approval_manager';
                    break;
                case seminar::APPROVAL_ADMIN:
                    $defaultvalues['approvaloptions'] = 'approval_admin';
                    break;
            }
            $defaultvalues['approval_termsandconds'] = $defaultvalues['approvalterms'];

            // Convert interest flags to option.
            $defaultvalues['declareinterest'] = ($defaultvalues['interestonlyiffull'] == 1) ? 2 : $defaultvalues['declareinterest'];

            // Translate the multiple signup values from db to form.
            $defaultvalues['multisignuprestrictfully'] = $defaultvalues['multisignupfully'];
            $defaultvalues['multisignuprestrictpartly'] = $defaultvalues['multisignuppartly'];
            $defaultvalues['multisignuprestrictnoshow'] = $defaultvalues['multisignupnoshow'];
            $defaultvalues['multisignuprestrictunableto'] = $defaultvalues['multisignupunableto'];

            // Slight hack caused by bad planning.
            if ($defaultvalues['multiplesessions'] == 0) {
                $defaultvalues['multisignupamount'] = 1;
            } else {
                $defaultvalues['multisignupamount'] = $defaultvalues['multisignupmaximum'];
            }
        }

        if (!isset($defaultvalues['gradepass'])) {
            // Manually load the default setting to correctly format the value.
            $gradepass = get_config('facetoface', 'gradepass');
            $defaultvalues['gradepass'] = \mod_facetoface\grade_helper::format($gradepass, $this->current->course);
        }

        // If there is a completiondelay value, then check the enabled checkbox.
        if (isset($defaultvalues['completiondelay']) && $defaultvalues['completiondelay'] != '') {
            $defaultvalues['completiondelayenabled'] = true;
        }

        parent::set_data($defaultvalues);
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Override the validation made by moodleform_mod
        if (array_key_exists('completion', $data) && $data['completion'] == COMPLETION_TRACKING_AUTOMATIC && !empty($data['completionunlocked'])) {
            unset($errors['completion']);

            if (empty($data['completionview']) && empty($data['completionpass']) && !$this->completion_rule_enabled($data)) {
                $errors['completion'] = get_string('badautocompletion', 'completion');
            }
        }

        if (!empty($data['selectedapprovers'])) {
            $selectedapprovers = explode(',', $data['selectedapprovers']);

            $systemapprovers = get_users_from_config(
                    get_config(null, 'facetoface_adminapprovers'),
                    'mod/facetoface:approveanyrequest'
            );

            $guest = guest_user();
            $usernamefields = get_all_user_name_fields(true, 'u');
            list($selectedsql, $selectedparam) = $DB->get_in_or_equal($selectedapprovers, SQL_PARAMS_NAMED);
            $selectedusers = $DB->get_records_sql("
                SELECT
                    u.id, {$usernamefields}, u.email
                FROM
                    {user} u
                WHERE
                    u.deleted = 0
                AND u.suspended = 0
                AND u.id != :guestid
                AND u.id $selectedsql
                ORDER BY
                    u.firstname,
                    u.lastname
            ", array_merge(array('guestid' => $guest->id), $selectedparam));

            $exists = array();
            $suberrors = array();
            foreach ($selectedapprovers as $selected) {
                // Check for non-guest active users.
                if (!isset($selectedusers[$selected])) {
                    $suberrors[] = html_writer::tag('li', get_string('error:approverinactive', 'facetoface', $selected));
                    continue;
                }
                $username = fullname($selectedusers[$selected]);

                // Check for duplicates.
                if (isset($exists[$selected])) {
                    $suberrors[] = html_writer::tag('li', get_string('error:approverselected', 'facetoface', $username));
                    continue;
                }
                $exists[$selected] = 1;

                // Check for system wide approvers.
                if (isset($systemapprovers[$selected])) {
                    $suberrors[] = html_writer::tag('li', get_string('error:approversystem', 'facetoface', $username));
                    continue;
                }
            }

            if (!empty($suberrors)) {
                $errors['approvaloptions'] = html_writer::tag('ul', implode("\n", $suberrors));
            }
        }

        // If reservedays set, ensure the reservation deadline is greater than the cancellation days.
        if (!empty($data['reservecancel']) && $data['reservedays'] <= $data['reservecanceldays']) {
            $errors['reservedays'] = get_string('reservegtcancel', 'mod_facetoface');
        }

        return $errors;
    }
}
