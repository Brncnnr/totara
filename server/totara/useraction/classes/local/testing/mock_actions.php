<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\local\testing;

use ReflectionProperty;
use totara_useraction\action\factory;
use totara_useraction\fixtures\mock_action;

/**
 * Helper class to let us inject mock actions into the testing framework.
 */
trait mock_actions {
    /**
     * @var array|null
     */
    private ?array $original = null;

    /**
     * @return void
     */
    protected function include_mock_action_fixtures(): void {
        global $CFG;
        require_once $CFG->dirroot . '/totara/useraction/tests/fixtures/mock_action.php';
        require_once $CFG->dirroot . '/totara/useraction/tests/fixtures/mock_invalid_action.php';
    }

    /**
     * Inject the mock action inside the factory.
     *
     * @return void
     */
    protected function inject_mock_actions(): void {
        $this->include_mock_action_fixtures();

        $prop = new ReflectionProperty(factory::class, 'concrete_actions');
        $prop->setAccessible(true);
        $actions = $prop->getValue();
        $this->original = $actions;
        $actions[] = mock_action::class;
        $prop->setValue($actions);
        $prop->setAccessible(false);
    }

    /**
     * Reset the mock actions back to normal.
     *
     * @return void
     */
    protected function remove_mock_actions(): void {
        if ($this->original === null) {
            throw new \coding_exception('Cannot remove factory actions as they have not been set.');
        }

        $prop = new ReflectionProperty(factory::class, 'concrete_actions');
        $prop->setAccessible(true);
        $prop->setValue($this->original);
        $prop->setAccessible(false);
        $this->original = null;
    }
}

