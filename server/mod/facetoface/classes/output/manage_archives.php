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

use core\output\notification;
use core\output\template;
use html_table;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * The manage archived users page.
 */
final class manage_archives extends template {
    /**
     * Create a template.
     *
     * @param notification $banner
     * @param html_table $table
     * @param integer $event_id
     * @param moodle_url $posturl
     * @param moodle_url $backurl
     * @return self
     */
    public static function create(notification $banner, html_table $table, int $event_id, moodle_url $posturl, moodle_url $backurl): self {
        global $OUTPUT, $USER;
        return new self([
            'posturl' => $posturl->out(false),
            'backurl' => $backurl->out(false),
            'banner' => [
                'template' => $banner->get_template_name(),
                'context' => $banner->export_for_template($OUTPUT),
            ],
            'table' => [
                'template' => 'core/table',
                'context' => $table->export_for_template($OUTPUT),
            ],
            'sesskey' => $USER->sesskey,
            'event_id' => $event_id,
        ]);
    }

    /**
     * Create a template.
     *
     * @param moodle_url $backurl
     * @return self
     */
    public static function create_empty(moodle_url $backurl): self {
        return new self([
            'backurl' => $backurl->out(false),
        ]);
    }
}
