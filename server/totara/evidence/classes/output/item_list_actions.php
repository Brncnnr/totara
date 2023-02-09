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

use core\output\template;
use moodle_url;
use totara_evidence\models\evidence_item;

class item_list_actions extends template {

    /**
     * Create action icons for an evidence type
     *
     * @param evidence_item $item
     * @return item_list_actions
     */
    public static function create(evidence_item $item): self {
        return new static(array_merge($item->get_data(), [
            'edit_url' => new moodle_url('/totara/evidence/edit.php', ['id' => $item->get_id()]),
        ]));
    }

}
