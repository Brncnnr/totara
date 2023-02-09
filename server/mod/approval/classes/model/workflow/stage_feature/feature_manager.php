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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\workflow\stage_feature;

use coding_exception;
use core_component;
use mod_approval\model\workflow\workflow_stage;

/**
 * Properties:
 *
 * @property-read formviews $formviews Formviews feature.
 * @property-read approval_levels $approval_levels Approval levels feature.
 * @property-read interactions $interactions Interactions feature.
 */
class feature_manager {

    /**
     * Selected features passed in the constructor.
     *
     * @var array
     */
    private $selected_features;

    /**
     * Instance of workflow stage the feature_manager is using.
     *
     * @var workflow_stage
     */
    private $stage;

    /**
     * All available features.
     *
     * @var
     */
    private static $available_features;

    /**
     * @param array $selected_features
     * @param workflow_stage $stage
     *
     * @throws coding_exception
     */
    public function __construct(array $selected_features, workflow_stage $stage) {
        $this->validate_features($selected_features);
        $this->selected_features = $selected_features;
        $this->stage = $stage;
    }

    /**
     * Checks the features provided are valid
     * @param array $features
     *
     * @throws coding_exception
     */
    private function validate_features(array $features) {
        $available_features = self::get_available_features();

        foreach ($features as $feature) {
            if (!in_array($feature, $available_features, true)) {
                throw new coding_exception("Invalid feature class $feature");
            }
        }
    }

    /**
     * Scans through namespace to get all available features.
     *
     * @return array
     */
    private static function get_available_features() {
        if (is_null(self::$available_features)) {
            $features = core_component::get_namespace_classes(
                'model\workflow\stage_feature',
                base::class,
                'mod_approval'
            );
            $available_features = [];

            foreach ($features as $feature_class) {
                /** @var base $feature_class*/
                $available_features[$feature_class::get_enum()] = $feature_class;
            }

            self::$available_features = $available_features;
        }

        return self::$available_features;
    }

    /**
     * Checks if the feature manager has the feature specified.
     *
     * @return bool
     */
    public function has(string $feature_enum): bool {
        $has_feature = false;

        /** @var base $feature_class*/
        foreach ($this->selected_features as $feature_class) {
            if ($feature_class::get_enum() === $feature_enum) {
                $has_feature = true;
            }
        }

        return $has_feature;
    }

    /**
     * Get feature class with specified enum from the feature manager.
     *
     * @return base
     */
    public function get(string $feature_enum): base {
        $class = null;

        /** @var base $feature_class*/
        foreach ($this->selected_features as $feature_class) {
            if ($feature_class::get_enum() === strtoupper($feature_enum)) {
                $class = $feature_class;
            }
        }

        if (empty($class)) {
            throw new coding_exception("Feature with $feature_enum enum not available in manager");
        }

        return new $class($this->stage);
    }

    /**
     * Get list of features selected.
     *
     * @return base[]|array
     */
    public function all(): array {
        $features = [];

        /** @var base $selected_feature*/
        foreach ($this->selected_features as $selected_feature) {
            $features[$selected_feature::get_sort_order()] = new $selected_feature($this->stage);
        }
        ksort($features);

        return array_values($features);
    }

    /**
     * Magic attribute getter
     *
     * @param string $field
     * @return mixed|null
     */
    public function __get(string $field) {
        return $this->get($field);
    }
}