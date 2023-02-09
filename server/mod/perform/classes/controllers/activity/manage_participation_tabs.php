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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use tabobject;
use tabtree;

trait manage_participation_tabs {

    /**
     * @param int $activity_id
     * @param string $selected_tab
     * @return tabtree
     */
    public static function get_participation_tabs(int $activity_id, string $selected_tab = 'subject_instances'): tabtree {
        $tabs = [];

        $tabs[] = new tabobject(
            'subject_instances',
            new \moodle_url('/mod/perform/manage/participation/subject_instances.php',
                ['activity_id' => $activity_id]
            ),
            get_string('subject_instances', 'mod_perform')
        );

        $tabs[] = new tabobject(
            'participant_instances',
            new \moodle_url('/mod/perform/manage/participation/participant_instances.php',
                ['activity_id' => $activity_id]
            ),
            get_string('participant_instances', 'mod_perform')
        );

        $tabs[] = new tabobject(
            'participant_sections',
            new \moodle_url('/mod/perform/manage/participation/participant_sections.php',
                ['activity_id' => $activity_id]
            ),
            get_string('participant_sections', 'mod_perform')
        );

        foreach ($tabs as $tab) {
            if ($tab->id === $selected_tab) {
                $tab->activated = true;
                $tab->selected = true;
                break;
            }
        }

        return new tabtree($tabs);
    }

}
