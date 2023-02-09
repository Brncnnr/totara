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
use context_user;
use core\entity\user;
use core\event\base;
use totara_evidence\entity;

abstract class evidence_item extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init(): void {
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = entity\evidence_item::TABLE;
    }

    /**
     * Create instance of event for an evidence item.
     *
     * @param entity\evidence_item $evidence_item
     * @return evidence_item|base
     * @throws coding_exception
     */
    public static function create_from_item(entity\evidence_item $evidence_item): evidence_item {
        $data = [
            'objectid' => $evidence_item->id,
            'context' => context_system::instance(),
            'relateduserid' => $evidence_item->user_id,
            'other' => [
                'typeid' => $evidence_item->typeid,
                'imported' => $evidence_item->imported,
            ],
        ];

        if (user::logged_in()->id != $evidence_item->user_id) {
            $data['context'] = context_user::instance($evidence_item->user_id);
        }

        $event = static::create($data);
        $event->add_record_snapshot($evidence_item::TABLE, (object) $evidence_item->to_array());

        return $event;
    }

}
