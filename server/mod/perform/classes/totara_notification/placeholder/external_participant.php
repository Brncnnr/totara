<?php
/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\placeholder;

use coding_exception;
use mod_perform\entity\activity\external_participant as external_participant_entity;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class external_participant extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var external_participant_entity|null
     */
    protected $entity;

    /**
     * Perform placeholder constructor.
     */
    public function __construct(?external_participant_entity $entity) {
        $this->entity = $entity;
    }

    /**
     * @param int $external_participant_id
     * @return self
     */
    public static function from_id(int $external_participant_id): self {
        $instance = self::get_cached_instance($external_participant_id);
        if (!$instance) {
            /** @var external_participant_entity $external_participant */
            $external_participant = external_participant_entity::repository()->find($external_participant_id);
            $instance = new static($external_participant);
            self::add_instance_to_cache($external_participant_id, $instance);
        }
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('name', get_string('notification_placeholder_external_participant_name', 'mod_perform')),
            option::create('email', get_string('notification_placeholder_external_participant_email', 'mod_perform')),
        ];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->entity !== null;
    }

    /**
     * @param string $key
     *
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        if ($this->entity === null) {
            throw new coding_exception("The performance activity is not available");
        }

        switch ($key) {
            case 'name':
                return $this->entity->name;
            case 'email':
                return $this->entity->email;
        }

        throw new coding_exception("Invalid key '$key'");
    }
}
