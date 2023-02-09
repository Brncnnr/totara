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
 * @package editor_weka
 */
namespace editor_weka\factory;

use core\editor\variant_name;
use editor_weka\extension\alignment;
use editor_weka\extension\attachment;
use editor_weka\extension\emoji;
use editor_weka\extension\hashtag;
use editor_weka\extension\layout;
use editor_weka\extension\link;
use editor_weka\extension\list_extension;
use editor_weka\extension\media;
use editor_weka\extension\mention;
use editor_weka\extension\text;

class variant_definition {
    private static $supported = [
        variant_name::FULL,
        variant_name::STANDARD,
        variant_name::BASIC,
        variant_name::SIMPLE,
        variant_name::DESCRIPTION,
    ];

    /**
     * variant_definition constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * Returning a set of definitions based on the variants.
     * Default metadata
     *
     * @return array[]
     */
    public static function get_definitions(): array {
        // Note: this list is intended to be fixed.
        // Adding new variants is cannot be done ad-hoc, it must be considered
        // and signed off by the team responsible for maintaining editors / Weka.

        return array_merge([
            // Full functionality, used for longform content like course pages, engage article content, etc.
            variant_name::FULL => [
                'exclude_extensions' => []
            ],
            // Standard editor
            variant_name::STANDARD => [
                'exclude_extensions' => [layout::class]
            ],
            // Locked down basic editor
            variant_name::BASIC => [
                'include_extensions' => [
                    text::class,
                    link::class,
                    list_extension::class,
                    emoji::class,
                ],
            ],
            // Simple editor, just text editing and a little bit of text formatting.
            variant_name::SIMPLE => [
                'include_extensions' => [text::class],
            ],

            variant_name::DESCRIPTION => [
                'alias_for' => variant_name::STANDARD,
            ],
        ], self::get_deprecated_definitions());
    }

    /**
     * @param string $variant_name
     * @return bool
     */
    public static function in_supported(string $variant_name): bool {
        return in_array($variant_name, self::$supported) ||
            array_key_exists($variant_name, self::get_deprecated_definitions());
    }

    /**
     * @return array[]
     */
    private static function get_deprecated_definitions() {
        // DEPRECATED VARIANTS:
        // Do not use or add new area-specific variants, they are a hold-over from a
        // previous paradigm and will be removed in a future release.
        // Instead, use one of the standard named variants above.
        return [
            'editor_weka-phpunit' => ['alias_for' => 'basic'],
            'editor_weka-behat' => ['alias_for' => 'full'],
            'editor_weka-learn' => ['alias_for' => 'full'],
            'editor_weka-default' => ['alias_for' => 'full'],
            'totara_playlist-comment' => [
                'alias_for' => 'basic',
                'extra_extensions' => [hashtag::class, mention::class],
            ],
            'totara_playlist-summary' => [
                'alias_for' => 'standard',
                'extra_extensions' => [hashtag::class, mention::class],
            ],
            'container_workspace-description' => [
                'alias_for' => 'standard',
                'extra_extensions' => [hashtag::class, mention::class],
            ],
            'container_workspace-discussion' => [
                'alias_for' => 'standard',
                'extra_extensions' => [hashtag::class, mention::class],
            ],
            'engage_article-content' => [
                'alias_for' => 'full',
                'extra_extensions' => [hashtag::class, mention::class],
            ],
            'engage_article-comment' => [
                'alias_for' => 'basic',
                'extra_extensions' => [hashtag::class, mention::class],
            ],
            'performelement_static_content-content' => ['alias_for' => 'full'],
        ];
    }
}
