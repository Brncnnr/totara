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
use totara_evidence\models\evidence_item;
use totara_mvc\view;
use totara_mvc\viewable;

class view_item extends template implements viewable {

    /**
     * Create the page for viewing evidence
     *
     * @param evidence_item $item
     * @return view_item
     */
    public static function create_without_name_and_button(evidence_item $item): self {
        global $CFG;
        require_once("$CFG->dirroot/lib/tablelib.php");
        require_once("$CFG->dirroot/totara/plan/record/evidence/lib.php");

        $data = array_merge(
            customfields::create($item)->data,
            [
                'usage_table' => list_evidence_in_use($item->get_id()),
                'type' => type_name_link::create_from_type($item->type)->data,
                'user_for' => user_name_link::create_from_user($item->user)->data,
                'created_by' => user_name_link::create_from_user($item->created_by_user)->data,
                'modified_by' => user_name_link::create_from_user($item->modified_by_user)->data,
                'created_at' => $item->display_created_at,
                'modified_at' => $item->display_modified_at,
            ]
        );

        return new static($data);
    }

    /**
     * Create the page for viewing evidence with it's name and an edit button
     *
     * @param evidence_item $item
     * @return view_item
     */
    public static function create(evidence_item $item): self {
        global $PAGE;

        $template = static::create_without_name_and_button($item);
        $template->data = array_merge($template->data, [
            'header' => title_buttons::create_for_viewing_item($item, $PAGE->url, true)->data
        ]);
        return $template;
    }

    public function render() {
        return view::core_renderer()->render($this);
    }

}
