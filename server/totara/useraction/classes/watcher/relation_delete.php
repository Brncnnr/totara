<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\watcher;

use totara_cohort\hook\delete_affects;
use totara_useraction\entity\scheduled_rule_audience_map;

/**
 * Watcher for delete hooks.
 */
class relation_delete {
    /**
     * Handle when a cohort is about to be deleted and add info about scheduled rules.
     *
     * @param delete_affects $hook
     * @return void
     */
    public static function cohort_delete_affects(delete_affects $hook): void {
        $count = scheduled_rule_audience_map::repository()->where('cohort_id', $hook->get_id())->count();
        $hook->add_affected(
            'totara_useraction',
            get_string('scheduled_user_actions', 'totara_useraction'),
            get_string('cohort_delete_affects_changes', 'totara_useraction'),
            $count
        );
    }
}
