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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_playlist
 */

namespace totara_playlist\local;

use core\orm\query\builder;
use engage_article\totara_engage\resource\article as article_resource;
use moodle_url;
use stdClass;
use stored_file;
use totara_engage\entity\engage_resource;
use totara_playlist\entity\playlist_resource;
use totara_playlist\local\image_processor\contract as image_processor_contract;
use totara_playlist\playlist;

/**
 * Class image_processor
 *
 * @package totara_playlist\local
 */
final class image_processor implements image_processor_contract {
    /**
     * RGB colour code of the background (stripe) of the image
     *
     * @var array
     */
    const IMAGE_BACKGROUND = [255, 255, 255];

    /**
     * RGBA colour code of the default squares (no image)
     * Transparent
     *
     * @var array
     */
    const IMAGE_DEFAULT = [0, 0, 0, 127];

    /**
     * Width x Height of the whole canvas
     *
     * @var array
     */
    const IMAGE_SIZE = [284, 150];

    /**
     * Width x Height of the whole square canvas
     *
     * @var array
     */
    const IMAGE_SQUARE_SIZE = [150, 150];

    /**
     * Width x Height of a single cell
     *
     * @var array
     */
    const CELL_SIZE = [142, 74];

    /**
     * Width x Height of a single square cell
     *
     * @var array
     */
    const CELL_SQUARE_SIZE = [75, 74];

    /**
     * How wide the lines between each cell is
     *
     * @var int
     */
    const CELL_SPACING = 1;

    /**
     * Preventing direct construction
     */
    private function __construct() {
        // No construction
    }

    /**
     * @return static
     */
    public static function make() {
        return new static();
    }

    /**
     * @param playlist $playlist
     * @param bool $squared If true, the square version of the image should be returned
     * @return stored_file|null
     */
    public function get_image_for_playlist(playlist $playlist, bool $squared = false): ?stored_file {
        /** @var stored_file[] $images */
        $images = $this->get_images_for_playlist($playlist);

        // regular image will just be card.png, squared will be card_squared
        foreach ($images as $image) {
            if (
                ($squared && $image->get_filename() === 'card_squared.png') ||
                (!$squared && $image->get_filename() === 'card.png')
            ) {
                return $image;
            }
        }

        return null;
    }

    /**
     * @param playlist $playlist
     * @return array
     */
    public function get_images_for_playlist(playlist $playlist): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        // Find the existing playlist image
        $files = $fs->get_area_files(
            $playlist->get_contextid(),
            playlist::get_resource_type(),
            playlist::IMAGE_AREA,
            $playlist->get_id()
        );
        $files = array_filter(
            $files,
            function(stored_file $file): bool {
                return !$file->is_directory();
            }
        );

        return $files;
    }

    /**
     * @param stored_file $image
     * @return moodle_url
     */
    public function get_image_url(stored_file $image): moodle_url {
        return moodle_url::make_pluginfile_url(
            $image->get_contextid(),
            $image->get_component(),
            $image->get_filearea(),
            $image->get_itemid(),
            $image->get_filepath(),
            $image->get_filename()
        );
    }

    /**
     * Grabs the attached resources, works out what we need to do &
     * generate two images. A landscape, and a square.
     *
     * @param playlist $playlist
     */
    public function update_playlist_images(playlist $playlist): void {
        global $CFG;

        $existing_images = $this->get_images_for_playlist($playlist);

        // Grab the article images that should make up this playlist image
        $images = $this->get_source_images_for_playlist($playlist->get_id());

        // Check first if any images exist, if not we can probably bail early
        if (empty($images)) {
            foreach($existing_images as $existing_image) {
                if ($existing_image) {
                    $existing_image->delete();
                }
            }

            // No images, no point generating anything further
            return;
        }

        $sources = array_fill(0, 4, null);
        foreach ($images as $i => $image) {
            $sources[$i] = $image->get_contenthash();
        }

        // If we have any existing images, compare the existing with our banners
        // and that'll tell us if we need to reload or not
        $must_reload = [
            'card.png' => [
                'square' => false,
                'existing' => null,
            ],
            'card_squared.png' => [
                'square' => true,
                'existing' => null,
            ],
        ];
        /** @var stored_file[] $existing_images */
        foreach ($existing_images as $existing_image) {
            if (isset($must_reload[$existing_image->get_filename()])) {

                // Ok, does this image already exist & is it already what we expect?
                $existing_source = json_decode($existing_image->get_source(), true);
                if ($existing_source === $sources) {
                    unset($must_reload[$existing_image->get_filename()]);
                } else {
                    $must_reload[$existing_image->get_filename()]['existing'] = $existing_image;
                }
            }
        }
        unset($existing_images);

        // If no image needs reloading, bail here
        $must_reload = array_filter($must_reload);
        if (empty($must_reload)) {
            return;
        }

        // Now we run through things twice, once for the rectangle, once for the square
        foreach ($must_reload as $filename => $image_definition) {
            $existing_image = $image_definition['existing'];

            // We want to replace, so delete the existing
            if ($existing_image) {
                $existing_image->delete();
            }

            // Needs to be generated, so let's do that
            $image_contents = $this->generate_image($images, $image_definition['square']);

            require_once("{$CFG->dirroot}/lib/filelib.php");
            $fs = get_file_storage();
            $fs->create_file_from_string(
                [
                    'contextid' => $playlist->get_contextid(),
                    'component' => playlist::get_resource_type(),
                    'filearea' => playlist::IMAGE_AREA,
                    'itemid' => $playlist->get_id(),
                    'filepath' => '/',
                    'filename' => $filename,
                    'source' => json_encode($sources),
                ],
                $image_contents
            );
            unset($image_contents);
        }
    }

    /**
     * Given a playlist, will look up the first four articles and return
     * the images involved
     *
     * @param int $playlist_id
     * @param int $max_images
     * @return array|stored_file[]
     */
    public function get_source_images_for_playlist(int $playlist_id, int $max_images = 4): array {
        global $CFG;
        require_once($CFG->libdir . '/filestorage/file_storage.php');

        // We want to find the first $max_images images for the playlist
        // Images are stored in the file system, so it's a bit of a clunky lookup
        $builder = builder::table('files', 'f');

        // Join against the engage_resource table
        $builder->join([engage_resource::TABLE, 'er'], function(builder $joining): void {
            $joining->where_raw('f.itemid = er.id');
            $joining->where('er.resourcetype', article_resource::get_resource_type());
        });

        // And then against the playlist_resource table
        $builder->join([playlist_resource::TABLE, 'pr'], 'er.id', 'pr.resourceid');

        $builder->where('f.filename', '<>', '.');
        $builder->where('f.component', article_resource::get_resource_type());
        $builder->where('f.filearea', article_resource::IMAGE_AREA);

        $builder->where('pr.playlistid', $playlist_id);
        $builder->where_not_null('er.extra');

        $builder->order_by('pr.sortorder');
        $builder->limit($max_images);

        $builder->select('f.*');

        $file_records = $builder->fetch();
        $files = [];

        $fs = get_file_storage();
        foreach ($file_records as $file_record) {
            /** @var stdClass $file_record */
            $files[] = $fs->get_file_instance($file_record);
        }

        return $files;
    }

    /**
     * @param array $images
     * @param bool $is_square
     * @return string|null
     */
    private function generate_image(array $images, $is_square = false): ?string {
        global $CFG;
        require_once($CFG->libdir . '/gdlib.php');

        [$image_w, $image_h] = $is_square ? static::IMAGE_SQUARE_SIZE : static::IMAGE_SIZE;
        [$cell_w, $cell_h] = $is_square ? static::CELL_SQUARE_SIZE : static::CELL_SIZE;
        $cell_s = static::CELL_SPACING;

        $canvas = imagecreatetransparent($image_w, $image_h);
        $colour_default = imagecolorallocatealpha($canvas, ...static::IMAGE_DEFAULT);
        imagefilledrectangle($canvas, 0, 0, $image_w, $image_h, $colour_default);
        imagecolordeallocate($canvas, $colour_default);

        $colour_image_background = imagecolorallocate($canvas, ...static::IMAGE_BACKGROUND);

        // Process each corner
        $corner = 0;
        foreach ($images as $image) {
            // $image may be a file, or it may just be a raw string (typically from unit tests)
            if (empty($image) || ($image instanceof stored_file && !$image->is_valid_image())) {
                continue;
            }

            // Grab the image contents back, and double check it has content.
            // It's rare, but possible for a corrupt image to skip other steps
            // so at this stage if we run into one, we're back to the default
            // colour instead.
            $contents = $image instanceof stored_file ? $image->get_content() : $image;
            if (empty($contents)) {
                continue;
            }
            $source_image = imagecreatefromstring($contents);
            unset($contents);
            if (!$source_image) {
                continue;
            }

            // We've got a cell, but it now needs to be rescaled to our ratio
            if ($image instanceof stored_file) {
                $image_info = $image->get_imageinfo(true);
            } else {
                $image_info = ['width' => imagesx($source_image), 'height' => imagesy($source_image)];
            }
            $cropped_image = crop_resize_image_from_image(
                $source_image,
                $image_info,
                $cell_w + static::CELL_SPACING,
                $cell_h + static::CELL_SPACING
            );
            $cropped_image = imagecreatefromstring($cropped_image);

            // X/Y starts at the top-left corner of the canvas
            // Cell 1 & 3, x = 0; otherwise x = width
            $x = $corner % 2 === 0 ? 0 : $cell_w + static::CELL_SPACING;
            // Cell 1 & 2, y = 0; otherwise y = width
            $y = $corner <= 1 ? 0 : $cell_h + static::CELL_SPACING;
            // Dest height gets a bit of stretching on the bottom row, to cover for a ugly blank line
            $dest_h = $cell_h + ($corner > 1 ? 1 : 0);
            imagefilledrectangle($canvas, $x, $y, $x + $cell_w, $y + $cell_h, $colour_image_background);
            imagecopybicubic(
                $canvas,
                $cropped_image,
                $x,
                $y,
                0,
                0,
                $cell_w,
                $dest_h,
                $cell_w,
                $cell_h
            );
            imagedestroy($cropped_image);
            $corner++;
        }

        imagecolordeallocate($canvas, $colour_image_background);

        // Draw a cross over top
        // Technically we're covering a couple of pixels of content, but it hides
        // any weirdness from odd aspect ratios (no funky black lines again)
        $colour_cross = imagecolorallocate($canvas, ...static::IMAGE_BACKGROUND);
        imagefilledrectangle($canvas, $cell_w, 0, $cell_w + $cell_s, $image_h, $colour_cross);
        imagefilledrectangle($canvas, 0, $cell_h, $image_w, $cell_h + $cell_s, $colour_cross);
        imagecolordeallocate($canvas, $colour_cross);

        imagesavealpha($canvas, true);

        // Capture the raw image content
        ob_start();
        imagepng($canvas);
        $image_data = ob_get_contents();
        ob_end_clean();

        imagedestroy($canvas);
        return $image_data;
    }
}
