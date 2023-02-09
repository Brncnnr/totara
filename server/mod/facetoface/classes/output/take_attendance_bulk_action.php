<?php
/*
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output;
defined('MOODLE_INTERNAL') || die();

use core\output\template;
use mod_facetoface\attendees_list_helper;
use mod_facetoface\signup\state\not_set;

/**
 * Class take_attendance_bulk_action
 * @package mod_facetoface\output
 */
class take_attendance_bulk_action extends template {
    /**
     * For each of selectors, it should have attributes as below:
     * + name: string
     * + label: string
     * + options: Array<string, mixed>
     * + disabled: boolean
     * + class: string
     * + container: Array<string, mixed>
     * + hiddenlabel: Array<string, mixed>
     *
     * @param bool $disabled    Whether we want to disable the bulk action list or not. As this
     *                          behaviour is for the scenario of whether event is not over yet.
     *
     * @return take_attendance_bulk_action
     */
    public static function create(bool $disabled): take_attendance_bulk_action {
        $data = [
            'disabled' => $disabled,
            'bulkselection' => self::create_bulkselection($disabled),
            'bulkaction' => self::create_bulkaction($disabled),
            'attendeesselector' => [
                [
                    'key' => 'selectall',
                    'value' => MDL_F2F_SELECT_ALL
                ],
                [
                    'key' => 'selectnone',
                    'value' => MDL_F2F_SELECT_NONE
                ],
                [
                    'key' => 'selectset',
                    'value' => MDL_F2F_SELECT_SET
                ],
                [
                    'key' => 'selectnotset',
                    'value' => MDL_F2F_SELECT_NOT_SET
                ]
            ]
        ];

        return new static($data);
    }

    /**
     * Creating the bulk action with a list of status codes
     * @param bool $disabled default false
     * @return array
     */
    protected static function create_bulkaction(bool $disabled = false): array {
        $statusoptions = \mod_facetoface\attendees_helper::get_status();

        // Cleaning not set here for the admin, and change it with the custom label here.
        unset($statusoptions[not_set::get_code()]);
        krsort($statusoptions, SORT_NUMERIC);

        return [
            'name' => 'bulkattendanceop',
            'options' => self::build_options($statusoptions),
            'disabled' => $disabled,
        ];
    }

    /**
     * Putting the options for selectors into shape of value and option_label.
     *
     * @param mixed $selected
     * @param array $options
     * @return array
     */
    private static function build_options(array $options, $selected = null): array {
        $o = [];
        foreach ($options as $code => $label) {
            $o[] = [
                'value' => $code,
                'option_label' => $label,
                'selected' => $code == $selected
            ];
        }

        return $o;
    }

    /**
     * Creating the bulk selection, for example it will bulk check all learner's checkboxes.
     * @param bool $disabled default false
     * @return array
     */
    protected static function create_bulkselection(bool $disabled = false): array {
        global $F2F_SELECT_OPTIONS;
        return [
            'name' => 'bulk_select',
            'options' => self::build_options($F2F_SELECT_OPTIONS, MDL_F2F_SELECT_NONE),
            'disabled' => $disabled,
        ];
    }
}