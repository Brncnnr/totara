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
 * @author  Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

final class core_json_editor_sample_documents {
    /**
     * Minimal sample document.
     *
     * @param boolean $as_string
     * @param boolean $rewrite_urls
     * @return array|string
     */
    public static function minimal(bool $as_string = false, bool $rewrite_urls = false) {
        return self::prettify([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Hooray!',
                        ],
                    ],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'kia ora koutou',
                        ],
                    ],
                ],
            ],
        ], $as_string, $rewrite_urls);
    }

    /**
     * Realistic sample document.
     *
     * @param boolean $as_string
     * @param boolean $rewrite_urls
     * @return array|string
     */
    public static function sample(bool $as_string = false, bool $rewrite_urls = false) {
        return self::prettify([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Hooray!'
                        ]
                    ]
                ],
                [
                    'type' => 'image',
                    'attrs' => [
                        'filename' => 'avocado.png',
                        'url' => '@@PLUGINFILE@@/avocado.png',
                        'alttext' => 'Avocado'
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'kia'],
                        [
                            'type' => 'text',
                            'marks' => [
                                [
                                    'type' => 'link',
                                    'attrs' => [
                                        'href' => 'https://totara.example.com/ora.jsp'
                                    ]
                                ]
                            ],
                            'text' => 'ora'
                        ],
                        ['type' => 'text', 'text' => 'koutou'],
                        ['type' => 'emoji', 'attrs' => ['shortcode' => '1F4A9']],
                    ],
                ],
                ['type' => 'ruler'],
                [
                    'type' => 'link_media',
                    'attrs' => [
                        'url' => 'https://www.youtube.com/watch?v=ParCfETbJ80',
                        'image' => 'https://i.ytimg.com/vi/ParCfETbJ80/maxresdefault.jpg',
                        'title' => 'Totara',
                        'description' => 'Totara TXP...',
                        'resolution' => ['width' => 1280, 'height' => 720],
                    ],
                ],
                [
                    'type' => 'attachments',
                    'content' => [
                        [
                            'type' => 'attachment',
                            'attrs' =>
                                [
                                    'filename' => 'eicar.com',
                                    'size' => 68,
                                    'option' => [],
                                    'url' => '@@PLUGINFILE@@/eicar.com'
                                ],
                        ],
                    ],
                ],
            ],
        ], $as_string, $rewrite_urls);
    }

    /**
     * Contains only image and attachment nodes.
     *
     * @param boolean $as_string
     * @param boolean $rewrite_urls
     * @return array|string
     */
    public static function image_only(bool $as_string = false, bool $rewrite_urls = false) {
        return self::prettify([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'image',
                    'attrs' => [
                        'filename' => 'Avocado.png',
                        'url' => '@@PLUGINFILE@@/Avocado.png',
                        'alttext' => 'Avocado'
                    ]
                ],
                [
                    'type' => 'attachments',
                    'content' => [
                        [
                            'type' => 'attachment',
                            'attrs' => [
                                'filename' => 'Cheeseburger.jpg',
                                'size' => 42195,
                                'option' => [],
                                'url' => '@@PLUGINFILE@@/Cheeseburger.jpg',
                            ],
                        ],
                    ],
                ],
            ],
        ], $as_string, $rewrite_urls);
    }

    /**
     * Contains more than 140 characters
     *
     * @param boolean $as_string
     * @param boolean $rewrite_urls
     * @return array|string
     */
    public static function long_text(bool $as_string = false, bool $rewrite_urls = false) {
        return self::prettify([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Kia ora koutou katoa'
                        ]
                    ]
                ],
                [
                    'type' => 'image',
                    'attrs' => [
                        'filename' => 'avo.png',
                        'url' => '@@PLUGINFILE@@/avo.png',
                        'alttext' => 'Mr. Avocado'
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
                        ]
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Aenean tempor sed metus quis porta.'
                        ]
                    ]
                ],
                [
                    'type' => 'link_media',
                    'attrs' => [
                        'url' => 'https://www.youtube.com/watch?v=ParCfETbJ80',
                        'image' => 'https://i.ytimg.com/vi/ParCfETbJ80/maxresdefault.jpg',
                        'title' => 'Totara',
                        'description' => 'Totara TXP...',
                        'resolution' => ['width' => 1280, 'height' => 720],
                    ],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Sed volutpat arcu eget nibh ultricies ultricies.'
                        ]
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Sed ac ligula enim. Ut posuere scelerisque lacus.'
                        ]
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Aliquam cursus leo dui, sit amet viverra velit lobortis non.'
                        ]
                    ]
                ],
            ],
        ], $as_string, $rewrite_urls);
    }

    /**
     * @param array   $doc
     * @param boolean $as_string
     * @param boolean $rewrite_urls
     * @return array|string
     */
    private static function prettify(array $doc, bool $as_string, bool $rewrite_urls) {
        $json = json_encode($doc, JSON_UNESCAPED_SLASHES);
        if ($rewrite_urls) {
            // Assumes file_rewrite_pluginfile_urls does not try to access the database.
            $json = file_rewrite_pluginfile_urls($json, 'pluginfile.php', 42, 'sample', 'dummy', 1);
        }
        return $as_string ? $json : json_decode($json, true);
    }

    /**
     * @param array $nodes
     * @return string
     */
    public static function create_json_document_from_nodes(array $nodes): string {
        return static::prettify(
            [
                'type' => 'doc',
                'content' => $nodes,
            ],
            true,
            false
        );
    }
}
