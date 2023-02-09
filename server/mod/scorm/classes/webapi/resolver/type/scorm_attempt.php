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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\webapi\resolver\type;

use core\webapi\execution_context;
use mod_scorm\formatter\scorm_attempt_formatter;

/**
 * SCORM attempts info
 */
class scorm_attempt extends \core\webapi\type_resolver {
    public static function resolve(string $field, $attempt_obj, array $args, execution_context $ec) {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        if (empty($attempt_obj) or empty($attempt_obj->scormid) or empty($attempt_obj->attempt)) {
            throw new \coding_exception('Invalid SCORM attempt request');
        }

        $format = $args['format'] ?? null;
        $scorm = $DB->get_record('scorm', ['id' => $attempt_obj->scormid], '*', MUST_EXIST);
        $attempt = $attempt_obj->attempt;
        $mode = 'normal';
        $context = $ec->get_relevant_context();

        $version = $scorm->version;
        if (!file_exists($CFG->dirroot.'/mod/scorm/datamodels/'.$version.'lib.php')) {
            $version = 'scorm_12';
        }
        require_once($CFG->dirroot.'/mod/scorm/datamodels/'.$version.'lib.php');

        // Set some vars to use as default values.
        $userdata = new \stdClass();
        $def = new \stdClass();
        $cmiobj = new \stdClass();
        $cmiint = new \stdClass();

        if (!isset($currentorg)) {
            $currentorg = '';
        }

        if ($scoes = $DB->get_records('scorm_scoes', array('scorm' => $scorm->id), 'sortorder, id')) {
            // Drop keys so that it is a simple array.
            $scoes = array_values($scoes);
            foreach ($scoes as $sco) {
                $def->{($sco->identifier)} = new \stdClass();
                $userdata->{($sco->identifier)} = new \stdClass();
                $def->{($sco->identifier)} = get_scorm_default($userdata->{($sco->identifier)}, $scorm, $sco->id, $attempt, $mode);

                // Reconstitute objectives.
                $cmiobj->{($sco->identifier)} = scorm_reconstitute_array_element($scorm->version, $userdata->{($sco->identifier)},
                    'cmi.objectives', array('score'));
                $cmiint->{($sco->identifier)} = scorm_reconstitute_array_element($scorm->version, $userdata->{($sco->identifier)},
                    'cmi.interactions', array('objectives', 'correct_responses'));
            }
        }

        // Translate from DB fields or derived properties to GraphQL properties.
        $data = new \stdClass();

        if ($field == 'attempt') {
            $data->attempt = $attempt_obj->attempt;
        }

        if ($field == 'timestarted') {
            $records = $DB->get_records('scorm_scoes_track', ['userid' => $USER->id, 'scormid' => $scorm->id, 'attempt' => $attempt_obj->attempt, 'element' => 'x.start.time'], 'value ASC');
            if (count($records)) {
                $record = array_shift($records);
                $data->timestarted = $record->value;
            } else {
                $data->timestarted = 0;
            }
        }

        if ($field == 'gradereported') {
            $gradereported = scorm_grade_user_attempt($scorm, $USER->id, $attempt_obj->attempt);
            if ($scorm->grademethod !== GRADESCOES && !empty($scorm->maxgrade)) {
                $gradereported = $gradereported / $scorm->maxgrade;
                $gradereported = number_format($gradereported * 100, 0);
            }
            $data->gradereported = $gradereported;
        }

        if ($field == 'defaults') {
            $data->defaults = json_encode($def);
        }

        if ($field == 'objectives') {
            $data->objectives = json_encode($cmiobj);
        }

        if ($field == 'interactions') {
            $data->interactions = json_encode($cmiint);
        }

        $formatter = new scorm_attempt_formatter($data, $context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['intro', 'package_url', 'launch_url'])) {
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted);
        }

        return $formatted;
    }
}
