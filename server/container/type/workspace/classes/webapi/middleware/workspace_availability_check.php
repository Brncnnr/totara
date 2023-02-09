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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\webapi\middleware;

use Closure;
use coding_exception;
use container_workspace\workspace;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use core_container\factory;
use dml_missing_record_exception;
use moodle_exception;

/**
 * A middleware to check whether the workspace has been marked for deleted or not.
 */
final class workspace_availability_check implements middleware {
    /**
     * @var string
     */
    private $workspace_id_field;

    public function __construct(string $workspace_id_field) {
        $this->workspace_id_field = $workspace_id_field;
    }

    /**
     * @param payload $payload
     * @param Closure $next
     *
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        if (!$payload->has_variable($this->workspace_id_field)) {
            throw new coding_exception(
                "Cannot find the field '{$this->workspace_id_field}' in payload"
            );
        }

        $workspace_id = $payload->get_variable($this->workspace_id_field);

        try {
            /** @var workspace $workspace */
            $workspace = factory::from_id($workspace_id);
        } catch (coding_exception $e) {
            throw new moodle_exception('invalid_workspace', 'container_workspace');
        } catch (dml_missing_record_exception $e) {
            throw new coding_exception("Cannot find workspace by id '{$workspace_id}'");
        }

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new coding_exception("Cannot find workspace by id '{$workspace_id}'");
        }

        if ($workspace->is_to_be_deleted()) {
            throw new coding_exception("The workspace is deleted");
        }

        return $next->__invoke($payload);
    }
}