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

use pix_icon;
use \core\output\template;
use \mod_facetoface\seminar_event;

defined('MOODLE_INTERNAL') || die();

/**
 * An event page navigation.
 */
final class event_page_navigation extends template {
    /**
     * @param seminar_event         $seminarevent
     * @param string                $movetotop the text of the 'move to top' link, or empty to hide the link
     * @param (array|\stdClass)[]   $navs consisting of [ icon, label, link ]
     * @param string                $class part of CSS class name
     * @return self
     */
    public static function create(seminar_event $seminarevent,
                                  string $movetotop,
                                  array $navs,
                                  string $class = 'signup'): self {
        global $OUTPUT;

        $data = [
            'class' => $class,
            'movetotop' => $movetotop,
            'navs' => []
        ];

        foreach ($navs as $nav) {
            $nav = (array)$nav;
            // Rewrite icon
            if (isset($nav['icon']) && $nav['icon'] instanceof pix_icon) {
                $icon = $nav['icon'];
                $nav['icon'] = [
                    'template' => $icon->get_template(),
                    'context' => $icon->export_for_template($OUTPUT),
                ];
            }
            $data['navs'][] = $nav;
        }

        return new static($data);
    }
}
