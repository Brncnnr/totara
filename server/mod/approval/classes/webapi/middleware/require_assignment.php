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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\middleware;

use Closure;
use core\orm\query\exceptions\record_not_found_exception;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use invalid_parameter_exception;
use mod_approval\model\application\application;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\helper\access_checks;
use mod_approval\model\workflow\workflow_stage_approval_level;
use moodle_exception;

/**
 * Interceptor that uses assignment related data in the incoming graphql payload.
 */
class require_assignment implements middleware {
    /** @var Closure */
    private $loader;

    /** @var boolean */
    private $set_relevant_context;

    /**
     * Private constructor to enforce the factory pattern.
     *
     * @param Closure $loader
     * @param boolean $set_relevant_context
     */
    private function __construct(Closure $loader, bool $set_relevant_context) {
        $this->loader = $loader;
        $this->set_relevant_context = $set_relevant_context;
    }

    /**
     * Store 'assignment' and 'application' by using the 'application_id'.
     *
     * @param boolean $set_relevant_context
     * @return self
     * @deprecated replace any call to this method with by_input_application_id
     * @todo TODO: remove this!
     */
    public static function by_application_id(bool $set_relevant_context = false): self {
        $loader = function (payload $payload) {
            $id = $payload->get_variable('application_id');
            if (empty($id)) {
                throw new invalid_parameter_exception('invalid application id');
            }
            $application = application::load_by_id($id);
            $payload->set_variable('application', $application);
            return $application->assignment;
        };
        return new self($loader, $set_relevant_context);
    }

    /**
     * Store 'assignment' and 'application' by using the 'input.application_id'.
     *
     * @param boolean $set_relevant_context
     * @return self
     */
    public static function by_input_application_id(bool $set_relevant_context = false): self {
        $loader = function (payload $payload) {
            $input = $payload->get_variable('input') ?: [];
            if (empty($input['application_id'])) {
                throw new invalid_parameter_exception('invalid application id');
            }
            $application = application::load_by_id($input['application_id']);
            $payload->set_variable('application', $application);
            return $application->assignment;
        };
        return new self($loader, $set_relevant_context);
    }

    /**
     * Store 'assignment' by using the 'input.assignment_id'.
     *
     * @param boolean $set_relevant_context
     * @return self
     */
    public static function by_input_assignment_id(bool $set_relevant_context = false): self {
        $loader = function (payload $payload) {
            $arg = $payload->get_variable('input');
            if (empty($arg['assignment_id'])) {
                throw new invalid_parameter_exception('invalid assignment id');
            }
            $id = (int)$arg['assignment_id'];
            $assignment = assignment::load_by_id($id);
            $payload->set_variable('assignment', $assignment);
            return $assignment;
        };
        return new self($loader, $set_relevant_context);
    }

    /**
     * Store 'assignment' by using the 'input.approval_level_id'.
     *
     * @param boolean $set_relevant_context
     * @return self
     */
    public static function default_by_input_approval_level_id(bool $set_relevant_context = false): self {
        $loader = function (payload $payload) {
            $arg = $payload->get_variable('input');
            if (empty($arg['approval_level_id'])) {
                throw new invalid_parameter_exception('invalid approval_level_id');
            }
            $id = (int)$arg['approval_level_id'];
            $approval_level = workflow_stage_approval_level::load_by_id($id);
            $assignment = $approval_level->get_workflow_stage()->get_workflow_version()->get_workflow()->get_default_assignment();
            $payload->set_variable('assignment', $assignment);
            return $assignment;
        };
        return new self($loader, $set_relevant_context);
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        try {
            $loader = $this->loader;
            /** @var assignment $assignment */
            $assignment = $loader($payload);
        } catch (record_not_found_exception $ex) {
            throw new moodle_exception('invalid_assignment', 'mod_approval');
        }

        require_login(null, false, null, false, true);
        $helper = access_checks::from_assignment($assignment);
        $helper->check();

        if ($this->set_relevant_context) {
            $context = $assignment->get_context();
            $payload->get_execution_context()->set_relevant_context($context);
        }

        // Store the assignment instance for later use.
        $payload->set_variable('assignment', $assignment);

        return $next($payload);
    }
}
