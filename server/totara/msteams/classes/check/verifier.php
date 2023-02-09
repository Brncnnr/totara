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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\check;

defined('MOODLE_INTERNAL') || die;

use core_component;

/**
 * Verify MS Teams settings.
 */
class verifier {
    /**
     * @var array
     */
    protected $result = [];

    /**
     * Verify settings.
     *
     * @return boolean
     */
    public function execute(): bool {
        $pass = true;
        $this->result = [];

        $classnames = core_component::get_namespace_classes('check\\checks', checkable::class, 'totara_msteams');
        foreach ($classnames as $classname) {
            $class = new $classname;
            /** @var checkable $class */
            $result = $class->check();
            if ($result !== status::PASS && $result !== status::SKIPPED) {
                $pass = false;
            }
            $this->result[] = (object)[
                'result' => $result,
                'class' => $class,
            ];
        }

        return $pass;
    }

    /**
     * Get the verification result.
     * The execute() function must be called first.
     *
     * @return array
     */
    public function get_results(): array {
        return $this->result;
    }

    /**
     * Get the first failure.
     * Use get_results() and filter the result to get all failures.
     *
     * @return string
     */
    public function get_report(): string {
        foreach ($this->result as $entry) {
            $result = $entry->result;
            $class = $entry->class;
            /** @var int $result */
            /** @var checkable $class */

            // Just return the first failure. Admin needs to fix all the failures anyway.
            if ($result !== status::PASS && $result !== status::SKIPPED) {
                return $class->get_report();
            }
        }
        return '';
    }
}
