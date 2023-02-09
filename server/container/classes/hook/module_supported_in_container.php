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
 * @package core_container
 */
namespace core_container\hook;

use totara_core\hook\base;

/**
 * A hook that allow the module itself to remove module that are not supported by the specific container type.
 * It is recommended that, the watcher for this hook should be placed under module component.
 */
final class module_supported_in_container extends base {
    /**
     * @var string
     */
    private $containertype;

    /**
     * @var array
     */
    private $mods;

    /**
     * mod_supported constructor.
     *
     * @param string        $containertype
     * @param string[]   $mods
     */
    public function __construct(string $containertype, array $mods) {
        $this->containertype = $containertype;
        $this->mods = [];

        if (!empty($mods)) {
            $this->mods = array_flip($mods);
        }
    }

    /**
     * @param string $modname
     * @return void
     */
    public function remove_mod(string $modname): void {
        if (!array_key_exists($modname, $this->mods)) {
            debugging("The mod '{$modname}' was not in the list", DEBUG_DEVELOPER);
            return;
        }

        unset($this->mods[$modname]);
    }

    /**
     * @return string
     */
    public function get_containertype(): string {
        return $this->containertype;
    }

    /**
     * Returning the original data value of modules. Do not expose keys.
     *
     * @return string[]
     */
    public function get_mods(): array {
        return array_keys($this->mods);
    }
}