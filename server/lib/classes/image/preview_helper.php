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

namespace core\image;

/**
 * A class to help on resizing image.
 */
final class preview_helper {
    /**
     * Crop mode will crop center of the image to required aspect ratio and resize to requested size
     */
    const MODE_CROP = 'crop';

    /**
     * Resize mode will resize image to fit inside required size maintaining original image aspect ratio
     */
    const MODE_RESIZE = 'resize';

    /**
     * Thumb mode will prepare canvas of required size and place resized image to fit in the center
     */
    const MODE_THUMB = 'thumb';

    /**
     * This is to cache all the image_sizes to the memory.
     * @var array
     */
    private static $image_sizes;

    /**
     * Theme is needed to override any sizes defined in theme config.
     * @var \theme_config
     */
    private $theme;

    /**
     * image_factory constructor.
     * @param \theme_config $theme
     */
    private function __construct(\theme_config $theme) {
        $this->theme = $theme;
    }

    /**
     * @param \theme_config|null $theme
     * @return preview_helper
     */
    public static function instance(?\theme_config $theme = null): preview_helper {
        if (null === $theme) {
            $theme = \theme_config::load(\theme_config::DEFAULT_THEME);
        }

        return new static($theme);
    }

    /**
     * This function will try to load every defined image size in the system, and return it for the usage.
     * Cache will be used.
     *
     * The list of dynamic sizes (for image) was built by core and many other plugins. This is for the ability
     * to introduce new sizes by different plugins rather than having a very limited predefined sizes.
     *
     * @return array
     */
    public static function get_all_sizes(): array {
        global $CFG;

        if (!isset(static::$image_sizes)) {
            static::$image_sizes = [];
        }

        if (empty(static::$image_sizes)) {
            $cache = \cache::make('core', 'image_sizes');
            $image_sizes = $cache->get('sizes');

            if (is_array($image_sizes)) {
                static::$image_sizes = $image_sizes;
            } else {
                static::$image_sizes = [];
                $core_file = "{$CFG->dirroot}/lib/db/image_sizes.php";

                // Need to load the file sizes from core first.
                if (file_exists($core_file)) {
                    $sizes = [];
                    require_once($core_file);

                    static::$image_sizes = $sizes;
                    unset($sizes);
                }

                // Now start loading from many other plugin place.
                $types = \core_component::get_plugin_types();

                foreach ($types as $type => $unused_location) {
                    $plugins = \core_component::get_plugin_list_with_file($type, 'db/image_sizes.php');

                    foreach ($plugins as $plugin => $file_path) {
                        $component = "{$type}_{$plugin}";

                        $sizes = [];
                        require_once($file_path);

                        foreach ($sizes as $size_name => $metadata) {
                            // Proper size_name check if it is debug developer mode. As some plugins
                            // can define really stupid size name that is out of order.
                            if ($CFG->debugdeveloper && stripos($size_name, "{$component}_") === false) {
                                throw new \coding_exception(
                                    "Invalid size name '{$size_name}' for the component '{$component}' " .
                                    "as it should be '{$component}_{$size_name}'"
                                );
                            }

                            // Collision check. Just debug
                            if (isset(static::$image_sizes[$size_name])) {
                                debugging(
                                    "The size name '{$size_name}' is already existing, therefore it will be skipped",
                                    DEBUG_DEVELOPER
                                );

                                continue;
                            }

                            static::$image_sizes[$size_name] = $metadata;
                        }

                        unset($sizes);
                    }
                }

                $cache->set('sizes', static::$image_sizes);
            }
        }

        return static::$image_sizes;
    }

    /**
     * Get image size and mode from preview name
     * @param string $name
     * @return array Return [$width, $height, $mode]
     */
    protected function get_size_from_name(string $name): array {
        $sizes = static::get_all_sizes();
        if (!isset($sizes[$name])) {
            throw new \file_exception('storedfileproblem', 'Invalid preview mode requested');
        }

        // Check theme override.
        $size = $this->theme->image_sizes[$name] ?? $sizes[$name];

        // Checks for $thumbnail option. By default it is true, which is for backward compatibility.
        $mode = $size['mode'] ?? 'thumb';

        return [$size['width'], $size['height'], $mode];
    }

    /**
     * Get preview image data
     * @param \stored_file $file
     * @param string $name
     *
     * @return string|false False will be returned if the problem occurs, otherwise the preview image data.
     */
    public function get_preview_content(\stored_file $file, string $name): ?string {
        [$width, $height, $mode] = $this->get_size_from_name($name);

        switch ($mode) {
            case self::MODE_CROP:
                $content = $file->crop_image($width, $height);
                break;
            case self::MODE_RESIZE:
                $content = $file->resize_image($width, $height);
                break;
            default:
                $content = $file->generate_image_thumbnail($width, $height);
        }
        return $content;
    }

    /**
     * Returns an image file that represent the given stored file as a preview.
     *
     * @param \stored_file $file
     * @param string $name Size name
     * @return \stored_file|null
     */
    public function get_file_preview(\stored_file $file, string $name): ?\stored_file {
        $context = \context_system::instance();
        $fs = get_file_storage();

        $preview_file = $fs->get_file(
            $context->id,
            'core',
            'preview',
            0,
            '/' . trim($name, '/') . '/' . $this->theme->name . '/',
            $file->get_contenthash()
        );

        if (!$preview_file) {
            $preview_file = $this->create_file_preview($file, $name);
        }

        return $preview_file;
    }

    /**
     * Creates a preview for the requested file when it does not yet exist.
     *
     * At the moment, only GIF, JPEG, PNG and SVG files are supported to have previews.
     * In the future, the support for other mimetypes can be added, too (eg. generate
     * an image preview of PDF, text documents etc).
     *
     * @param \stored_file $file
     * @param string $name Size name
     *
     * @return \stored_file|null
     */
    public function create_file_preview(\stored_file $file, string $name): ?\stored_file {
        $mime_type = $file->get_mimetype();
        $data = null;

        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/png':
                // make a preview of the image
                $data = $this->get_preview_content($file, $name);
                break;
            case 'image/svg+xml':
                // return the file as is as svg image size is automatically calculated
                // based on the viewbox it needs to display in.
                return $file;
            default:
                // unable to create the preview of this mimetype yet
                return null;
        }

        if ($data === null) {
            return null;
        }

        $context = \context_system::instance();

        $record = new \stdClass();
        $record->contextid = $context->id;
        $record->component = 'core';
        $record->filearea = 'preview';
        $record->itemid = 0;
        $record->filepath = '/' . trim($name, '/') . '/' . $this->theme->name . '/';
        $record->filename = $file->get_contenthash();

        $image_info = getimagesizefromstring($data);
        if ($image_info) {
            $record->mimetype = $image_info['mime'];
        }

        $fs = get_file_storage();
        return $fs->create_file_from_string($record, $data);
    }
}
