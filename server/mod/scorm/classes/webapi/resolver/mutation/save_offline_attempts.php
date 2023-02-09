<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;

class save_offline_attempts extends mutation_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/grade_item.php');

        $scorm = $DB->get_record('scorm', ['id' => $args['scormid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('scorm', $scorm->id, $scorm->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $scorm->course], '*', MUST_EXIST);

        // NOTE: we have a better solution for access checks
        require_login($course, false, null, $cm, true);

        require_capability('mod/scorm:view', $context);
        require_capability('mod/scorm:launch', $context);
        require_capability('mod/scorm:savetrack', $context);

        $failed = false;
        $now = time();

        if (!$scorm->allowmobileoffline) {
            $failed = true;
        } else {
            list($available, $warnings) = scorm_get_availability_status($scorm);
            if (!$available) {
                $failed = true;
            }
        }

        $result = [];
        $update_grades = false;
        foreach ($args['attempts'] as $ak => $attempt) {
            if ($failed) {
                $result[$ak] = false;
                continue;
            }
            if ($attempt['timestarted'] > $now) {
                // Future attempts are not allowed, stop.
                $failed = true;
                $result[$ak] = false;
                continue;
            }

            // Find attempt number.
            $sql = 'SELECT MAX(attempt)
                      FROM "ttr_scorm_scoes_track"
                     WHERE userid = :userid AND scormid = :scormid';
            $lastattempt = $DB->get_field_sql($sql, ['userid' => $USER->id, 'scormid' => $scorm->id]);
            $attemptnum = $lastattempt ? $lastattempt + 1 : 1;
            if ($scorm->maxattempt && $attemptnum > $scorm->maxattempt) {
                // No more attempts.
                $failed = true;
                $result[$ak] = false;
                continue;
            }

            $tracks = [];
            $sco = null;
            foreach ($attempt['tracks'] as $track) {
                if ($track['element'] === '' || $track['element'] === 'x.start.time' || $track['element'] === 'x.offline.attempt') {
                    continue;
                }
                if (!$sco || $sco->identifier !== $track['identifier']) {
                    $sco = $DB->get_record('scorm_scoes', ['scorm' => $scorm->id, 'identifier' => $track['identifier'], 'scormtype' => 'sco']);
                }
                if (!$sco) {
                    // Invalid sco identifier, stop.
                    $failed = true;
                    $result[$ak] = false;
                    continue 2;
                }
                if (!isset($tracks[$sco->id])) {
                    $tracks[$sco->id] = [];
                    // Record origin of attempt.
                    $record = new \stdClass();
                    $record->userid = $USER->id;
                    $record->scormid = $scorm->id;
                    $record->scoid = $sco->id;
                    $record->attempt = $attemptnum;
                    $record->element = 'x.offline.attempt';
                    $record->value = '1';
                    $record->timemodified = $now;
                    $tracks[$sco->id][$record->element] = $record;
                    // Record reported start of attempt.
                    $record = new \stdClass();
                    $record->userid = $USER->id;
                    $record->scormid = $scorm->id;
                    $record->scoid = $sco->id;
                    $record->attempt = $attemptnum;
                    $record->element = 'x.start.time';
                    $record->value = $attempt['timestarted'];
                    $record->timemodified = $attempt['timestarted'];
                    $tracks[$sco->id][$record->element] = $record;
                }
                $record = new \stdClass();
                $record->userid = $USER->id;
                $record->scormid = $scorm->id;
                $record->scoid = $sco->id;
                $record->attempt = $attemptnum;
                $record->element = $track['element'];
                $record->value = $track['value'];
                $record->timemodified = $track['timemodified'];
                $tracks[$sco->id][$record->element] = $record;
            }

            foreach ($tracks as $scoid => $ts) {
                if (!isset($ts['cmi.core.lesson_status'])
                    || ($ts['cmi.core.lesson_status']->value !== 'completed' && $ts['cmi.core.lesson_status']->value !== 'failed' && $ts['cmi.core.lesson_status']->value !== 'passed')
                ) {
                    // All offline attempts must be completed before saving.
                    $failed = true;
                    $result[$ak] = false;
                    continue 2;
                }

                // Update grade?
                if (isset($ts['cmi.core.score.raw']) ||
                    (isset($ts['cmi.core.lesson_status']) && ($ts['cmi.core.lesson_status']->value === 'completed' || $ts['cmi.core.lesson_status']->value === 'passed'))
                ) {
                    $update_grades = true;
                }
            }

            // Do a raw insert - there is no suitable API for bulk offline inserts.
            $trans = $DB->start_delegated_transaction();
            foreach ($tracks as $scoid => $ts) {
                $DB->insert_records('scorm_scoes_track', $ts);
            }
            $trans->allow_commit();
            $result[$ak] = true;

            // Trigger events as necessary to mimic what would happen online.
            foreach ($tracks as $scoid => $ts) {
                if (isset($ts['cmi.core.score.raw']) ||
                    (isset($ts['cmi.core.lesson_status']) &&
                        ($ts['cmi.core.lesson_status']->value === 'completed' || $ts['cmi.core.lesson_status']->value === 'passed' || $ts['cmi.core.lesson_status']->value === 'failed')
                    )
                ) {
                    $data = array(
                        'other' => array('attemptid' => $attemptnum),
                        'objectid' => $scorm->id,
                        'context' => $context,
                        'relateduserid' => $USER->id
                    );
                    if (isset($ts['cmi.core.score.raw'])) {
                        // Create score submitted event.
                        $data['other']['cmielement'] = 'cmi.core.score.raw';
                        $data['other']['cmivalue'] = $ts['cmi.core.score.raw']->value;
                        $track  = $DB->get_record('scorm_scoes_track', ['userid' => $USER->id, 'scormid' => $scorm->id, 'scoid' => $scoid, 'attempt' => $attemptnum, 'element' => 'cmi.core.score.raw'], '*', MUST_EXIST);
                        $event = \mod_scorm\event\scoreraw_submitted::create($data);
                        $event->add_record_snapshot('scorm_scoes_track', $track);
                        $event->add_record_snapshot('course_modules', $cm);
                        $event->add_record_snapshot('scorm', $scorm);
                        $event->trigger();
                    }

                    if (isset($ts['cmi.core.lesson_status']) &&
                        ($ts['cmi.core.lesson_status']->value === 'completed' || $ts['cmi.core.lesson_status']->value === 'passed' || $ts['cmi.core.lesson_status']->value === 'failed')
                    ) {
                        // Create status submitted event.
                        $data['other']['cmielement'] = 'cmi.core.lesson_status';
                        $data['other']['cmivalue'] = $ts['cmi.core.lesson_status']->value;
                        $track  = $DB->get_record('scorm_scoes_track', ['userid' => $USER->id, 'scormid' => $scorm->id, 'scoid' => $scoid, 'attempt' => $attemptnum, 'element' => 'cmi.core.lesson_status'], '*', MUST_EXIST);
                        $event = \mod_scorm\event\status_submitted::create($data);
                        $event->add_record_snapshot('scorm_scoes_track', $track);
                        $event->add_record_snapshot('course_modules', $cm);
                        $event->add_record_snapshot('scorm', $scorm);
                        $event->trigger();
                    }
                }
            }
        }

        // Trigger updating grades based on a given set of SCORM CMI elements.
        if ($update_grades) {
            $grade_scorm = $DB->get_record('scorm', array('id' => $scorm->id));
            include_once($CFG->dirroot.'/mod/scorm/lib.php');
            scorm_update_grades($grade_scorm, $USER->id);
        }

        // Find current (next) attempt number.
        $sql = 'SELECT MAX(attempt)
                      FROM "ttr_scorm_scoes_track"
                     WHERE userid = :userid AND scormid = :scormid';
        $lastattempt = $DB->get_field_sql($sql, ['userid' => $USER->id, 'scormid' => $scorm->id]);
        $attemptnum = $lastattempt ? $lastattempt + 1 : 1;

        // Create response object
        $response = new \stdClass();
        $response->attempts_accepted = $result;
        $response->maxattempt = $scorm->maxattempt;
        $response->attempts_current = $attemptnum;
        // Translate completion property to string
        switch ($cm->completion) {
            case COMPLETION_TRACKING_NONE:
                $response->completion = 'tracking_none';
                break;
            case COMPLETION_TRACKING_MANUAL:
                $response->completion = 'tracking_manual';
                break;
            case COMPLETION_TRACKING_AUTOMATIC:
                $response->completion = 'tracking_automatic';
                break;
            default:
                $response->completion = 'unknown';
                break;
        }
        $response->completionview = $cm->completionview;
        $response->completionstatusrequired = $scorm->completionstatusrequired != '' ? $scorm->completionstatusrequired : null;
        $response->completionscorerequired = $scorm->completionscorerequired != '' ? $scorm->completionscorerequired : null;
        $response->completionstatusallscos = $scorm->completionstatusallscos;
        // Generate completion status
        $completioninfo = new \completion_info($course);
        $completiondata = $completioninfo->get_data($cm);
        switch ($completiondata->completionstate) {
            case COMPLETION_INCOMPLETE:
                $response->completionstatus = 'incomplete';
                break;
            case COMPLETION_COMPLETE:
                $response->completionstatus = 'complete';
                break;
            case COMPLETION_COMPLETE_PASS:
                $response->completionstatus = 'complete_pass';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $response->completionstatus = 'complete_fail';
                break;
            default:
                $response->completionstatus = 'unknown';
                break;
        }
        // Find grade
        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'scorm',
            'iteminstance' => $scorm->id,
        ]);
        $grade = new \grade_grade(array('itemid' => $item->id, 'userid' => $USER->id));
        $response->gradefinal = $grade->finalgrade;
        $response->grademax = $grade->rawgrademax;
        $response->gradepercentage = ((float)$grade->finalgrade / (float)$grade->rawgrademax) * 100;
        return $response;
    }
}
