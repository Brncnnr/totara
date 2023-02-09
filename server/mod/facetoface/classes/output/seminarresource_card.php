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

use core\output\flex_icon;
use \core\output\template;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * The seminar resource card on the seminar resource detail page.
 */
class seminarresource_card extends template {
    /**
     * Element constructor.
     *
     * @param array $data context data for mustache template, this should be the public API of template
     */
    public function __construct(array $data) {
        if (get_class($this) === seminarresource_card::class) {
            foreach (['simple', 'detailsection', 'preview', 'multibutton'] as $deprecated) {
                if (isset($data[$deprecated])) {
                    debugging("seminarresource_card no longer supports '{$deprecated}'. Please use virtualroom_card instead.", DEBUG_DEVELOPER);
                }
            }
        }
        parent::__construct($data);
    }

    /**
     * Create a template instance to display only the heading text.
     *
     * @param string $heading heading text
     * @param boolean $inactive set true to deactivate the card
     * @return self
     * @deprecated since Totara 13.5
     */
    public static function create_simple(string $heading, bool $inactive): self {
        debugging(__METHOD__ . '() is deprecated. Please use the builder class instead.', DEBUG_DEVELOPER);
        return new virtualroom_card([
            'heading' => $heading,
            'simple' => true,
            'inactive' => $inactive,
        ]);
    }

    /**
     * Create a template instance to display only the heading text and details.
     *
     * @param string $heading
     * @param seminarevent_detail_section $details
     * @param boolean $inactive
     * @return self
     * @deprecated since Totara 13.5
     */
    public static function create_details(string $heading, seminarevent_detail_section $details, bool $inactive): self {
        debugging(__METHOD__ . '() is deprecated. Please use the builder class instead.', DEBUG_DEVELOPER);
        return new virtualroom_card([
            'heading' => $heading,
            'simple' => false,
            'detailsection' => $details->get_template_data(),
            'inactive' => $inactive,
        ]);
    }

    /**
     * Create a template instance to display the heading text, details and a command link.
     *
     * @param string $heading
     * @param string $buttonlabel
     * @param string|moodle_url $url
     * @param boolean $accent
     * @param seminarevent_detail_section|null $details
     * @param boolean $inactive
     * @param string|null $buttonhint
     * @param string|null $preview
     * @param array|null $host_info;
     * @return self
     * @deprecated since Totara 13.5
     */
    public static function create(string $heading, string $buttonlabel, $url, bool $accent, ?seminarevent_detail_section $details, bool $inactive, ?string $buttonhint = null, ?string $preview = null, ?array $host_info = null): self {
        global $OUTPUT;
        debugging(__METHOD__ . '() is deprecated. Please use the builder class instead.', DEBUG_DEVELOPER);
        if ($url instanceof moodle_url) {
            $url = $url->out(false);
        }
        $buttons = [[
            'text' => $buttonlabel,
            'url' => (string)$url,
            'hint' => $buttonhint,
        ]];
        if ($accent) {
            $buttons[0]['style'] = 'primary';
        }
        if (!is_null($host_info) && !empty($host_info)) {
            $hostbutton = [
                'text' => $host_info['buttonlabel'],
                'url' => (string)$host_info['url'],
                'hint' => $host_info['buttonhint'],
            ];
            if (!empty($host_info['accent'])) {
                $hostbutton['style'] = 'primary';
            }
            array_unshift($buttons, $hostbutton);
        }
        $icon = new flex_icon('mod_facetoface|copy_virtual_room_link');
        $data = [
            'heading' => $heading,
            'simple' => !$details,
            'has_buttons' => true,
            'buttons' => $buttons,
            'inactive' => $inactive,
            'copy' => [
                'text' => get_string('copyroomlink', 'mod_facetoface'),
                'icon' => [
                    'template' => $icon->get_template(),
                    'context' => $icon->export_for_template($OUTPUT),
                ],
                'url' => (string)$url,
            ],
        ];
        if ($details) {
            $data['detailsection'] = $details->get_template_data();
        }
        if ((string)$preview !== '') {
            $data['preview'] = $preview;
        }
        return new virtualroom_card($data);
    }
}
