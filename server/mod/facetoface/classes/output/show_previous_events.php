<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output;

use core\output\template;
use mod_facetoface\seminar;
use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\filters\past_event_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * A "show all" past events link.
 */
class show_previous_events extends template {
    /**
     * Instantiate show_previous_events.
     *
     * @param seminar       $seminar
     * @param filter_list   $filters
     * @param string        $anchor     The anchor link without '#'
     * @param string        $class      Part of CSS class name
     * @return show_previous_events
     */
    public static function create(seminar $seminar,
                                  filter_list $filters,
                                  string $anchor = '',
                                  string $class = 'sessionlist'): show_previous_events {
        $data = [
            'class' => $class,
        ];

        $timeperiod = (int)get_config(null, 'facetoface_previouseventstimeperiod');
        if ($timeperiod <= 0) {
            $data['hidden'] = true;
            $data['showall'] = true;
            $data['text'] = get_string('showpreviousevents:state:all', 'mod_facetoface');
        } else {
            $data['hidden'] = false;
            // Get the current filter value.
            $filtervalue = $filters->get_filter_value(past_event_filter::class);

            // Temporarily overwrite the filter value with a flipped value.
            $displayall = ! $filtervalue;
            $filters->set_filter_value(past_event_filter::class, $displayall);

            $data['showall'] = $displayall;
            $data['link'] = [
                'url' => $filters->to_url($seminar)->out(false),
                'anchor' => $anchor
            ];

            if ($displayall) {
                $data['text'] = get_string('showpreviousevents:state:partial', 'mod_facetoface', $timeperiod);
                $data['link']['label'] = get_string('showpreviousevents:link:all', 'mod_facetoface');
            } else {
                $data['text'] = get_string('showpreviousevents:state:all', 'mod_facetoface');
                $data['link']['label'] = get_string('showpreviousevents:link:less', 'mod_facetoface');
            }
            // Revert to the current filter value.
            $filters->set_filter_value(past_event_filter::class, $filtervalue);
        }

        return new static($data);
    }
}
