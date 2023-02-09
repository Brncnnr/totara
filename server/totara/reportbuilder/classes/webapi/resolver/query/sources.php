<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_reportbuilder\report_helper;
use totara_reportbuilder\webapi\resolver\helper;

/**
 * Query to return all available report builder sources.
 */
class sources extends query_resolver {

    use helper;

    /**
     * Returns all available report builder sources.
     *
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        if (!self::user_can_edit_report()) {
            throw new \coding_exception('No permission to edit reports.');
        }

        $output = [];

        foreach (report_helper::get_sources() as $source) {
            $src = \reportbuilder::get_source_object($source);

            if ($src->is_source_ignored() || !$src->selectable) {
                continue;
            }

            $output[] = $src;
        }

        return $output;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}