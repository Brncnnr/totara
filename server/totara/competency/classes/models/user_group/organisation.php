<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models\user_group;

use context_system;
use core\orm\collection;
use core\orm\entity\entity;
use hierarchy_organisation\entity\organisation as organisation_entity;
use totara_competency\models\user_group;
use totara_competency\user_groups;

class organisation extends user_group {

    /**
     * {@inheritdoc}
     */
    public static function load_by_id(int $id): user_group {
        /** @var organisation_entity $position */
        $org = organisation_entity::repository()->find($id);
        return self::load_by_entity($org);
    }

    /**
     * {@inheritdoc}
     */
    public static function load_by_entity(?entity $org): user_group {
        if ($org) {
            $is_deleted = false;
            $name = $org->display_name;
            $id = $org->id;
        } else {
            $is_deleted = true;
            $name = get_string('deleted_organisation', 'totara_competency');
            $id = 0;
        }
        return new static($id, $name, $is_deleted);
    }

    /**
     * {@inheritdoc}
     */
    public static function load_user_groups(array $ids): collection {
        return organisation_entity::repository()->where_in('id', $ids)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function can_view(): bool {
        $systemcontext = context_system::instance();
        return has_capability('totara/hierarchy:vieworganisation', $systemcontext);
    }

    /**
     * {@inheritdoc}
     */
    public function get_type(): string {
        return user_groups::ORGANISATION;
    }

}