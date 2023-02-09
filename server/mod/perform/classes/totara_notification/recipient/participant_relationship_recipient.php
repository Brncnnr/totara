<?php
/**
 * This file is part of Totara Learn
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\recipient;

use coding_exception;
use mod_perform\entity\activity\participant_instance;
use totara_core\relationship\relationship as core_relationship;
use totara_notification\recipient\recipient;

/**
 * Class relationship_recipient
 *
 * Returns all participants that have the given relationship in the given subject instance.
 *
 * @package mod_perform\recipient
 */
abstract class participant_relationship_recipient implements recipient {

    abstract protected static function get_relationship_idnumber(): string;

    public static function get_user_ids(array $data): array {
        if (!isset($data['subject_instance_id'])) {
            throw new coding_exception('Missing subject_instance_id');
        }

        return participant_instance::repository()
            ->where('subject_instance_id', $data['subject_instance_id'])
            ->where('core_relationship_id', core_relationship::load_by_idnumber(static::get_relationship_idnumber())->id)
            ->get()
            ->pluck('participant_id');
    }
}
