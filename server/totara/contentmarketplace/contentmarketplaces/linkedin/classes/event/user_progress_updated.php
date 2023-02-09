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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\event;

use contentmarketplace_linkedin\entity\user_progress as user_progress_entity;
use contentmarketplace_linkedin\model\user_progress;
use context_user;
use core\event\base;

final class user_progress_updated extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = user_progress_entity::TABLE;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('user_progress_updated', 'contentmarketplace_linkedin');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' progressed in the LinkedIn Learning course with URN " .
            "'{$this->other['learning_object_urn']}' to a value of {$this->other['progress']}%.";
    }

    /**
     * @param user_progress $user_progress
     * @return self|base
     */
    public static function create_from_user_progress(user_progress $user_progress): self {
        return self::create([
            'objectid' => $user_progress->id,
            'context' => context_user::instance($user_progress->user_id),
            'userid' => $user_progress->user_id,
        ])
            ->add_entity_snapshot($user_progress->get_entity_copy());
    }

}
