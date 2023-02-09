<?php
/**
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use Exception;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\section;
use moodle_exception;
use totara_mvc\tui_view;

class section_content extends perform_controller {

    /**
     * @var section $section
     */
    private $section;

    /**
     * @return int
     */
    protected function get_section_id_param(): int {
        return $this->get_required_param('section_id', PARAM_INT);
    }

    /**
     * Loads section model from parameter.
     *
     * @return section
     * @throws moodle_exception
     */
    protected function get_section(): section {
        if (!isset($this->section)) {
            try {
                $this->section = section::load_by_id($this->get_section_id_param());
            } catch (Exception $exception) {
                throw new moodle_exception('invalid_section', 'mod_perform');
            }
        }
        return $this->section;
    }

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return $this->get_section()->activity->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $this->require_capability('mod/perform:manage_activity', $this->get_context());
        $this->set_url(self::get_url(['section_id' => $this->get_section_id_param()]));

        $section = $this->get_section();
        $title = $section->activity->get_multisection_setting()
            ? $section->get_display_title()
            : $section->activity->name;
        $activity_state = $section->activity->get_status_state();
        $string_field_formatter = new string_field_formatter(format::FORMAT_PLAIN, $this->setup_context());
        $props = [
            'activity-context-id' => (int) $section->activity->context_id,
            'section-id' => (int) $this->get_section_id_param(),
            'activity-id' => (int) $section->activity_id,
            'activity-state' => [
                'code' => $activity_state::get_code(),
                'name' => $activity_state::get_name(),
                'display_name' => $activity_state::get_display_name(),
            ],
            'title' => $string_field_formatter->format($title),
            'is-multi-section-active' => $section->activity->get_multisection_setting(),
            'go-back-link' => [
                'url' => (string) edit_activity::get_url(['activity_id' => $section->activity_id]),
                'text' => get_string(
                    'back_to_activity_content',
                    'mod_perform',
                    $string_field_formatter->format($section->activity->name)
                ),
            ],
        ];

        return self::create_tui_view('mod_perform/pages/SectionContent', $props)
            ->set_title(get_string('manage_section_content_page_title', 'mod_perform', format_string($title)));
    }

    public static function get_base_url(): string {
        return '/mod/perform/manage/activity/section.php';
    }
}