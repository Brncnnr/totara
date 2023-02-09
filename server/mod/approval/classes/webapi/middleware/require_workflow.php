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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use invalid_parameter_exception;
use mod_approval\model\workflow\helper\access_checks;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_interaction;

/**
 * Interceptor that uses workflow related data in the incoming graphql payload.
 */
class require_workflow implements middleware {
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
     * Store 'workflow' by using the 'input.workflow_id'.
     *
     * @param boolean $set_relevant_context
     * @return self
     */
    public static function by_input_workflow_id(bool $set_relevant_context = false): self {
        $loader = function (payload $payload) {
            $arg = $payload->get_variable('input');
            if (empty($arg['workflow_id'])) {
                throw new invalid_parameter_exception('invalid workflow_id');
            }
            $id = (int)$arg['workflow_id'];
            $workflow = workflow::load_by_id($id);
            $payload->set_variable('workflow', $workflow);
            return $workflow;
        };
        return new self($loader, $set_relevant_context);
    }

    /**
     * Store 'workflow' and 'workflow_stage' by using the 'input.workflow_stage_id'.
     *
     * @param boolean $set_relevant_context
     * @return self
     */
    public static function by_input_workflow_stage_id(bool $set_relevant_context = false): self {
        $loader = function (payload $payload) {
            $arg = $payload->get_variable('input');
            if (empty($arg['workflow_stage_id'])) {
                throw new invalid_parameter_exception('invalid workflow_stage_id');
            }
            $id = (int)$arg['workflow_stage_id'];
            $stage = workflow_stage::load_by_id($id);
            $workflow = $stage->get_workflow_version()->get_workflow();
            $payload->set_variable('workflow', $workflow);
            $payload->set_variable('workflow_stage', $stage);
            return $workflow;
        };
        return new self($loader, $set_relevant_context);
    }

    /**
     * Store 'workflow' and 'workflow_stage_interaction' by using the 'input.workflow_stage_interaction_id'.
     *
     * @param boolean $set_relevant_context
     * @return self
     */
    public static function by_input_workflow_stage_interaction_id(bool $set_relevant_context = false): self {
        $loader = function (payload $payload) {
            $arg = $payload->get_variable('input');
            if (empty($arg['workflow_stage_interaction_id'])) {
                throw new invalid_parameter_exception('invalid workflow_stage_interaction_id');
            }
            $id = (int)$arg['workflow_stage_interaction_id'];
            $interaction = workflow_stage_interaction::load_by_id($id);
            $workflow = $interaction->workflow_stage->workflow_version->workflow;
            $payload->set_variable('workflow', $workflow);
            $payload->set_variable('workflow_stage_interaction', $interaction);
            return $workflow;
        };
        return new self($loader, $set_relevant_context);
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        global $PAGE;

        $loader = $this->loader;
        /** @var workflow */
        $workflow = $loader($payload);

        require_login(null, false, null, false, true);
        $helper = access_checks::from_workflow($workflow);
        $helper->check();

        /** @var \moodle_page $PAGE */
        $PAGE->set_course($helper->get_container()->to_record());

        if ($this->set_relevant_context) {
            $context = $workflow->get_context();
            $payload->get_execution_context()->set_relevant_context($context);
        }

        // Store the workflow instance for later use.
        $payload->set_variable('workflow', $workflow);

        return $next($payload);
    }
}
