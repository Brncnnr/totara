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

use core_phpunit\testcase;
use editor_weka\factory\extension_loader;
use editor_weka\extension\link;
use editor_weka\extension\text;
use editor_weka\extension\ruler;
use editor_weka\extension\attachment;
use editor_weka\extension\media;
use editor_weka\extension\list_extension;
use editor_weka\extension\emoji;
use editor_weka\extension\hashtag;
use editor_weka\extension\mention;
use editor_weka\extension\alignment;
use editor_weka\extension\layout;

class editor_weka_extension_loader_testcase extends testcase {
    /**
     * List of standard (core) extension classes.
     *
     * @var string[]
     */
    private static $standard_extension_classes = [
        link::class,
        text::class,
        ruler::class,
        attachment::class,
        media::class,
        list_extension::class,
        emoji::class,
        hashtag::class,
        mention::class,
        alignment::class,
        layout::class,
    ];

    /**
     * This test is to annoy people and force them to change the test when
     * they change anything from the function.
     *
     * @return void
     */
    public function test_get_minimal_extensions(): void {
        $extensions = extension_loader::get_minimal_required_extension_classes();
        self::assertCount(3, $extensions);

        self::assertContainsEquals(link::class, $extensions);
        self::assertContainsEquals(text::class, $extensions);
        self::assertContainsEquals(ruler::class, $extensions);
    }

    /**
     * This test is to annoy people and force them to change the test when
     * they change anything from the metadata function.
     *
     * @return void
     */
    public function test_get_standard_extensions(): void {
        self::assertEqualsCanonicalizing(
            static::$standard_extension_classes,
            extension_loader::get_standard_extension_classes()
        );
    }

    /**
     * @return void
     */
    public function test_get_all_extensions_classes(): void {
        $missing_standard_extensions = array_diff(
            static::$standard_extension_classes,
            extension_loader::get_all_extension_classes()
        );
        self::assertEquals([], $missing_standard_extensions);
    }

    /**
     * @return void
     * @dataProvider variant_data
     */
    public function test_get_extensions_for_variant(string $variant, string $type, array $test, array $extra): void {
        $result = extension_loader::get_extensions_for_variant($variant)['extensions'];

        // Extensions must JSON encode as an array
        self::assertStringStartsWith('[', json_encode($result));

        if ($type === 'exclude') {
            self::assertEquals([], array_values(array_intersect($test, $result)), 'Excluded extensions are present');
        } else if ($type === 'include') {
            self::assertEquals([], array_values(array_diff($test, $result)), 'Included extensions are missing');
        } else {
            throw new \Exception('unknown type');
        }

        self::assertEquals([], array_values(array_diff($extra, $result)), 'Extra extensions are missing');
    }

    /**
     * @return array
     */
    public function variant_data(): array {
        $basic_extensions = [text::class, link::class, list_extension::class, emoji::class];
        return [
            // standard
            ['full', 'exclude', [], []],
            ['standard', 'exclude', [], []],
            ['basic', 'include', $basic_extensions, []],
            ['simple', 'include', [text::class], []],

            // aliases
            ['description', 'exclude', [], []],

            // deprecated area-specific variants
            ['editor_weka-phpunit', 'include', $basic_extensions, []],
            ['editor_weka-behat', 'exclude', [], []],
            ['editor_weka-learn', 'exclude', [], []],
            ['editor_weka-default', 'exclude', [], []],
            ['totara_playlist-comment', 'include', $basic_extensions, [hashtag::class, mention::class]],
            ['totara_playlist-summary', 'exclude', [], [hashtag::class, mention::class]],
            ['container_workspace-description', 'exclude', [], [hashtag::class, mention::class]],
            ['container_workspace-discussion', 'exclude', [], [hashtag::class, mention::class]],
            ['engage_article-content', 'exclude', [], [hashtag::class, mention::class]],
            ['engage_article-comment', 'include', $basic_extensions, [hashtag::class, mention::class]],
            ['performelement_static_content-content', 'exclude', [], []],
        ];
    }
}
