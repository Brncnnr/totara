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

namespace mod_facetoface;

global $CFG;
require_once($CFG->dirroot."/mod/facetoface/lib.php");

/**
 * Yet another helper class to format session times.
 */
class event_dates {
    /**
     * Get rendered start date, finish date and timestamp.
     * @param int $timestart start timestamp
     * @param int $timefinish finish timestamp
     * @param int|string|float|DateTimeZone $sesiontimezone
     * @param bool $displaytimezones should timezone be displayed
     * @return string
     */
    public static function render(int $timestart, int $timefinish, $sesiontimezone, bool $displaytimezones = true): string {
        $sessionobj = output\session_time::format(
            $timestart,
            $timefinish,
            $sesiontimezone,
            $displaytimezones
        );

        return get_string('sessiondatecolumn_html', 'facetoface', $sessionobj);
    }

    /**
     * Return default start and end date/time of session
     * @return int[] of [$defaultstart, $defaultfinish]
     */
    public static function get_default(): array {
        $config = get_config('facetoface');
        $now = time();
        $defaultstart = $now;

        if (!empty($config->defaultdaystosession)) {
            if (!empty($config->defaultdaysskipweekends)) {
                $defaultstart = strtotime("+{$config->defaultdaystosession} weekdays", $defaultstart);
            } else {
                $defaultstart = strtotime("+{$config->defaultdaystosession} days", $defaultstart);
            }
        }

        $defaultfinish = $defaultstart;

        if (!empty($config->defaultdaysbetweenstartfinish)) {
            $days = (int)$config->defaultdaysbetweenstartfinish;
            if (!empty($config->defaultdaysskipweekends)) {
                $defaultfinish = strtotime("+{$days} weekdays", $defaultfinish);
            } else {
                $defaultfinish = strtotime("+{$days} days", $defaultfinish);
            }
        }

        // Adjust for start time hours.
        if (!empty($config->defaultstarttime_hours)) {
            $defaultstart = strtotime(date('Y-m-d', $defaultstart).' 00:00:00');
            $defaultstart += HOURSECS * (int)$config->defaultstarttime_hours;
        }

        // Adjust for finish time hours.
        if (!empty($config->defaultfinishtime_hours)) {
            $defaultfinish = strtotime(date('Y-m-d', $defaultfinish).' 00:00:00');
            $defaultfinish += HOURSECS * (int)$config->defaultfinishtime_hours;
        }

        // Adjust for start time minutes.
        if (!empty($config->defaultstarttime_minutes)) {
            $defaultstart += MINSECS * (int)$config->defaultstarttime_minutes;
        }

        // Adjust for finish time minutes.
        if (!empty($config->defaultfinishtime_minutes)) {
            $defaultfinish += MINSECS * (int)$config->defaultfinishtime_minutes;
        }
        return array($defaultstart, $defaultfinish);
    }

    /**
     * Validate dates and room availability
     * @param int $timestart
     * @param int $timefinish
     * @param array $roomids
     * @param array $assetids
     * @param int $sessionid ignore room conflicts within current session (as it is covered by dates and some dates can be marked as deleted)
     * @param int $facetofaceid
     * @param array $facilitatorids
     * @return array errors ('timestart' => string, 'timefinish' => string, 'assetids' => string, 'roomids' => string 'facilitatorids' => string)
     */
    public static function validate(int $timestart, int $timefinish, array $roomids, array $assetids, int $sessionid, int $facetofaceid, array $facilitatorids): array {

        $seminar = new seminar($facetofaceid);
        $seminarevent = new seminar_event($sessionid);

        // If we are creating a new event we'll need to set the facetofaceid.
        if (!$seminarevent->exists()) {
            if ($seminar->exists()) {
                $seminarevent->set_facetoface($facetofaceid);
            }
        }

        $errors = array();
        // Validate start time.
        if ($timestart > $timefinish) {
            $errstr = get_string('error:sessionstartafterend', 'facetoface');
            $errors['timestart'] = $errstr;
            $errors['timefinish'] = $errstr;
        }

        // Validate rooms.
        if (!empty($roomids)) {
            foreach ($roomids as $roomid) {
                $room = new \mod_facetoface\room($roomid);
                if (!$room->exists()) {
                    // This will likely never be reached as room creation uses a MUST_EXIST database call.
                    $errors['roomid'][] = get_string('roomdeleted', 'facetoface');
                } else if (!$room->is_available($timestart, $timefinish, $seminarevent)) {
                    $link = \html_writer::link(new \moodle_url('/mod/facetoface/reports/rooms.php', array('roomid' => $roomid)), $room->get_name(),
                        array('target' => '_blank'));
                    // We should not get here because users should be able to select only available slots.
                    $errors['roomid'][] = get_string('error:isalreadybooked', 'facetoface', $link);
                }
            }
            if (!empty($errors['roomid'])) {
                $errors['roomids'] = implode(\html_writer::empty_tag('br'), $errors['roomid']);
            }
        }

        // Validate assets.
        if (!empty($assetids)) {
            foreach ($assetids as $assetid) {
                $asset = new asset($assetid);
                if (!$asset->exists()) {
                    // This will likely never be reached as asset creation uses a MUST_EXIST database call.
                    $errors['assetid'][] = get_string('assetdeleted', 'facetoface');
                } else if (!$asset->is_available($timestart, $timefinish, $seminarevent)) {
                    $link = \html_writer::link(new \moodle_url('/mod/facetoface/reports/assets.php', array('assetid' => $assetid)), $asset->get_name(),
                        array('target' => '_blank'));
                    // We should not get here because users should be able to select only available slots.
                    $errors['assetid'][] = get_string('error:isalreadybooked', 'facetoface', $link);
                }
            }
            if (!empty($errors['assetid'])) {
                $errors['assetids'] = implode(\html_writer::empty_tag('br'), $errors['assetid']);
            }
        }

        // Validate facilitators.
        if (!empty($facilitatorids)) {
            foreach ($facilitatorids as $facilitatorid) {
                $facilitator = new facilitator($facilitatorid);
                if (!$facilitator->exists()) {
                    // This will likely never be reached as facilitator creation uses a MUST_EXIST database call.
                    $errors['facilitatorid'][] = get_string('facilitatordeleted', 'mod_facetoface');
                } else if (!$facilitator->is_available($timestart, $timefinish, $seminarevent)) {
                    $link = \html_writer::link(
                        new \moodle_url('/mod/facetoface/reports/facilitators.php', ['facilitatorid' => $facilitatorid]),
                        $facilitator->get_name(),
                        ['target' => '_blank']
                    );
                    // We should not get here because users should be able to select only available slots.
                    $errors['facilitatorid'][] = get_string('error:isalreadybooked', 'mod_facetoface', $link);
                }
            }
            if (!empty($errors['facilitatorid'])) {
                $errors['facilitatorids'] = implode(\html_writer::empty_tag('br'), $errors['facilitatorid']);
            }
        }

        // Consolidate error message.
        if (!empty($errors['roomid']) || !empty($errors['assetid']) || !empty($errors['facilitatorid'])) {
            $items = array();
            if (!empty($errors['roomid'])) {
                foreach ($errors['roomid'] as $roomerror) {
                    $items[] = \html_writer::tag('li', $roomerror);
                }
                // Don't show duplicate error.
                unset($errors['roomid']);
            }
            if (!empty($errors['assetid'])) {
                foreach ($errors['assetid'] as $asseterror) {
                    $items[] = \html_writer::tag('li', $asseterror);
                }
                // Don't show duplicate error.
                unset($errors['assetid']);
            }
            if (!empty($errors['facilitatorid'])) {
                foreach ($errors['facilitatorid'] as $facilitatorerror) {
                    $items[] = \html_writer::tag('li', $facilitatorerror);
                }
                // Don't show duplicate error.
                unset($errors['facilitatorid']);
            }
            $details = \html_writer::tag('ul', implode('', $items));
            $errors['timestart'] = get_string('error:datesunavailablestuff', 'facetoface', $details);
        }
        return $errors;
    }

    /**
     * Format the dates for the given session, when listing the other bookings made by a given manager
     * in a particular face to face instance.
     *
     * @param \stdClass $session
     * @return string
     */
    public static function format_dates(\stdClass $session): string {
        if (!empty($session->sessiondates)) {
            $formatteddates = array();
            foreach ($session->sessiondates as $date) {
                $formatteddate = '';
                $sessionobj = output\session_time::format($date->timestart, $date->timefinish, $date->sessiontimezone);
                if ($sessionobj->startdate == $sessionobj->enddate) {
                    $formatteddate .= $sessionobj->startdate . ', ';
                } else {
                    $formatteddate .= $sessionobj->startdate . ' - ' . $sessionobj->enddate . ', ';
                }
                $formatteddate .= $sessionobj->starttime . ' - ' . $sessionobj->endtime . ' ' . $sessionobj->timezone;
                $formatteddates[] = $formatteddate;
            }
            $formatteddates = '<li>'.implode('</li><li>', $formatteddates).'</li>';
            $ret = \html_writer::tag('ul', $formatteddates);
        } else {
            $ret = \html_writer::tag('em', get_string('wait-listed', 'facetoface'));
        }
        return $ret;
    }
}
