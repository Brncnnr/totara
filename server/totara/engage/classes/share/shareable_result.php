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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\share;

class shareable_result {

    /**
     * @var bool
     */
    private $shareable;

    /**
     * @var array
     */
    private $reason;

    /**
     * shareable_result constructor.
     *
     * @param bool $sharable
     * @param array $reason
     */
    public function __construct(bool $sharable = true, string $reason = '') {
        $this->shareable = $sharable;
        $this->reason = $reason;
    }

    /**
     * Indicate whether the component is shareable.
     *
     * @param bool $shareable
     */
    public function set_shareable(bool $shareable): void {
        $this->shareable = $shareable;
    }

    /**
     * @return bool
     */
    public function is_shareable(): bool {
        return $this->shareable;
    }

    /**
     * @param string $reason
     */
    public function set_reason(string $reason): void {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function get_reason(): string {
        return $this->reason;
    }

    /**
     * Get message for why we cant share this component.
     *
     * @return string
     */
    public function get_message(string $component): string {
        $manager = get_string_manager();
        $errorcode = $this->reason;

        if ($manager->string_exists($this->reason['errorcode'], $this->reason['component'])) {
            return $manager->get_string($this->reason['errorcode'], $this->reason['component']);
        }

        throw new \coding_exception("No language string found for [{$errorcode}:{$component}]");
    }
}
