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
 * @package totara_core
 */

namespace totara_core\virtualmeeting;

use totara_core\entity\virtual_meeting;
use totara_core\entity\virtual_meeting_config;
use totara_core\entity\virtual_meeting_config_repository;

/**
 * Storage facility.
 */
final class storage {
    /** @var integer */
    private $virtualmeetingid;

    /**
     * Constructor.
     *
     * @param virtual_meeting $entity
     * @internal Do *NOT* call me!!
     * @codeCoverageIgnore
     */
    public function __construct(virtual_meeting $entity) {
        $this->virtualmeetingid = $entity->id;
    }

    /**
     * @return virtual_meeting_config_repository
     */
    private function repository(): virtual_meeting_config_repository {
        return virtual_meeting_config::repository()->where('virtualmeetingid', $this->virtualmeetingid);
    }

    /**
     * Get a configuration value.
     *
     * @param string $name
     * @param boolean $strict blow up if a record not found
     * @return string|null
     */
    public function get(string $name, bool $strict = false): ?string {
        $entity = virtual_meeting_config::repository()->find_by_name($this->virtualmeetingid, $name, $strict);
        if ($entity === null) {
            return null;
        }
        return $entity->value;
    }

    /**
     * Set a configuration value.
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function set(string $name, string $value): self {
        $entity = virtual_meeting_config::repository()->find_by_name($this->virtualmeetingid, $name, false);
        if ($entity === null) {
            $entity = new virtual_meeting_config();
            $entity->virtualmeetingid = $this->virtualmeetingid;
            $entity->name = $name;
        }
        $entity->value = $value;
        $entity->save();
        return $this;
    }

    /**
     * Delete a configuration value.
     *
     * @param string $name
     * @return self
     */
    public function delete(string $name): self {
        $this->repository()->where('name', $name)->delete();
        return $this;
    }

    /**
     * Delete all configuration values associated to the virtual meeting instance.
     *
     * @return self
     */
    public function delete_all(): self {
        $this->repository()->delete();
        return $this;
    }

    /**
     * Return the age (in seconds) of a stored configuration value
     *
     * @param string $name
     * @param int $current_time
     * @param bool $strict
     * @return int|null
     */
    public function age(string $name, int $current_time = 0, bool $strict = false): ?int {
        $entity = virtual_meeting_config::repository()->find_by_name($this->virtualmeetingid, $name, $strict);
        if ($entity === null) {
            return null;
        }
        $time = $current_time ?: time();
        if ($entity->timemodified === null) {
            return $time - $entity->timecreated;
        } else {
            return $time - $entity->timemodified;
        }
    }
}
