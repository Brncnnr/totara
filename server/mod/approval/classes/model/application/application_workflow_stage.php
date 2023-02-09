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

namespace mod_approval\model\application;

use coding_exception;
use core\collection;
use mod_approval\exception\model_exception;
use mod_approval\model\workflow\workflow_stage;

/**
 * Represents a workflow stage in a particular application.
 *
 * @property-read application $application Application
 * @property-read workflow_stage $stage Workflow stage
 * @property-read collection|application_activity[] $activities Activities in this stage
 */
final class application_workflow_stage {
    /** @var application */
    private $application;

    /** @var workflow_stage */
    private $workflow_stage;

    /** @var collection|application_activity[]|null */
    private $activities_in_stage = null;

    /**
     * Private constructor.
     *
     * @param application $application
     * @param workflow_stage $stage
     */
    private function __construct(application $application, workflow_stage $stage) {
        $this->application = $application;
        $this->workflow_stage = $stage;
    }

    /**
     * Create an instance.
     *
     * @param application $application Application model
     * @param workflow_stage|null $stage Workflow stage model in $application
     * @return self|null self if $stage is not null, or null
     */
    public static function load_by_stage(application $application, ?workflow_stage $stage): ?self {
        if ($stage === null) {
            return null;
        }
        if (!$application->workflow_version->stages->has('id', $stage->id)) {
            throw new model_exception('Invalid stage');
        }
        return new self($application, $stage);
    }

    /**
     * Create a collection of instances from the stages associated to the application.
     *
     * @param application $application
     * @return collection|application_workflow_stage[]
     */
    public static function load_all_by_application(application $application): collection {
        return $application->workflow_version->stages->map(
            function (workflow_stage $stage) use ($application) {
                return new self($application, $stage);
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function __get(string $name) {
        if ($this->__isset($name)) {
            $method = 'get_' . $name;
            return $this->{$method}();
        }
        throw new coding_exception('undefined property');
    }

    /**
     * @inheritDoc
     */
    public function __isset(string $name): bool {
        return in_array($name, ['application', 'stage', 'activities']);
    }

    /**
     * @return application
     */
    public function get_application(): application {
        return $this->application;
    }

    /**
     * @return workflow_stage
     */
    public function get_stage(): workflow_stage {
        return $this->workflow_stage;
    }

    /**
     * @return collection|application_activity[]
     */
    public function get_activities(): collection {
        if ($this->activities_in_stage === null) {
            $this->activities_in_stage = $this->application->activities->filter('workflow_stage_id', $this->workflow_stage->id);
        }
        return $this->activities_in_stage;
    }
}
