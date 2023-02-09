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
 * @author Aaron Machin <aaron.machin@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\model;

use core\orm\entity\model;
use totara_useraction\action\action_contract;
use totara_useraction\action\factory as action_factory;
use totara_useraction\entity\scheduled_rule_history as entity;

/**
 * Represents a scheduled_rule_history
 *
 * @property-read int $id
 * @property-read int $scheduled_rule_id
 * @property-read int $user_id
 * @property-read int $created
 * @property-read bool $success
 * @property-read action_contract $action
 * @property-read string|null $message
 */
class scheduled_rule_history extends model {
    /**
     * @var entity
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'scheduled_rule_id',
        'user_id',
        'created',
        'success',
        'message',
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'action',
    ];

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return entity::class;
    }

    /**
     * Fetch the relevant user action.
     *
     * @return action_contract
     */
    public function get_action(): action_contract {
        $identifier = $this->entity->action;
        return action_factory::create($identifier);
    }

    /**
     * Delete the scheduled rule history.
     *
     * @return void
     */
    public function delete(): void {
        $this->entity->delete();
    }
}