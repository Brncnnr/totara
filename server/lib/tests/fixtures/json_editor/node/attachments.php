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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */

return [
    'type' => 'attachments',
    'content' => [
        [
            'type' => 'attachment',
            'attrs' => [
                'url' => "http://example.com/totara/pluginfile.php/editor_weka/samples/15/12/example1.jpg",
                'filesize' => 1024,
                'filename' => 'example1.jpg'
            ]
        ],
        [
            'type' => 'attachment',
            'attrs' => [
                'url' => "http://example.com/totara/pluginfile.php/editor_weka/samples/15/13/example2.jpg",
                'filesize' => 1024,
                'filename' => 'example2.jpg'
            ]
        ],
        [
            'type' => 'attachment',
            'attrs' => [
                'url' => "http://example.com/totara/pluginfile.php/editor_weka/samples/15/14/example3.jpg",
                'filesize' => 1024,
                'filename' => 'example3.jpg'
            ]
        ]
    ]
];