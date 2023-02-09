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
use tabobject;
use tabtree;
use totara_evidence\customfield_area;
use totara_evidence\models\evidence_type;
use totara_mvc\view;

class header extends template {

    /**
     * @param string|null $title Page header text
     * @param array|null $backlink Link back to the previous screen
     * @param array|null $buttons Primary action buttons
     * @param tabtree|null $tabs Tabbed navigation menu
     * @return header
     */
    public static function create(?string $title = null, ?array $backlink = null,
                                  ?array $buttons = null, ?tabtree $tabs = null): self {
        $data = [];

        if (isset($title)) {
            $data['title'] = $title;
        }

        if (isset($backlink)) {
            $data['backlink'] = $backlink;
        }

        if (isset($buttons)) {
            $data['has_buttons'] = true;
            $data['buttons'] = $buttons;
        }

        if (isset($tabs)) {
            $data['tabs'] = view::core_renderer()->render($tabs);
        }

        return new static($data);
    }

    /**
     * Create a header specifically for use with evidence types
     *
     * @param evidence_type $type
     * @param bool $with_tabs
     * @param string|null $current_tab
     * @return header
     */
    public static function create_for_type(evidence_type $type = null, bool $with_tabs = true, string $current_tab = null): self {
        if ($with_tabs) {
            $data = [
                'title' => $type ? $type->get_display_name() : get_string('add_an_evidence_type', 'totara_evidence'),
                'backlink' => [
                    'url'   => new moodle_url('/totara/evidence/type/index.php'),
                    'label' => get_string('navigation_back_to_manage_types', 'totara_evidence')
                ],
                'tabs' => view::core_renderer()->render(
                    new tabtree(
                        [
                            new tabobject(
                                'general',
                                new moodle_url(
                                    '/totara/evidence/type/edit.php',
                                    $type ? ['id' => $type->get_id()] : []
                                ),
                                get_string('general')
                            ),
                            new tabobject(
                                'custom_fields',
                                $type ? customfield_area\evidence::get_url($type->get_id()) : null,
                                get_string('custom_fields', 'totara_evidence')
                            )
                        ],
                        $current_tab,
                        $type ? [] : ['custom_fields']
                    )
                )
            ];
        } else {
            $data = [
                'backlink' => [
                    'url'   => customfield_area\evidence::get_url($type->get_id()),
                    'label' => get_string('navigation_back_to_x', 'totara_evidence', $type->get_display_name())
                ]
            ];
        }

        return new self($data);
    }

}
