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
 */

namespace mod_perform\controllers\reporting\performance\filters;

use mod_perform\rb\filter\relationship_name as rb_filter_relationship_name;

class relationship_name extends filter_controller {

    /**
     * @inheritDoc
     */
    protected static function get_item_options(): array {
        return rb_filter_relationship_name::get_item_options();
    }

    /**
     * @inheritDoc
     */
    protected static function get_search_type(): string {
        return 'relationship_name';
    }

    /**
     * @inheritDoc
     */
    protected static function display_selected_items($id, $display_name, $filtername): string {
        return rb_filter_relationship_name::display_selected_items($id, $display_name, $filtername);
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/filters/relationship_name.php';
    }
}