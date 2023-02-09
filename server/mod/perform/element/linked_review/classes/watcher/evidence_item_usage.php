<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\watcher;

use performelement_linked_review\entity\linked_review_content;
use totara_evidence\hook\evidence_item_usage as evidence_item_usage_hook;

/**
 * Class watches for in use checks on instances.
 *
 * @package performelement_linked_review\watcher
 */
class evidence_item_usage {

    /**
     * Process hook.
     *
     * @param evidence_item_usage_hook $hook
     */
    public static function in_use(evidence_item_usage_hook $hook) {
        // Check if this instance is used as content in an element.
        $count = linked_review_content::repository()
            ->where('content_id', $hook->get_instance_id())
            ->where('content_type', $hook->get_component())
            ->count();
        if ($count > 0) {
            $hook->add_used_by(
                get_string('pluginname', 'performelement_linked_review')
            );
        }
    }

}