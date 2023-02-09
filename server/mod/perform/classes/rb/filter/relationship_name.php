<?php
/**
 * This file is part of Totara Perform
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 * @subpackage totara_reportbuilder
 */

namespace mod_perform\rb\filter;

use totara_core\relationship\relationship;
use totara_core\relationship\relationship_provider;

/**
 * Filter based on selecting multiple relationship names via a dialog
 */
class relationship_name extends perform_filter_type {

    /**
     * @inheritDoc
     */
    protected static function get_modal_title(): array {
        return ['choose_relationship_name_plural', 'mod_perform'];
    }

    /**
     * @inheritDoc
     */
    protected static function get_modal_css(): string {
        return 'rb-filter-choose-relationship-name';
    }

    /**
     * @inheritDoc
     */
    public static function get_item_options(): array {
        static $options = [];
        if (!empty($options)) {
            return $options;
        }

        $relationships = (new relationship_provider())
            ->filter_by_component('mod_perform', true)
            ->get_compatible_relationships(['user_id']);
        /** @var relationship $relationship */
        foreach ($relationships as $relationship) {
            $options[$relationship->id] = $relationship->get_name();
        }
        return $options;
    }
}
