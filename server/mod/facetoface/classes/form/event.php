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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

global $CFG;

use mod_facetoface\attendees_helper;
use mod_facetoface\facilitator_helper;
use mod_facetoface\notice_sender;
use mod_facetoface\room_helper;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar;
use mod_facetoface\signup\state\{booked, requested, requestedadmin, requestedrole, waitlisted};

require_once("{$CFG->libdir}/formslib.php");
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

class event extends \moodleform {

    /** @var \stdClass A record from the facetoface_sessions table */
    protected $session;

    /** @var \stdClass A record from the facetoface table */
    protected $facetoface;

    /** @var \context_module */
    protected $context;

    /** @var array */
    protected $editoroptions;

    /** @var \stdClass Data for a form sumbission*/
    protected $fromform;

    /** @var \moodle_url */
    protected $returnurl;

    /**
     * This is an array that holding the confliting users, including the event role and the
     * attendees of an event.
     *
     * @var \stdClass[]
     */
    protected $users_roles_in_conflict;

    /** @var bool */
    Protected $has_date_changed;

    function definition() {

        $mform =& $this->_form;
        $this->session = (isset($this->_customdata['session'])) ? $this->_customdata['session'] : false;
        $this->facetoface = $this->_customdata['facetoface'];
        $this->editoroptions = $this->_customdata['editoroptions'];
        $sessiondata = $this->_customdata['sessiondata'];
        $seminar = new seminar($this->_customdata['f']);
        $this->context = \context_module::instance($this->_customdata['cm']->id);
        if ($this->_customdata['backtoallsessions']) {
            $this->returnurl = new \moodle_url('/mod/facetoface/view.php', array('f' => $this->facetoface->id));
        } else {
            $this->returnurl = new \moodle_url('/course/view.php', array('id' => $this->_customdata['course']->id));
        }

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->addElement('hidden', 'f', $this->_customdata['f']);
        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->addElement('hidden', 'c', $this->_customdata['c']);
        $mform->setType('id', PARAM_INT);
        $mform->setType('f', PARAM_INT);
        $mform->setType('s', PARAM_INT);
        $mform->setType('c', PARAM_INT);
        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_BOOL);
        $mform->addElement('hidden', 'backtoevent', $this->_customdata['backtoevent']);
        $mform->setType('backtoevent', PARAM_BOOL);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        self::add_date_render_fields($this, $this->_customdata['defaulttimezone'], $this->_customdata['s'], $sessiondata);

        $mform->addElement('date_time_selector', 'registrationtimestart', get_string('registrationtimestart', 'facetoface'), array('optional' => true, 'showtimezone' => true));
        $mform->addHelpButton('registrationtimestart', 'registrationtimestart', 'facetoface');
        $mform->addElement('date_time_selector', 'registrationtimefinish', get_string('registrationtimefinish', 'facetoface'), array('optional' => true, 'showtimezone' => true));
        $mform->addHelpButton('registrationtimefinish', 'registrationtimefinish', 'facetoface');

        $mform->addElement('text', 'capacity', get_string('maxbookings', 'facetoface'), array('size' => 5));
        $mform->addRule('capacity', null, 'required', null, 'client');
        $mform->setType('capacity', PARAM_INT);
        $mform->setDefault('capacity', 10);
        $mform->addRule('capacity', null, 'numeric', null, 'client');
        $mform->addHelpButton('capacity', 'maxbookings', 'facetoface');

        $mform->addElement('checkbox', 'allowoverbook', get_string('allowoverbook', 'facetoface'));
        $mform->addHelpButton('allowoverbook', 'allowoverbook', 'facetoface');

        if (has_capability('mod/facetoface:configurecancellation', $this->context)) {
            // User cancellation settings.
            $radioarray = array();
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationanytime', 'facetoface'), 1);
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationnever', 'facetoface'), 0);
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationcutoff', 'facetoface'), 2);
            $mform->addGroup($radioarray, 'allowcancellations', get_string('allowbookingscancellations', 'facetoface'), array('<br/>'), false);
            $mform->setType('allowcancellations', PARAM_INT);
            $mform->addHelpButton('allowcancellations', 'allowbookingscancellations', 'facetoface');

            // Cancellation cutoff.
            $cutoffnotegroup = array();
            $cutoffnotegroup[] =& $mform->createElement('duration', 'cancellationcutoff', '', array('defaultunit' => HOURSECS, 'optional' => false));
            $cutoffnotegroup[] =& $mform->createElement('static', 'cutoffnote', null, get_string('cutoffnote', 'facetoface'));
            $mform->addGroup($cutoffnotegroup, 'cutoffgroup', '', '&nbsp;', false);
            $mform->disabledIf('cancellationcutoff[number]', 'allowcancellations', 'notchecked', 2);
            $mform->disabledIf('cancellationcutoff[timeunit]', 'allowcancellations', 'notchecked', 2);
        }

        $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
        if ($facetoface_allowwaitlisteveryone) {
            $mform->addElement('checkbox', 'waitlisteveryone', get_string('waitlisteveryone', 'facetoface'));
            $mform->addHelpButton('waitlisteveryone', 'waitlisteveryone', 'facetoface');
        }

        // Show minimum bookings and cut-off (for when this should be reached).
        $mform->addElement('text', 'mincapacity', get_string('minbookings', 'facetoface'), array('size' => 5));
        $mform->setType('mincapacity', PARAM_INT);
        $mform->setDefault('mincapacity', get_config('facetoface', 'defaultminbookings'));
        $mform->addRule('mincapacity', null, 'numeric', null, 'client');
        $mform->addHelpButton('mincapacity', 'mincapacity', 'facetoface');

        if (facetoface_site_allows_legacy_notifications()) {
            $cutoffdurationgroup = array();
            $cutoffdurationgroup[] =& $mform->createElement('checkbox', 'sendcapacityemail', '');
            $cutoffdurationgroup[] =& $mform->createElement('duration', 'cutoff', '', array('defaultunit' => HOURSECS, 'optional' => false));
            $cutoffdurationgroup[] =& $mform->createElement('static', 'cutoffnote', null, get_string('cutoffnote', 'facetoface'));
            $mform->addGroup($cutoffdurationgroup, 'cutoffdurationgroup', get_string('enablemincapacitynotification', 'facetoface'), '&nbsp;', false);

            $mform->setDefault('sendcapacityemail', 0);
            $mform->addHelpButton('cutoffdurationgroup', 'enablemincapacitynotification', 'facetoface');

            $mform->setType('cutoff', PARAM_INT);
            $mform->disabledIf('cutoff[number]', 'sendcapacityemail');
            $mform->disabledIf('cutoff[timeunit]', 'sendcapacityemail');
        }


        if (!get_config(NULL, 'facetoface_hidecost')) {
            $formarray  = array();
            $formarray[] = $mform->createElement('text', 'normalcost', get_string('normalcost', 'facetoface'), 'size="5"');
            $formarray[] = $mform->createElement('static', 'normalcosthint', '', \html_writer::tag('span', get_string('normalcosthinttext','facetoface'), array('class' => 'hint-text')));
            $mform->addGroup($formarray,'normalcost_group', get_string('normalcost','facetoface'), array(' '),false);
            $mform->setType('normalcost', PARAM_TEXT);
            $mform->addHelpButton('normalcost_group', 'normalcost', 'facetoface');

            if (!get_config(NULL, 'facetoface_hidediscount')) {
                $formarray  = array();
                $formarray[] = $mform->createElement('text', 'discountcost', get_string('discountcost', 'facetoface'), 'size="5"');
                $formarray[] = $mform->createElement('static', 'discountcosthint', '', \html_writer::tag('span', get_string('discountcosthinttext','facetoface'), array('class' => 'hint-text')));
                $mform->addGroup($formarray,'discountcost_group', get_string('discountcost','facetoface'), array(' '),false);
                $mform->setType('discountcost', PARAM_TEXT);
                $mform->addHelpButton('discountcost_group', 'discountcost', 'facetoface');
            }
        }

        $mform->addElement('editor', 'details_editor', get_string('details', 'facetoface'), null, $this->editoroptions);
        $mform->setType('details_editor', PARAM_RAW);
        $mform->addHelpButton('details_editor', 'details', 'facetoface');

        // Choose users for trainer roles
        $roles = \mod_facetoface\trainer_helper::get_trainer_roles($this->context);

        if ($roles) {
            $trainerhelper = new \mod_facetoface\trainer_helper(new \mod_facetoface\seminar_event($this->_customdata['s']));

            // Get current trainers
            $current_trainers = $trainerhelper->get_trainers();
            // Get course context and roles
            $rolenames = role_get_names($this->context);
            // Loop through all selected roles
            $header_shown = false;
            foreach ($roles as $role) {
                $rolename = $rolenames[$role->id]->localname;

                // Attempt to load users with this role in this context.
                $usernamefields = get_all_user_name_fields(true, 'u');
                $rs = get_role_users($role->id, $this->context, true, "u.id, {$usernamefields}", 'u.id ASC');

                if (!$rs) {
                    continue;
                }

                $choices = array();
                foreach ($rs as $roleuser) {
                    $choices[$roleuser->id] = fullname($roleuser);
                }

                // Show header (if haven't already)
                if ($choices && !$header_shown) {
                    $mform->addElement('header', 'trainerroles', get_string('sessionroles', 'facetoface'));
                    $mform->addHelpButton('trainerroles', 'sessionroles', 'facetoface');
                    $mform->addElement('static', 'roleapprovalerror');
                    $header_shown = true;
                }

                // If only a few, use checkboxes
                if (count($choices) < 4) {
                    $role_shown = false;
                    foreach ($choices as $cid => $choice) {
                        // Only display the role title for the first checkbox for each role
                        if (!$role_shown) {
                            $roledisplay = $rolename;
                            $role_shown = true;
                        } else {
                            $roledisplay = '';
                        }

                        $mform->addElement('advcheckbox', 'trainerrole['.$role->id.']['.$cid.']', $roledisplay, $choice, null, array('', $cid));
                        $mform->setType('trainerrole['.$role->id.']['.$cid.']', PARAM_INT);
                    }
                } else {
                    $choices = array(0 => get_string('none', 'facetoface')) + $choices;
                    $mform->addElement('select', 'trainerrole['.$role->id.']', $rolename, $choices, array('multiple' => 'multiple'));
                    $mform->setType('trainerrole['.$role->id.']', PARAM_SEQUENCE);
                }

                // Select current trainers
                if ($current_trainers) {
                    foreach ($current_trainers as $roleid => $trainers) {
                        $t = array();
                        foreach ($trainers as $trainer) {
                            $t[] = $trainer->id;
                            $mform->setDefault('trainerrole['.$roleid.']['.$trainer->id.']', $trainer->id);
                        }

                        $mform->setDefault('trainerrole['.$roleid.']', implode(',', $t));
                    }
                }
            }
        }

        // Show all custom fields. Customfield support.
        if (!$this->session) {
            $this->session = new \stdClass();
        }
        if (empty($this->session->id)) {
            $this->session->id = 0;
        }
        customfield_definition($mform, $this->session, 'facetofacesession', 0, 'facetoface_session');

        $this->add_action_buttons();

        $this->set_data($sessiondata);
    }

    /**
     * Adds html hidden fields and html rendered table to display in session date form
     * @param \moodleform $form Form where to add fields and set values
     * @param string $defaulttimezone
     * @param int $sessionid
     * @param \stdClass $sessiondata
     */
    public static function add_date_render_fields($form, $defaulttimezone, $sessionid, $sessiondata) {
        $mform = $form->_form;

        $mform->addElement('hidden', "cntdates");
        $mform->setType("cntdates", PARAM_INT);

        $table = new \html_table();
        $table->attributes['class'] = 'generaltable fullwidth f2fmanagedates';
        $table->head = array(
            get_string('dateandtime', 'facetoface'),
            get_string('rooms', 'mod_facetoface'),
            get_string('facilitators', 'mod_facetoface'),
            get_string('assets', 'facetoface'),
            ''
        );
        $table->data = array();

        $mform->addElement('static', 'errors');

        for ($i = 0; $i < $sessiondata->cntdates; $i++) {
            $row = self::date_render_mixin($mform, $i, $sessiondata, $defaulttimezone);
            $table->data[] = $row;
        }
        $dateshtmlcontent = \html_writer::table($table);

        // Render this content hidden. Then it will be displayed by js during init.
        $html = \html_writer::div($dateshtmlcontent, 'sessiondates hidden', array('id'=>'sessiondates_' . $sessionid));
        $mform->addElement('static', 'sessiondates', get_string('sessiondates', 'facetoface'), $html)->set_allow_xss(true);
        $mform->addElement('submit','date_add_fields', get_string('dateadd', 'facetoface'));
        $mform->registerNoSubmitButton('date_add_fields');
    }

    /**
     * Returns fields and html code required for one date (or new date if no session data provided)
     * Used also to dynamically inject new or cloned session date (event)
     * @param \MoodleQuickForm $mform
     * @param int $offset
     * @param \stdClass $sessiondata
     * @param string $defaulttimezone Default timezone if date not set
     * @return
     */
    public static function date_render_mixin($mform, $offset, $sessiondata, $defaulttimezone) {
        global $OUTPUT;

        $dateid   = !empty($sessiondata->{"sessiondateid[$offset]"}) ? $sessiondata->{"sessiondateid[$offset]"} : 0;
        $roomids  = !empty($sessiondata->{"roomids[$offset]"}) ? $sessiondata->{"roomids[$offset]"} : '';
        $assetids = !empty($sessiondata->{"assetids[$offset]"}) ? $sessiondata->{"assetids[$offset]"} : '';
        $facilitatorids = !empty($sessiondata->{"facilitatorids[$offset]"}) ? $sessiondata->{"facilitatorids[$offset]"} : '';

        // Add per-date form elements.
        // Clonable fields also must be listed in session.js.
        $mform->addElement('hidden', "sessiondateid[$offset]", $dateid);
        $mform->setType("sessiondateid[$offset]", PARAM_INT);
        $mform->addElement('hidden', "roomcapacity[$offset]");
        $mform->setType("roomcapacity[$offset]", PARAM_INT);
        $mform->addElement('hidden', "roomids[$offset]", $roomids);
        $mform->setType("roomids[$offset]", PARAM_SEQUENCE);
        $mform->addElement('hidden', "facilitatorids[$offset]", $facilitatorids);
        $mform->setType("facilitatorids[$offset]", PARAM_SEQUENCE);
        $mform->addElement('hidden', "assetids[$offset]", $assetids);
        $mform->setType("assetids[$offset]", PARAM_SEQUENCE);
        $mform->addElement('hidden', "timestart[$offset]");
        $mform->setType("timestart[$offset]", PARAM_INT);
        $mform->addElement('hidden', "timefinish[$offset]");
        $mform->setType("timefinish[$offset]", PARAM_INT);
        $mform->addElement('hidden', "sessiontimezone[$offset]");
        $mform->setType("sessiontimezone[$offset]", PARAM_TIMEZONE);
        $mform->addElement('hidden', "datedelete[$offset]");
        $mform->setType("datedelete[$offset]", PARAM_INT);

        $row = array();
        $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

        // Dates.
        if (empty($sessiondata->{"timestart[$offset]"})
            || empty($sessiondata->{"timefinish[$offset]"})
            || empty($sessiondata->{"sessiontimezone[$offset]"})) {
            list($timestart, $timefinish) = \mod_facetoface\event_dates::get_default();
            $sessiontimezone = $defaulttimezone;
        } else {
            $timestart = $sessiondata->{"timestart[$offset]"};
            $timefinish = $sessiondata->{"timefinish[$offset]"};
            $sessiontimezone = $sessiondata->{"sessiontimezone[$offset]"};
        }

        $mform->setDefault("timestart[$offset]", $timestart);
        $mform->setDefault("timefinish[$offset]", $timefinish);
        $mform->setDefault("sessiontimezone[$offset]", $sessiontimezone);

        $dateshtml = \mod_facetoface\event_dates::render(
            $timestart,
            $timefinish,
            $sessiontimezone,
            $displaytimezones
        );

        $strcopy = get_string('copy');
        $strdelete = get_string('delete');
        $streditdate = get_string('editdate', 'facetoface');

        $lockicon = $OUTPUT->render(new \core\output\flex_icon('lock', ['title' => get_string('virtual_meeting_date_locked', 'mod_facetoface')]));
        $lockspan = \html_writer::span($lockicon, 'mod_facetoface-date-lock');
        $editicon = $OUTPUT->action_icon('#', new \pix_icon('t/edit', $streditdate), null,
            array(
                'id' => "show-selectdate{$offset}-dialog",
                'class' => 'action-icon mod_facetoface-show-selectdate-dialog mod_facetoface-date-has-virtual-room',
                'data-offset' => $offset
            )
        );
        $row[] = $editicon . $lockspan . \html_writer::span($dateshtml, 'timeframe-text', array('id' => 'timeframe-text' . $offset));

        // Room.
        $selectrooms = \html_writer::link("#", get_string('selectrooms', 'facetoface'), array(
            'id' => "show-selectrooms{$offset}-dialog",
            'class' => 'show-selectrooms-dialog',
            'data-offset' => $offset
        ));

        // Room names and capacity will be loaded by js.
        $row[] =  \html_writer::tag('ul', '', array(
                'id' => 'roomlist' . $offset,
                'class' => 'mod_facetoface-roomlist nonempty',
                'data-offset' => $offset
            )) . $selectrooms;

        // Facilitators.
        $selectfacilitators = \html_writer::link("#", get_string('selectfacilitators', 'mod_facetoface'), array(
            'id' => "show-selectfacilitators{$offset}-dialog",
            'class' => 'show-selectfacilitators-dialog',
            'data-offset' => $offset
        ));

        // Facilitators items will be loaded by js.
        $row[] =  \html_writer::tag('ul', '', array(
                'id' => 'facilitatorlist' . $offset,
                'class' => 'mod_facetoface-facilitatorlist nonempty',
                'data-offset' => $offset
            )) . $selectfacilitators;

        // Assets.
        $selectassets = \html_writer::link("#", get_string('selectassets', 'facetoface'), array(
            'id' => "show-selectassets{$offset}-dialog",
            'class' => 'show-selectassets-dialog',
            'data-offset' => $offset
        ));

        // Assets items will be loaded by js.
        $row[] =  \html_writer::tag('ul', '', array(
                'id' => 'assetlist' . $offset,
                'class' => 'mod_facetoface-assetlist nonempty',
                'data-offset' => $offset
            )) . $selectassets;

        // Options.
        $cloneicon = $OUTPUT->action_icon('#', new \pix_icon('t/copy', $strcopy), null,
            array('class' => 'action-icon dateclone', 'data-offset' => $offset, 'data-action' => 'clonedate'));
        $deleteicon = $OUTPUT->action_icon('#', new \pix_icon('t/delete', $strdelete), null,
            array('class' => 'action-icon dateremove', 'data-offset' => $offset, 'data-action' => 'removedate'));
        $row[] = $cloneicon . $deleteicon;

        return $row;
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $facetofaceid = $this->_customdata['f'];
        $session = $this->session;
        $dates = [];
        $dateids = isset($data['sessiondateid']) ? $data['sessiondateid'] : [];
        $datecount = count($dateids);
        $deletecount = 0;
        $errdates = [];
        $users_in_conflict = [];

        for ($i=0; $i < $datecount; $i++) {
            if (!empty($data['datedelete'][$i])) {
                // Ignore dates marked for deletion.
                $deletecount++;
                continue;
            }

            $starttime = $data["timestart"][$i];
            $endtime = $data["timefinish"][$i];
            $roomids = $data["roomids"][$i];
            $roomlist = [];
            $assetids = $data["assetids"][$i];
            $assetlist = [];
            $facilitatorids = $data["facilitatorids"][$i];
            $facilitatorlist = [];

            if (!empty($roomids)) {
                $roomlist = explode(',', $roomids);

                // Verify the date only has 1 virtual meeting associated with it.
                $vmcount = 0;
                foreach ($roomlist as $roomid) {
                    $room = new \mod_facetoface\room($roomid);
                    $vmwhitelist = [
                        room_virtualmeeting::VIRTUAL_MEETING_NONE,
                        room_virtualmeeting::VIRTUAL_MEETING_INTERNAL
                    ];

                    $virtualmeeting = room_virtualmeeting::from_roomid($roomid);
                    if ($virtualmeeting->exists() && !in_array($virtualmeeting->get_plugin(), $vmwhitelist) && ++$vmcount > 1) {
                        $errdates['virtualmeetingmax'] = get_string('error:toomanyvirtualmeetings', 'facetoface');
                    }
                }
            }

            if (!empty($assetids)) {
                $assetlist = explode(',', $assetids);
            }
            if (!empty($facilitatorids)) {
                $facilitatorlist = explode(',', $facilitatorids);
            }
            // If event is a cloning then remove session id and behave as a new event to get rooms availability.
            $sessid = ($data['c'] ? 0 : $data['s']);

            $errdate = \mod_facetoface\event_dates::validate(
                $starttime, $endtime, $roomlist, $assetlist, $sessid, $facetofaceid, $facilitatorlist
            );

            if (!empty($errdate['timestart'])) {
                $errdates[] = $errdate['timestart'];
            }
            if (!empty($errdate['timefinish'])) {
                $errdates[] = $errdate['timefinish'];
            }
            if (!empty($errdate['roomids'])) {
                $errdates[] = $errdate['roomids'];
            }
            if (!empty($errdate['assetids'])) {
                $errdates[] = $errdate['assetids'];
            }
            if (!empty($errdate['facilitatorids'])) {
                $errdates[] = $errdate['facilitatorids'];
            }

            //Check this date does not overlap with any previous dates - time overlap logic from a Stack Overflow post
            if (!empty($dates)) {
                foreach ($dates as $existing) {
                    if (($endtime > $existing->timestart) && ($existing->timefinish > $starttime) ||
                        ($endtime == $existing->timefinish) || ($starttime == $existing->timestart)) {
                        // This date clashes with an existing date - either they overlap or
                        // one of them is zero minutes and they start at the same time or end at the same time.
                        $messageconflictsamedate = get_string('error:sessiondatesconflict', 'facetoface');
                        if (!in_array($messageconflictsamedate, $errdates)) {
                            $errdates[] = $messageconflictsamedate;
                            break;
                        }
                    }
                }
            }

            // Registration cannot open once session has started.
            if (!empty($data['registrationtimestart'])) {
                if ($data['registrationtimestart'] >= $starttime) {
                    $errors['registrationtimestart'] = get_string('registrationstartsession', 'facetoface');
                }
            }

            // Registration close date must be on or before session has started.
            if (!empty($data['registrationtimefinish'])) {
                if ($data['registrationtimefinish'] > $starttime) {
                    $errors['registrationtimefinish'] = get_string('registrationfinishsession', 'facetoface');
                }
            }

            // If valid date, add to array.
            $date = new \stdClass();
            $date->timestart = $starttime;
            $date->timefinish = $endtime;
            $dates[] = $date;
        }

        if (isset($this->_customdata['session']) && isset($this->_customdata['session']->sessiondates) && count($dates) === count($this->_customdata['session']->sessiondates)) {
            // Its an existing session with the same number of dates, we are going to need to check if the session dates have been changed.
            $dateschanged = false;
            foreach ($dates as $date) {
                // We need to find each submit date.
                // If all are found then this submit date has not changed.
                $datefound = false;
                foreach ($this->_customdata['session']->sessiondates as $originaldate) {
                    if ($date->timestart == $originaldate->timestart && $date->timefinish == $originaldate->timefinish) {
                        // We've found the date.
                        $datefound = true;
                        break;
                    }
                }
                // If we didn't find the date, then we know they have changed.
                if (!$datefound) {
                    $dateschanged = true;
                    break;
                }
            }

        } else {
            // There are no previous session dates, or the number of session dates has changed.
            // Because of this we treat the dates as having changed.
            $dateschanged = true;
        }
        $this->has_date_changed = $dateschanged;

        if(!empty($data['registrationtimestart']) && !empty($data['registrationtimefinish'])) {
            $start = $data['registrationtimestart'];
            $finish = $data['registrationtimefinish'];
            if ($start >= $finish) {
                // Registration opening time cannot be after registration close time.
                $errors['registrationtimestart'] = get_string('registrationerrorstartfinish', 'facetoface');
                $errors['registrationtimefinish'] = get_string('registrationerrorstartfinish', 'facetoface');
            }
        }

        $seminarevent = null;
        if (!empty($session)) {
            $seminarevent = new \mod_facetoface\seminar_event($session->id);
        }

        // Check approval by role.
        $trainerdata = !empty($data['trainerrole']) ? $data['trainerrole'] : array();
        if ($dates && is_array($trainerdata)) {
            // Seminar approval by role is set, required at least one role selected.
            $selectedroleids = array();

            $usernamefields = get_all_user_name_fields(true, 'u');
            // Query to load users with roles the seminar context.
            // Loop through roles.
            foreach ($trainerdata as $roleid => $trainers) {
                // Attempt to load users with this role in this context.
                $trainerlist = get_role_users(
                    $roleid,
                    $this->context,
                    true,
                    "u.id, {$usernamefields}",
                    'u.id ASC'
                );

                foreach ($trainers as $trainer) {
                    // Skip not selected trainers.
                    if (!$trainer) {
                        continue;
                    }

                    $selectedroleids[] = $roleid;

                    // Check their availability.
                    $sessiondates = \mod_facetoface\seminar_session_list::from_user_conflicts_with_dates($trainer, $dates, $seminarevent);
                    if (!$sessiondates->is_empty()) {
                        $users_in_conflict[] = $trainerlist[$trainer];
                    }
                }
            }

            $seminar = new \mod_facetoface\seminar($facetofaceid);

            // Check if default role approval is selected.
            if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ROLE &&
                !in_array($seminar->get_approvalrole(), $selectedroleids)) {
                $rolenames = role_get_names($this->context);
                $errors['roleapprovalerror'] = get_string(
                    'error:rolerequired',
                    'facetoface',
                    $rolenames[$seminar->get_approvalrole()]->localname
                );
            }
        }

        //check capcity is a number
        if (empty($data['capacity'])) {
            $errors['capacity'] = get_string('error:capacityzero', 'facetoface');
        } else {
            $capacity = $data['capacity'];
            if (!(is_numeric($capacity) && (intval($capacity) == $capacity) && $capacity > 0)) {
                $errors['capacity'] = get_string('error:capacitynotnumeric', 'facetoface');
            }
        }

        // Check the minimum bookings.
        $mincapacity = $data['mincapacity'];
        if (!is_numeric($mincapacity) || (intval($mincapacity) != $mincapacity)) {
            $errors['mincapacity'] = get_string('error:mincapacitynotnumeric', 'facetoface');
        } else if ($mincapacity > $data['capacity']) {
            $errors['mincapacity'] = get_string('error:mincapacitytoolarge', 'facetoface');
        }

        // Check the cut-off is at least the day before the earliest start time.
        if (!empty($data['sendcapacityemail'])) {
            // If the cutoff or the dates have changed check the cut-off is at least the day before the earliest start time.
            // We only want to run this validation if the cutoff period has changed, or if the dates have changed.
            $cutoff = $data['cutoff'];
            if (!isset($this->_customdata['session']->cutoff) || $this->_customdata['session']->cutoff != $cutoff || $dateschanged) {
                if ($cutoff < DAYSECS) {
                    $errors['cutoffdurationgroup'] = get_string('error:cutofftooclose', 'facetoface');
                } else {
                    $now = time();
                    foreach ($dates as $dateid => $date) {
                        $cutofftimestamp = $date->timestart - $cutoff;
                        if ($cutofftimestamp < $now) {
                            $errors['cutoffdurationgroup'] = get_string('error:cutofftoolate', 'facetoface');
                            break;
                        }
                    }
                }
            }
        }

        // Check that there is not booking conflicts for current attendees.
        if ($dates && null !== $seminarevent && $seminarevent->exists()) {
            // No point to check the confliction of event attendees, if the seminar event is not defined yet nor
            // seminar event is not existing in the database storage yet.
            $helper = new attendees_helper($seminarevent);
            $statuscodes = [booked::get_code(), waitlisted::get_code()];
            $currentattendees = $helper->get_attendees_with_codes($statuscodes);

            foreach ($currentattendees as $attendee) {
                $sessiondates = \mod_facetoface\seminar_session_list::from_user_conflicts_with_dates($attendee->id, $dates, $seminarevent);
                if (!$sessiondates->is_empty()) {
                    $users_in_conflict[$attendee->id] = $attendee;
                }
            }
        }

        // Process the data for a custom field and validate it.
        $errors += customfield_validation((object)$data, 'facetofacesession', 'facetoface_session');

        // Consolidate date errors.
        if (!empty($errdates)) {
            $errors['errors'] = implode(\html_writer::empty_tag('br'), $errdates);
        }
        $this->users_roles_in_conflict = $users_in_conflict;

        return $errors;
    }

    /**
     * The function will calculate which user is in conflicting based on the scenario's parameters.
     * For example, if the form does not change date then there are no conflicting. Furthermore,
     * if the event does not have any session, then there should have no conflicting.
     *
     * @return array
     */
    function get_users_in_conflict() {
        if (!($data = $this->get_data())) {
            // Only check the conflicts if the form is already submitted
            return [];
        }

        if ($this->session != false && $this->has_date_changed == false) {
            // Check for conflicts only if it's a new session or there was a change in dates or
            // user with roles were added.
            return [];
        } else if ($this->_customdata['savewithconflicts']) {
            // If save with conflict passed then we don't need to return the users with conflicts.
            return [];
        }

        return $this->users_roles_in_conflict;
    }

    /**
     * Build user roles in conflict message, used when saving an event.
     *
     * @return string Message
     */
    public function get_conflict_message() {

        if (empty($this->users_roles_in_conflict)) {
            return '';
        }

        foreach ($this->users_roles_in_conflict as $user) {
            if (property_exists($user, "name")) {
                // Indicating that the $user was already had the attribute 'name' built.
                $users[] = $user->name;
                continue;
            }
            $users[] = fullname($user);
        }
        $details = new \stdClass();
        $details->users = implode('; ', $users);
        $details->userscount = count($this->users_roles_in_conflict);

        return format_text(get_string('userschedulingconflictdetected_body', 'facetoface', $details));
    }

    /**
     * Prepare form data
     *
     * @param \stdClass $session Facetoface session
     * @param \stdClass $facetoface Facetoface instance
     * @param \stdClass $course Course
     * @param \context $context Context
     * @param int $cntdates Dates count
     * @param bool $clone A flag whether the session is being cloned from another session
     * @return array Prepared form data
     * @throws \coding_exception
     */
    public static function prepare_data($session, $facetoface, $course, $context, $cntdates, $clone = false) {
        global $TEXTAREA_OPTIONS;

        $defaulttimezone = '99';

        $editoroptions   = array(
            'noclean'  => false,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $course->maxbytes,
            'context'  => $context,
        );

        if (!isset($session)) {
            $sessiondata = new \stdClass();
            $sessiondata->id = 0;
            $sessiondata->allowcancellations = $facetoface->allowcancellationsdefault;
            $sessiondata->cancellationcutoff = $facetoface->cancellationscutoffdefault;
            $sessiondata->cntdates = $cntdates;
            $nbdays = 1;
        } else {
            // Load custom fields data for the session.
            customfield_load_data($session, 'facetofacesession', 'facetoface_session');

            // Set values for the form and unset some values that will be evaluated later.
            $sessiondata = clone($session);
            if (isset($sessiondata->sessiondates)) {
                unset($sessiondata->sessiondates);
            }

            $editoroptions = $TEXTAREA_OPTIONS;
            $editoroptions['context'] = $context;
            $sessiondata->detailsformat = FORMAT_HTML;
            $sessiondata = file_prepare_standard_editor($sessiondata, 'details', $editoroptions, $editoroptions['context'],
                'mod_facetoface', 'session', $session->id);

            // Let form know how many dates to process.
            if ($cntdates > $sessiondata->cntdates) {
                $sessiondata->cntdates = $cntdates;
            }

            $nbdays = count($session->sessiondates);
            if ($session->sessiondates) {
                $i = 0;
                foreach ($session->sessiondates as $date) {
                    $idfield = "sessiondateid[$i]";
                    $timestartfield = "timestart[$i]";
                    $timefinishfield = "timefinish[$i]";
                    $timezonefield = "sessiontimezone[$i]";
                    $roomsfield = "roomids[$i]";
                    $assetsfield = "assetids[$i]";
                    $facilitatorsfield = "facilitatorids[$i]";

                    if ($date->sessiontimezone === '') {
                        $date->sessiontimezone = '99';
                    } else if ($date->sessiontimezone != 99) {
                        $date->sessiontimezone = \core_date::normalise_timezone($date->sessiontimezone);
                    }

                    if(!$clone) {
                        $sessiondata->$idfield = $date->id;
                    }

                    $sessiondata->$timestartfield = $date->timestart;
                    $sessiondata->$timefinishfield = $date->timefinish;
                    $sessiondata->$timezonefield = $date->sessiontimezone;
                    $sessiondata->$roomsfield = \mod_facetoface\room_helper::get_session_roomids($date->id);
                    $sessiondata->$assetsfield = \mod_facetoface\asset_helper::get_session_assetids($date->id);
                    $sessiondata->$facilitatorsfield = \mod_facetoface\facilitator_helper::get_session_facilitatorids($date->id);

                    // NOTE: There is no need to remove rooms and assets
                    //       because form validation will not allow saving
                    //       and likely they will just change the date.

                    $i++;
                }
            }
        }
        return array($sessiondata, $editoroptions, $defaulttimezone, $nbdays);
    }

    /**
     * @param $data
     * @return bool
     *
     * @deprecated Since totara 13
     */
    public function new_user_roles_added($data) {

        debugging("mod_facetoface\\form\\event::new_user_roles_added() function has been deprecated as unused", DEBUG_DEVELOPER);

        global $DB;
        $trainersindb = array();
        $newusersadded = false;

        // Get data trainers from the form.
        $trainerdatafromform = !empty($data->trainerrole) ? $data->trainerrole : array();
        // Get user roles from database.
        $userroles = $DB->get_records('facetoface_session_roles', array('sessionid' => $data->s));
        foreach ($userroles as $userrole) {
            $trainersindb[$userrole->roleid][$userrole->userid] = 0;
        }

        foreach ($trainerdatafromform as $roleid => $trainers) {
            foreach ($trainers as $trainerid => $selected) {
                // Exclude not selected trainers.
                if (!$selected) {
                    continue;
                }
                // Check if the selected user is not already assigned.
                if (!isset($trainersindb[$roleid])) {
                    // No records in DB for this role. New users added to the role.
                    $newusersadded = true;
                    break;
                } else if (!array_key_exists($trainerid, $trainersindb[$roleid])) {
                    // The selected user cannot be found for the current role in DB.
                    $newusersadded = true;
                    break;
                }
            }
        }

        return $newusersadded;
    }

    public function process_data() {
        global $USER;

        if (!($fromform = $this->get_data())) { // Form submitted
            return null;
        }

        $session = $this->session;
        $facetoface = $this->facetoface;

        // Make sure user cannot cancel this page request. (Back luck IIS users!)
        ignore_user_abort();

        if (empty($fromform->submitbutton)) {
            print_error('error:unknownbuttonclicked', 'facetoface', $this->returnurl);
        }

        // Pre-process fields
        if (empty($fromform->allowoverbook)) {
            $fromform->allowoverbook = 0;
        }
        if (empty($fromform->waitlisteveryone)) {
            $fromform->waitlisteveryone = 0;
        }
        if (empty($fromform->normalcost)) {
            $fromform->normalcost = 0;
        }
        if (empty($fromform->discountcost)) {
            $fromform->discountcost = 0;
        }
        if (empty($fromform->selfapproval)) {
            $fromform->selfapproval = 0;
        }
        if ($fromform->mincapacity < 0) {
            $fromform->mincapacity = 0;
        }
        if (empty($fromform->sendcapacityemail)) {
            $fromform->sendcapacityemail = 0;
        }
        if (empty($fromform->cutoff)) {
            $fromform->cutoff = 0;
        }

        $todb = new \stdClass();
        $todb->cutoff     = $fromform->cutoff;
        $todb->capacity   = $fromform->capacity;
        $todb->normalcost = $fromform->normalcost;
        $todb->facetoface = $facetoface->id;
        $todb->mincapacity = $fromform->mincapacity;
        $todb->discountcost  = $fromform->discountcost;
        $todb->usermodified  = $USER->id;
        $todb->allowoverbook = $fromform->allowoverbook;
        $todb->waitlisteveryone  = $fromform->waitlisteveryone;
        $todb->sendcapacityemail = $fromform->sendcapacityemail;
        $todb->registrationtimestart  = $fromform->registrationtimestart;
        $todb->registrationtimefinish = $fromform->registrationtimefinish;

        // Do not change cancellation here!
        unset($fromform->cancelledstatus);

        $canconfigurecancellation = has_capability('mod/facetoface:configurecancellation', $this->context);
        if ($canconfigurecancellation) {
            $todb->allowcancellations = $fromform->allowcancellations;
            $todb->cancellationcutoff = $fromform->cancellationcutoff;
        } else {
            if ((int)$session->id != 0) {
                $todb->allowcancellations = $session->allowcancellations;
                $todb->cancellationcutoff = $session->cancellationcutoff;
            } else {
                $todb->allowcancellations = $facetoface->allowcancellationsdefault;
                $todb->cancellationcutoff = $facetoface->cancellationscutoffdefault;
            }
        }

        $this->fromform = $fromform;
        return $todb;
    }

    public function save($todb) {
        global $DB;

        $session = $this->session;
        $facetoface = $this->facetoface;
        $fromform =& $this->fromform;

        //check dates and calculate total duration
        $sessiondates = array();
        for ($i = 0; $i < $fromform->cntdates; $i++) {
            if (!empty($fromform->datedelete[$i])) {
                continue; // skip this date
            }
            if (!empty($fromform->timestart[$i]) && !empty($fromform->timefinish[$i])) {

                $date = new \stdClass();

                $date->id = isset($fromform->sessiondateid[$i]) ? $fromform->sessiondateid[$i] : null;
                $date->sessiontimezone = $fromform->sessiontimezone[$i];
                $date->timestart  = $fromform->timestart[$i];
                $date->timefinish = $fromform->timefinish[$i];
                $date->roomids  = !empty($fromform->roomids[$i]) ? explode(',', $fromform->roomids[$i]) : array();
                $date->assetids = !empty($fromform->assetids[$i]) ? explode(',', $fromform->assetids[$i]) : array();
                $date->facilitatorids = !empty($fromform->facilitatorids[$i]) ? explode(',', $fromform->facilitatorids[$i]) : array();
                sort($date->roomids, SORT_NUMERIC);
                sort($date->facilitatorids, SORT_NUMERIC);
                $sessiondates[] = $date;
            }
        }

        $transaction = $DB->start_delegated_transaction();

        $update = false;
        // Cloning the session from the existing one.
        if (!$this->_customdata['c'] && (int)$session->id != 0) {
            $update = true;
            $todb->id  = $session->id;
            $sessionid = $session->id;
            $olddates  = $DB->get_records('facetoface_sessions_dates', array('sessionid' => $session->id), 'timestart');
            foreach ($olddates as &$olddate) {
                $olddate->roomids = room_helper::get_room_ids_sorted($olddate->id);
                $olddate->facilitatorids = facilitator_helper::get_facilitator_ids_sorted($olddate->id);
            }
        } else {
            // Create or Duplicate the session.
            $sessionid = 0;
        }
        try {
            $seminarevent = new \mod_facetoface\seminar_event($sessionid);
            $seminarevent->from_record($todb);
            $seminarevent->save();
            // This is where it calculates new, changed and old dates, and sends facilitator notifs.
            $session_changes = \mod_facetoface\seminar_event_helper::merge_sessions($seminarevent, $sessiondates);
        } catch (\moodle_exception $e) {
            print_error('error:couldnotsaveevent', 'facetoface', $this->returnurl);
        }

        // Record a list of booked users before we possibly put some people back onto the waitlist.
        // Note that requested roles are included out of caution, but shouldn't cause problems if they're not relevant.
        $helper = new attendees_helper($seminarevent);
        $previous_booked_attendee_ids = array_keys($helper->get_attendees_with_codes(
            [
                booked::get_code(),
                requested::get_code(),
                requestedadmin::get_code(),
                requestedrole::get_code()
            ]
        ));

        $fromform->id = $seminarevent->get_id();
        customfield_save_data($fromform, 'facetofacesession', 'facetoface_session');

        $transaction->allow_commit();

        if ($update) {
            // Now that we have updated the session record fetch the rest of the data we need.
            \mod_facetoface\signup_helper::update_attendees($seminarevent);
        }

        // Get details.
        // This should be done before sending any notification as it could be a required field in their template.
        $data = file_postupdate_standard_editor($fromform, 'details', $this->editoroptions, $this->context, 'mod_facetoface', 'session', $seminarevent->get_id());
        $session->details = $data->details;
        $DB->set_field('facetoface_sessions', 'details', $data->details, array('id' => $seminarevent->get_id()));

        $excludetrainers = [];
        $trainerhelper = new \mod_facetoface\trainer_helper($seminarevent);
        // Save trainer roles.
        if (isset($fromform->trainerrole)) {
            $trainerrole = $fromform->trainerrole;
            foreach ($trainerrole as $roleid => $trainers) {
                $added = $trainerhelper->add_trainers($roleid, $trainers);
                $excludetrainers = array_merge($excludetrainers, $added);
            }

            $trainerhelper->remove_trainers($excludetrainers);
        }

        \mod_facetoface\calendar::update_entries($seminarevent);

        if ($update) {
            // Send any necessary datetime change notifications but only if date/time is known.
            if (\mod_facetoface\seminar_session_list::dates_check($olddates, $sessiondates)) {
                $statuscodes = [booked::get_code(), waitlisted::get_code()];
                $attendees = $helper->get_attendees_with_codes($statuscodes);

                $sessions_cancelled = array_map(function ($session_resource) {
                    return $session_resource->get_session()->to_record();
                }, $session_changes['sessions_cancelled']);

                foreach ($attendees as $user) {
                    $signup = \mod_facetoface\signup::create($user->id, $seminarevent);
                    // Force ical attachment delivery when users were previously not waitlisted - they may now be waitlisted
                    // if the last session was removed from the event. Booked users always get them anyway.
                    $force_attachment = in_array($user->id, $previous_booked_attendee_ids);
                    notice_sender::signup_datetime_changed(
                        $signup,
                        $olddates,
                        true,
                        $force_attachment,
                        $sessions_cancelled
                    );
                }

                $attendees_requested = $helper->get_attendees_with_codes([requested::get_code()]);
                foreach ($attendees_requested as $user) {
                    $signup = \mod_facetoface\signup::create($user->id, $seminarevent);
                    // Triggered for centralised notifications only.
                    notice_sender::signup_datetime_changed(
                        $signup,
                        $olddates,
                        false,
                        false,
                        $sessions_cancelled
                    );
                }

                $sessiontrainers = $trainerhelper->get_trainers();
                if (!empty($sessiontrainers)) {
                    foreach ($sessiontrainers as $roleid => $trainers) {
                        foreach ($trainers as $trainer) {
                            if (!empty($trainer->id)) {
                                notice_sender::event_datetime_changed(
                                    $trainer->id,
                                    $seminarevent,
                                    $olddates,
                                    false,
                                    $sessions_cancelled
                                );
                            }
                        }
                    }
                }
            }
            \mod_facetoface\event\session_updated::create_from_session($session, $this->context)->trigger();
        } else {
            \mod_facetoface\event\session_created::create_from_session($session, $this->context)->trigger();
        }
    }
}
