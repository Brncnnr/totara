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

namespace mod_perform\controllers\reporting\performance;

use tabobject;
use tabtree;

trait activity_response_data_tabs {

    public static $by_content_tab_uri = '/mod/perform/reporting/performance/activity_responses_by_content.php';
    public static $by_user_tab_uri = '/mod/perform/reporting/performance/activity_responses_by_user.php';

    /**
     * @param string $selected_tab
     * @return tabtree
     */
    public static function get_activity_response_data_tabs(string $selected_tab = 'by_user'): tabtree {
        $tabs = [];

        $tabs[] = new tabobject(
            'by_user',
            new \moodle_url(self::$by_user_tab_uri),
            get_string('browse_records_by_user', 'mod_perform')
        );

        $tabs[] = new tabobject(
            'by_content',
            new \moodle_url(self::$by_content_tab_uri),
            get_string('browse_records_by_content', 'mod_perform')
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
