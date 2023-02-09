<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\resource;

use totara_engage\resource\input\definition;

final class helper {
    /**
     * On adding event.
     * @var string
     */
    public const SANITIZE_ON_ADD = 'add';

    /**
     * On updating event.
     * @var string
     */
    public const SANITIZE_ON_UPDATE = 'update';

    /**
     * helper constructor.
     */
    private function __construct() {
        // Preventing the construction.
    }

    /**
     * A function to provide the default value data if the parameter $data does not have it.
     * Also it is for running validation on $data against the parameter $definitions passing in to it.
     *
     * The sample data structure of parameter $definitions will look similar to the example below.
     *
     * @param definition[]  $definitions    The data structure definition
     * @param array         $data
     * @param string        $event
     *
     * @return array
     */
    public static function sanitize_instance_data(array $definitions, array $data,
                                                  string $event = self::SANITIZE_ON_ADD): array {
        if (empty($definitions)) {
            // No keys
            return $data;
        }

        // Perform the parameter $data valiation and also setup the default data if needs,
        // this is a master piece of my works ever !!!
        foreach ($definitions as $definition) {
            if (!($definition instanceof definition)) {
                continue;
            }

            $key = $definition->get_key();
            $required = false;
            $alias = $definition->get_alias();

            if (self::SANITIZE_ON_ADD == $event) {
                $required = $definition->is_required_on_add();
            } else if (self::SANITIZE_ON_UPDATE == $event) {
                $required = $definition->is_required_on_update();
            } else {
                debugging("Invalid event '{$event}'", DEBUG_DEVELOPER);
            }

            if (!array_key_exists($key, $data)) {
                if ($required) {
                    // If it is required, and either $alias is not set or the parameter \$data does not have
                    // key alias then we will throw an exception.
                    if (null == $alias || !array_key_exists($alias, $data)) {
                        throw new \coding_exception("Unable to find the required key '{$key}' in parameter \$data");
                    }
                }

                if (null == $alias || !array_key_exists($alias, $data)) {
                    // There is no alias, so we use the default. Default to null, if the $option has defined
                    // any type of default value, then we will use it.
                    if (self::SANITIZE_ON_ADD == $event) {
                        // We should be adding the default
                        $data[$key] = $definition->get_default();
                    }

                    continue;
                }

                // So alias key is existing in $data, time to get it and remove the alias.
                $data[$key] = $data[$alias];
                unset($data[$alias]);
            } else if (null != $alias && array_key_exists($alias, $data)) {
                // Both $alias and $key are existing in $data. Time to trim down and giving this a debug message.
                if ($data[$key] != $data[$alias]) {
                    debugging(
                        "Inconsistency of value between property with key '{$key}' and its alias '{$alias}'",
                        DEBUG_DEVELOPER
                    );
                }

                // Unset the alias, because we should not be using it.
                unset($data[$alias]);
            }

            if ($definition->has_validators()) {
                $validators = $definition->get_validators();

                foreach ($validators as $validator) {
                    $result = $validator->is_valid($data[$key]);
                    if (!$result) {
                        throw new \coding_exception("Validation run for property '{$key}' has been failed");
                    }
                }
            }
        }

        return $data;
    }
}