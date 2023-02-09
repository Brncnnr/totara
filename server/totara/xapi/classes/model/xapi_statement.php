<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_xapi
 */

namespace totara_xapi\model;

use core\entity\user;
use core\orm\entity\model;
use totara_xapi\entity\xapi_statement as xapi_statement_entity;

/**
 * @property-read int $id
 * @property-read int $time_created
 * @property-read int|null $user_id
 * @property-read user|null $user
 * @property-read string|null $client_id
 *
 * @property-read array $statement
 */
class xapi_statement extends model {

    /**
     * @var xapi_statement_entity
     */
    protected $entity;

    /**
     * @var array
     */
    private $statement_parsed;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'time_created',
        'user_id',
        'user',
        'client_id'
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'statement',
    ];

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return xapi_statement_entity::class;
    }

    /**
     * @return array
     */
    public function get_statement(): array {
        if (!isset($this->statement_parsed)) {
            $this->statement_parsed = json_decode($this->entity->statement, true, 512, JSON_THROW_ON_ERROR);
        }
        return $this->statement_parsed;
    }

    /**
     * @return string
     */
    public function get_raw_statement(): string {
        return $this->entity->statement;
    }
}