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

namespace totara_evidence\output;

use core\entity\user;
use core\output\template;
use moodle_url;
use totara_evidence\models\evidence_type;

class type_name_link extends template {

    /**
     * Create a type name link from an evidence type model
     *
     * @param evidence_type $type
     *
     * @return type_name_link
     */
    public static function create_from_type(evidence_type $type): self {
        return new static([
            'can_manage' => $type::can_manage(),
            'type_id' => $type->get_id(),
            'type_name' => $type->get_display_name(),
            'user_id' => user::logged_in()->id,
            'view_url' => new moodle_url('/totara/evidence/type/view.php', ['id' => $type->get_id()]),
        ]);
    }

    /**
     * Create a type name link from report data
     *
     * @param int $type_id
     * @param string $type_name
     * @param bool $can_manage
     *
     * @return type_name_link
     */
    public static function create_from_report(int $type_id, string $type_name, bool $can_manage): self {
        return new static([
            'can_manage' => $can_manage,
            'type_id' => $type_id,
            'type_name' => $type_name,
            'user_id' => user::logged_in()->id,
            'view_url' => new moodle_url('/totara/evidence/type/view.php', ['id' => $type_id]),
        ]);
    }

}
