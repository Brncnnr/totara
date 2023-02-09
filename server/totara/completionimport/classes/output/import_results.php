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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_completionimport
 */

namespace totara_completionimport\output;

class import_results extends \core\output\template {

    public static function create_from_import(object $importresults, string $importtype): import_results {
        $data = [];
        $data['results'] = [];

        if (empty($importresults->errors)) {
            if ($importtype === 'course') {
                $data['importmessage'] = get_string('importdonecourse', 'totara_completionimport');

                $resultdata[] = get_string('importrecordcount', 'totara_completionimport', $importresults->totalrows);
                $data['results'] = $resultdata;

            } else if ($importtype == 'certification') {
                $data['importmessage'] = get_string('importdonecertification', 'totara_completionimport');

                $resultdata[] = get_string('importrecordcount', 'totara_completionimport', $importresults->totalrows);
                $data['results'] = $resultdata;
            }
        } else {
            $data['haserrors'] = true;

            if (empty($importresults->totalrows)) {
                $data['importmessage'] = get_string('importnone', 'totara_completionimport');
            }
        }

        $data['reportlink'] = [
            'text' => $importresults->reportlink['text'],
            'link' => $importresults->reportlink['link']->out()
        ];

        $data['errors'] = $importresults->errors;

        return new import_results($data);
    }
}
