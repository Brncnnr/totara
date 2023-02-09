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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossuw <riana.rossouw@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\type;

use container_workspace\formatter\discussion\discussion_search_result_formatter;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_core\advanced_feature;

/**
 * Resolver for workspace content search results
 */
class discussion_search_result extends type_resolver {
    /**
     * @param string $field
     * @param object $resul
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed|null
     */
    public static function resolve(string $field, $result, array $args, execution_context $ec) {
        advanced_feature::require('container_workspace');

        $context = \context_system::instance();
        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
        } else {
            $workspace_id = $result->workspace_id;
            $context = \context_course::instance($workspace_id);

            $ec->set_relevant_context($context);
        }
        
        switch ($field) {
            case 'owner':
                return $result->owner;
                
            default:
                $formatter = new discussion_search_result_formatter($result, $context);
                $format = null;

                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                return $formatter->format($field, $format);
        }
    }
}