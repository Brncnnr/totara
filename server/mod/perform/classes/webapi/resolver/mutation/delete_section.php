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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\section;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;
use moodle_exception;

class delete_section extends mutation_resolver {

    /**
     * hard deletes section, section_relationship, participant_section, relationship(only when has no related section_relationship).
     *
     * @inheritDoc
     * @throws coding_exception|\invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        if (!$input) {
            throw new \invalid_parameter_exception('section details not given');
        }

        $section_id = (int)$input['section_id'] ?? 0;
        if (!$section_id) {
            throw new \invalid_parameter_exception('unknown section id');
        }

        $section = section::load_by_id($section_id);

        $section->check_deletion_requirements();

        $section->delete();

        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_section_id('input.section_id', true),
            require_manage_capability::class
        ];
    }
}
