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

class title_buttons extends template {

    /**
     * Create the title header - and edit button if possible - for when viewing an evidence item
     *
     * @param evidence_item $item
     * @param moodle_url $current_url The current page URL to return to
     * @param bool $with_small_title Should the title be smaller - i.e. a subtitle?
     * @return title_buttons
     */
    public static function create_for_viewing_item(evidence_item $item, moodle_url $current_url,
                                                   bool $with_small_title = true): self {
        $data = [
            'title' => $item->get_display_name(),
            'smaller_title' => $with_small_title,
        ];

        if (!$item->can_modify()) {
            return new static($data);
        }

        $edit_url = new moodle_url('/totara/evidence/edit.php', [
            'id' => $item->get_id(),
            'return_to' => $current_url->out_as_local_url(),
        ]);

        $buttons = [
            'has_buttons' => true,
            'buttons' => [
                'url' => $edit_url,
                'label' => get_string('edit_this_item', 'totara_evidence')
            ],
        ];

        return new static(array_merge($buttons, $data));
    }

}
