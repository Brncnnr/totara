<?php
/*
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\output\lists;

require(__DIR__ . '/../../../../config.php');

$displaydebugging = false;
if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    if (debugging()) {
        $displaydebugging = true;
    } else {
        throw new coding_exception('Invalid access detected.');
    }
}
$title = '\totara_competency\output\lists testing page';

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);
$PAGE->set_context($context);
$PAGE->set_url('/totara/competency/tests/fixtures/lists.php');
$PAGE->set_pagelayout('noblocks');
$PAGE->set_title($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if ($displaydebugging) {
    // This is intentionally hard coded - this page is not in the navigation and should only ever be used by developers.
    $msg = 'This page exists for acceptance testing, if you are here for another reason please file an improvement request.';
    echo $OUTPUT->notification($msg, 'notifysuccess');
    // We display a developer debug message as well to ensure that this doesn't not get shown during behat testing.
    debugging('This is a developer resource, please contact your system admin if you arrived here by mistake.', DEBUG_DEVELOPER);
}

$selectable = optional_param('selectable', true, PARAM_BOOL);
$has_hierarchy = optional_param('has_hierarchy', true, PARAM_BOOL);
$has_actions = optional_param('has_actions', true, PARAM_BOOL);

$extra_data  = [
    [
        'addition_data_required' => ['type' => 'bla']
    ]
];

$headers = [
    [
        'columns' => [
            [
                'value' => 'Column 1',
            ], [
                'value' => 'Column 2',
                'width' => 'sm'
            ], [
                'value' => 'Column 3',
            ], [
                'value' => 'Column 4',
                'width' => 'sm'
            ]
        ],
    ],
];
$rows = [
    [
        'columns' => [
            [
                'expand_trigger' => false,
                'value' => 'Expand trigger on different column'
            ], [
                'label' => 'Column 2',
                'expand_trigger' => true,
                'value' => '22',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'active' => true,
        'expandable' => true,
        'id' => '1',
        'extra_data' => $extra_data,
        'actions' => [
            [
                'disabled' => false,
                'hidden' => false,
                'event_key' => 'show',
                'icon' => $OUTPUT->pix_icon('i/show', 'show')
            ], [
                'disabled' => false,
                'hidden' => false,
                'event_key' => 'up',
                'icon' => 'up'
            ], [
                'disabled' => false,
                'hidden' => false,
                'event_key' => 'down',
                'icon' => $OUTPUT->pix_icon('i/down', 'down')
            ]
        ]
    ], [
        'columns' => [
            [
                'expand_trigger' => true,
                'value' => 'Two action icons'
            ], [
                'label' => 'Column 2',
                'value' => '0',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'expandable' => true,
        'id' => '2',
        'actions' => [
            [
                'disabled' => false,
                'hidden' => false,
                'event_key' => 'hide',
                'icon' => $OUTPUT->pix_icon('i/hide', 'hide')
            ], [
                'disabled' => false,
                'hidden' => false,
                'event_key' => 'delete',
                'icon' => $OUTPUT->pix_icon('i/delete', 'delete')
            ]
        ]
    ], [
        'columns' => [
            [
                'expand_trigger' => true,
                'value' => 'Standard row without actions and hierarchy'
            ], [
                'label' => 'Column 2',
                'value' => '2364',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'expandable' => true,
        'id' => '3',
        'actions' => [[]]
    ], [
        'columns' => [
            [
                'expand_trigger' => true,
                'value' => 'Row with hierarchy enabled'
            ], [
                'label' => 'Column 2',
                'value' => '364',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'expandable' => true,
        'has_children' => true,
        'id' => '4',
    ], [
        'columns' => [
            [
                'expand_trigger' => true,
                'value' => 'Row with text action link'
            ], [
                'label' => 'Column 2',
                'value' => '234',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'expandable' => true,
        'id' => '5',
        'actions' => [
            [
                'disabled' => false,
                'hidden' => false,
                'event_key' => 'link',
                'icon' => 'link'
            ]
        ]
    ], [
        'columns' => [
            [
                'expand_trigger' => true,
                'value' => 'Row with hidden action icon'
            ], [
                'label' => 'Column 2',
                'value' => '234',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'disabled' => true,
        'expandable' => true,
        'id' => '6',
        'actions' => [
            [
                'disabled' => false,
                'hidden' => true,
                'event_key' => 'delete',
                'icon' => $OUTPUT->pix_icon('i/delete', 'delete')
            ]
        ]
    ], [
        'columns' => [
            [
                'expand_trigger' => true,
                'value' => 'Non-expandable row'
            ], [
                'label' => 'Column 2',
                'value' => '52',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'id' => '7'
    ], [
        'columns' => [
            [
                'expand_trigger' => true,
                'value' => 'Row with disabled action icon'
            ], [
                'label' => 'Column 2',
                'value' => '52',
                'width' => 'sm'
            ], [
                'label' => 'Column 3',
                'value' => '00/00/00'
            ], [
                'label' => 'Column 4',
                'value' => '52',
                'width' => 'sm'
            ]
        ],
        'expandable' => true,
        'has_children' => true,
        'id' => '8',
        'actions' => [
            [
                'disabled' => true,
                'hidden' => false,
                'event_key' => 'disabled',
                'icon' => $OUTPUT->pix_icon('t/delete_grey', 'disabled', 'totara_core')
            ]
        ]
    ],
];

$list = lists::create(
    'uniqueKey',
    'My Title',
    $headers,
    $rows,
    $selectable,
    'totara_competency/test_lists_expand',
    '',
    [],
    $has_hierarchy,
    'No results',
    $has_actions
);

$PAGE->requires->js_call_amd('totara_competency/test_lists', 'init');

echo $OUTPUT->render($list);
echo $OUTPUT->footer();
