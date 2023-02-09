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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */


namespace mod_facetoface\form;

/**
 * Form for choosing dates and associated information: room, and assets
 */
class event_date extends \moodleform {

    public function definition() {
        global $PAGE;
        $mform = $this->_form;

        $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

        $defaulttimezone = $this->_customdata['timezone'];
        $defaultstart = $this->_customdata['start'];
        $defaultfinish = $this->_customdata['finish'];

        $mform->addElement('hidden', 'sessiondateid', $this->_customdata['sessiondateid']);
        $mform->setType('sessiondateid', PARAM_INT);
        $mform->addElement('hidden', 'sessionid', $this->_customdata['sessionid']);
        $mform->setType('sessionid', PARAM_INT);
        $mform->addElement('hidden', 'roomids', $this->_customdata['roomids']);
        $mform->setType('roomids', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'facilitatorids', $this->_customdata['facilitatorids']);
        $mform->setType('facilitatorids', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'assetids', $this->_customdata['assetids']);
        $mform->setType('assetids', PARAM_SEQUENCE);

        $mform->addElement('static', 'dateunavailable', "");

        if ($displaytimezones) {
            $timezones = array('99' => get_string('timezoneuser', 'totara_core')) + \core_date::get_list_of_timezones();
            $mform->addElement('select', 'sessiontimezone', get_string('sessiontimezone', 'facetoface'), $timezones);
        } else {
            $mform->addElement('hidden', 'sessiontimezone', '99');
        }
        $mform->addHelpButton('sessiontimezone', 'sessiontimezone','facetoface');
        $mform->setDefault('sessiontimezone', $defaulttimezone);
        $mform->setType('sessiontimezone', PARAM_TIMEZONE);

        if (empty($defaultstart)) {
            throw new \coding_exception('Start and finish dates must be provided and cannot be empty.');
        }
        // NOTE: Do not set type for date elements because it borks timezones!
        $mform->addElement('date_time_selector', 'timestart', get_string('timestart', 'facetoface'), array('defaulttime' => $defaultstart, 'showtimezone' => true));
        $mform->addHelpButton('timestart', 'sessionstarttime', 'facetoface');
        $mform->setDefault('timestart', $defaultstart);
        $mform->addElement('date_time_selector', 'timefinish', get_string('timefinish', 'facetoface'), array('defaulttime' => $defaultfinish, 'showtimezone' => true));
        $mform->addHelpButton('timefinish', 'sessionfinishtime', 'facetoface');
        $mform->setDefault('timefinish', $defaultfinish);

        if ($displaytimezones) {
            $tz = $defaulttimezone;
            // Really nasty default timezone hackery.
            $el = $mform->getElement("timestart");
            $el->set_option('timezone', $tz);
            $el = $mform->getElement("timefinish");
            $el->set_option('timezone', $tz);
        }
        // Date selector put calendar above fields. And in dialog box it effectively pushes it over top of edge of screen.
        // It doesn't support position settings, so hack it's instance to put it in position below.
        // Better way is to fix dateselector form element allowing to choose position, but it will require changes in upstream code.
        $PAGE->requires->yui_module('moodle-form-dateselector', '
            (function() {
                M.form.dateselector.fix_position = function() {
                    if (this.currentowner) {
                        var alignpoints = [
                            Y.WidgetPositionAlign.TL,
                            Y.WidgetPositionAlign.BL
                        ];

                        // Change the alignment if this is an RTL language.
                        if (window.right_to_left()) {
                            alignpoints = [
                                Y.WidgetPositionAlign.TR,
                                Y.WidgetPositionAlign.BR
                            ];
                        }


                        this.panel.set(\'align\', {
                            node: this.currentowner.get(\'node\').one(\'select\'),
                            points: alignpoints
                        });
                    };
                };
            })');
    }

    function validation($data, $files) {

        $roomids = array();
        if (!empty($data['roomids'])) {
            $roomids = explode(',', $data['roomids']);
        }

        $facilitatorids = array();
        if (!empty($data['facilitatorids'])) {
            $facilitatorids = explode(',', $data['facilitatorids']);
        }

        $assetids = array();
        if (!empty($data['assetids'])) {
            $assetids = explode(',', $data['assetids']);
        }

        $facetofaceid = $this->_customdata['facetofaceid'];

        $errors = \mod_facetoface\event_dates::validate(
            $data['timestart'], $data['timefinish'], $roomids, $assetids, $data['sessionid'], $facetofaceid, $facilitatorids
        );

        return $errors;
    }
}