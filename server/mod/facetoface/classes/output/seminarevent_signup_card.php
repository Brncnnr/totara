<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output;

use \core\output\template;

defined('MOODLE_INTERNAL') || die();

/**
 * The signup / cancellation block for the seminar event information page.
 */
final class seminarevent_signup_card extends template {
    /**
     * Create a template instance to display only the heading text.
     *
     * @param string $heading heading text
     * @return self
     */
    public static function create_simple(string $heading): self {
        return new static([
            'heading' => $heading
        ]);
    }

    /**
     * Create a template instance to display plain text information.
     *
     * @param string $heading heading text
     * @param string $text plain text, no html tags accepted
     * @return self
     */
    public static function create_plain(string $heading, string $text): self {
        return new static([
            'heading' => $heading,
            'statictext' => $text
        ]);
    }

    /**
     * Create a template instance to display the toggle link.
     *
     * @param string $heading heading text
     * @param string $linktext the text of the link to toggle the checkbox
     * @param string $checkboxid the id of the associated hidden checkbox
     * @return self
     */
    public static function create_toggle(string $heading, string $linktext, string $checkboxid): self {
        return new static([
            'heading' => $heading,
            'toggle' => (object)[
                'label' => $linktext,
                'checkboxid' => $checkboxid
            ]
        ]);
    }
}
