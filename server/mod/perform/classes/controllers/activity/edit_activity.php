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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use coding_exception;
use context;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\controllers\perform_controller;
use mod_perform\controllers\requires_activity;
use mod_perform\models\activity\activity;
use moodle_url;
use totara_mvc\tui_view;

class edit_activity extends perform_controller {

    use requires_activity;

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return $this->get_activity_from_param()->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $activity = $this->get_activity_from_param();

        $this->require_capability('mod/perform:manage_activity', $this->get_context());
        $this->set_url(self::get_url(['activity_id' => $activity->id]));

        $props = [
            'activity-id' => $activity->id,
            'go-back-link' => (string) new moodle_url(manage_activities::URL),
        ];
        $activity_was_cloned = (bool) $this->get_optional_param('activity_cloned', false, PARAM_BOOL);

        if ($activity_was_cloned) {
            $props = $this->include_cloned_props($props);
        }

        $formatter = new string_field_formatter(format::FORMAT_HTML, $activity->get_context());

        return self::create_tui_view('mod_perform/pages/ManageActivity', $props)
            ->set_title(
                get_string(
                    'manage_activity_for_page_title',
                    'mod_perform',
                    $formatter->format($activity->name)
                )
            );
    }

    /**
     * Include activity cloned properties.
     *
     * @param $props
     * @return array
     */
    private function include_cloned_props($props): array {
        $cloned_activity_id = $this->get_optional_param('cloned_activity_id', false, PARAM_INT) ?? null;

        if (empty($cloned_activity_id)) {
            throw new coding_exception('Missing activity this activity was cloned from.');
        }
        $cloned_activity = activity::load_by_id($cloned_activity_id);

        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $cloned_activity->get_context());

        $props['activity-cloned-success'] = true;
        $props['cloned-activity-name'] = $formatter->format($cloned_activity->name);

        return $props;
    }

    public static function get_base_url(): string {
        return '/mod/perform/manage/activity/edit.php';
    }

}