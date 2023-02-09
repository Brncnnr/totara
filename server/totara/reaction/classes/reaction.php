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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_reaction
 */

namespace totara_reaction;

use totara_reaction\entity\reaction as entity;

/**
 * Class reaction
 * @package totara_reaction
 */
final class reaction {
    /**
     * @var entity
     */
    private $entity;

    /**
     * @var \stdClass|null
     */
    private $user;

    /**
     * reaction constructor.
     * @param entity $entity
     */
    protected function __construct(entity $entity) {
        $this->entity = $entity;
        $this->user = null;
    }

    /**
     * @param entity $entity
     * @param \stdClass|null $user
     * @return reaction
     */
    public static function from_entity(entity $entity, ?\stdClass $user = null): reaction {
        if (!$entity->exists()) {
            throw new \coding_exception("Cannot instantiate a record that is not existing in the system");
        }

        $userid = $entity->userid;

        if (null !== $user && $userid != $user->id) {
            throw new \coding_exception("The user parameter has a miss-match user's id with the entity");
        } else {
            $user = \core_user::get_user($userid);
        }

        $reaction = new static($entity);
        $reaction->user = $user;

        return $reaction;
    }

    /**
     * @param string    $component
     * @param string    $area
     * @param int       $instanceid
     * @param int       $contextid
     * @param int|null  $userid
     *
     * @return reaction
     */
    public static function create(string $component, string $area, int $instanceid,
                                  int $contextid, ?int $userid = null): reaction {
        global $USER;
        if (null === $userid) {
            $userid = $USER->id;
        }

        $entity = new entity();

        $entity->component = $component;
        $entity->area = $area;
        $entity->instanceid = $instanceid;
        $entity->userid = $userid;
        $entity->contextid = $contextid;

        $entity->save();
        return new static($entity);
    }

    /**
     * @return bool
     */
    public function delete(): bool {
        $this->entity->delete();
        return $this->entity->deleted();
    }

    /**
     * @return bool
     */
    public function exists(): bool {
        return $this->entity->exists();
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return (int) $this->entity->userid;
    }

    /**
     * @return \stdClass
     */
    public function get_user(): \stdClass {
        if (null === $this->user) {
            $userid = $this->entity->userid;
            $this->user = \core_user::get_user($userid);
        }

        return $this->user;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * @return int
     */
    public function get_contextid(): int {
        return $this->entity->contextid;
    }

    /**
     * @return int
     */
    public function get_instanceid(): int {
        return $this->entity->instanceid;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->entity->component;
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return $this->entity->area;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return $this->entity->timecreated;
    }
}