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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\watcher;

use editor_weka\hook\find_context;
use mod_approval\form_schema\field_type\application_editor;
use mod_approval\model\application\application;
use totara_core\advanced_feature;

/**
 * Sets the required context for a mod_approval weka editor instance.
 *
 * @package mod_approval\watcher
 */
class editor_context_watcher {

    /**
     * Sets the context into the hook.
     *
     * @param find_context $find_context
     */
    public static function set_context(find_context $find_context) {
        // Not our problem.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        if (!self::for_mod_approval($find_context)) {
            return;
        }
        $application_context = application::load_by_id($find_context->get_instance_id())->context;
        $find_context->set_context($application_context);
    }

    /**
     * Checks if find_context hook is for mod_approval.
     *
     * @param find_context $find_context
     * @return bool
     */
    private static function for_mod_approval(find_context $find_context): bool {
        return $find_context->get_component() === application_editor::FILE_COMPONENT
            && $find_context->get_area() === application_editor::FILE_AREA;
    }
}