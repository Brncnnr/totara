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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

/**
 * Class formview_generator_object
 *
 * Provides a structured interface for passing properties to the application generator.
 *
 * @package mod_approval\testing
 */
final class formview_generator_object {
    public $field_key;
    public $workflow_stage;
    public $required = false;
    public $disabled = false;
    public $default_value;
    public $active = true;

    /**
     * Formview_generator_object constructor, captures required properties.
     *
     * @param string $field_key
     * @param int $workflow_stage_id
     */
    public function __construct(string $field_key, int $workflow_stage_id) {
        $this->field_key = $field_key;
        $this->workflow_stage = $workflow_stage_id;
    }
}