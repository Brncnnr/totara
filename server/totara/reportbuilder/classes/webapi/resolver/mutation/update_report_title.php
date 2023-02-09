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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_reportbuilder\webapi\resolver\helper;

/**
 * Mutation to update a report title
 */
class update_report_title extends mutation_resolver {

    use helper;

    /**
     * Updates a report title.
     *
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        $formatted = format_string($args['title'], true);
        if (!$formatted) {
            throw new \invalid_parameter_exception('The title must contain a value');
        }

        if (!self::user_can_edit_report()) {
            throw new \coding_exception('No permission to edit reports.');
        }

        if (!$DB->record_exists('report_builder', ['id' => $args['reportid']])) {
            throw new \coding_exception('Attempted to edit a non-existent report');
        }

        \reportbuilder::update_fullname($args['reportid'], $args['title']);

        return $formatted;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}

