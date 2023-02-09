<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams;

use lang_string;

defined('MOODLE_INTERNAL') || die;

$tabs = [
    'catalog' => [
        'name' => new lang_string('tab:catalog', 'totara_msteams'),
        'url' => '/totara/msteams/tabs/catalog.php',
        'redirectUrl' => '/totara/msteams/tabs/catalog.php?redirect=1',
        'externalUrl' => '/totara/catalog/index.php',
        'dependencies' => [],
        'features' => [],
    ],
    'mylearning' => [
        'name' => new lang_string('tab:mylearning', 'totara_msteams'),
        'url' => '/totara/msteams/tabs/mylearning.php',
        'redirectUrl' => '/totara/msteams/tabs/mylearning.php?redirect=1',
        'dependencies' => [
            'block_current_learning' => 2020101200,
        ],
        'features' => [],
    ],
    'library' => [
        'name' => new lang_string('tab:library', 'totara_msteams'),
        'url' => '/totara/msteams/tabs/contributions.php',
        'redirectUrl' => '/totara/engage/your_resources.php',
        'dependencies' => [],
        'features' => [
            'engage_resources'
        ],
    ],
    'help' => [
        'name' => new lang_string('tab:help', 'totara_msteams'),
        'url' => '/totara/msteams/tabs/help.php',
        'redirectUrl' => '/totara/msteams/help.php',
        'dependencies' => [],
        'features' => [],
    ],
];
