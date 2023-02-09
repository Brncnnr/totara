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

namespace mod_approval\model\assignment\approver_type;

use core\entity\user as user_entity;
use core\orm\query\exceptions\record_not_found_exception;
use core\webapi\resolver\type\user as user_resolver;

/**
 * User approver type.
 */
class user implements approver_type {
    /**
     * Type Identifier.
     *
     * @var Int
     */
    public const TYPE_IDENTIFIER = 2;

    /**
     * @inheritDoc
     */
    public function entity(int $identifier) {
        return new user_entity($identifier);
    }

    /**
     * @inheritDoc
     */
    public function entity_name(int $identifier): string {
        return $this->entity($identifier)->fullname;
    }

    /**
     * @inheritDoc
     */
    public function label(): string {
        return get_string('model_assignment_approver_type_user', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function is_valid(int $identifier): bool {
        return user_entity::repository()->where('id', $identifier)->exists();
    }

    /**
     * @inheritDoc
     */
    public function options(): ?array {
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return self::TYPE_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'USER';
    }

    /**
     * @inheritDoc
     */
    public static function resolver_class(): string {
        return user_resolver::class;
    }
}