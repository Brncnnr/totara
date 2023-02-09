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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\status;

/**
 * Class workflow_generator_object
 *
 * Provides a structured interface for passing properties to the workflow generator.
 *
 * @package mod_approval\testing
 */
final class workflow_generator_object {
    public $course_id;
    public $workflow_type_id;
    public $name = 'Generated Workflow';
    public $description;
    public $id_number;
    public $form_id;
    public $template_id = 0;
    public $active = true;
    public $to_be_deleted = false;

    /**
     * Form version id for default workflow_version
     * @var int
     */
    public $form_version_id;

    /**
     * Workflow_version status code
     * @var int
     */
    public $status;

    /**
     * Workflow_generator_object constructor, captures required properties.
     *
     * @param int $workflow_type_id
     * @param int $form_id
     * @param int $form_version_id
     */
    public function __construct(int $workflow_type_id, int $form_id, int $form_version_id, int $status = status::ACTIVE) {
        $this->workflow_type_id = $workflow_type_id;
        $this->form_id = $form_id;
        $this->form_version_id = $form_version_id;
        $this->status = $status;
        // Generate a default id_number
        $this->id_number = uniqid('workflow');
    }
}