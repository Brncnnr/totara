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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\entity;

use core\orm\entity\entity;
use stdClass;

/**
 * Entity class for table "ttr_notifications"
 *
 * @property int         $id
 * @property int         $useridfrom
 * @property int         $useridto
 * @property string|null $subject
 * @property string|null $fullmessage
 * @property int         $fullmessageformat
 * @property string|null $fullmessagehtml
 * @property string|null $smallmessage
 * @property string|null $component
 * @property string|null $eventtype
 * @property string|null $contexturl
 * @property string|null $contexturlname
 * @property int|null    $timeread
 * @property int         $timecreated
 */
class notification extends entity {
    /**
     * @var string
     */
    public const TABLE = 'notifications';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @return stdClass
     */
    public function get_record(): stdClass {
        return (object) $this->get_attributes_raw();
    }
}