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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\event;

use coding_exception;
use context_system;
use core\event\base;
use totara_evidence\entity;

abstract class evidence_type extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init(): void {
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = entity\evidence_type::TABLE;
    }

    /**
     * Create instance of event for an evidence type.
     *
     * @param entity\evidence_type $evidence_type
     * @return evidence_type|base
     * @throws coding_exception
     */
    public static function create_from_type(entity\evidence_type $evidence_type): evidence_type {
        $data = [
            'objectid' => $evidence_type->id,
            'context' => context_system::instance(),
        ];

        $event = static::create($data);
        $event->add_record_snapshot($evidence_type::TABLE, (object) $evidence_type->to_array());

        return $event;
    }

}
