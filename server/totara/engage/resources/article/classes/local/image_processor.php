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
 * @package engage_article
 */

namespace engage_article\local;

use core\json_editor\document;
use core\json_editor\node\image;
use core\json_editor\node\link_media;
use core\json_editor\node\node;
use engage_article\totara_engage\resource\article;
use file_exception;
use stored_file;

/**
 * Helper method to parse through the content of an article and extract out
 * the image to use as the card/catalogue image. These images are saved
 * in the file system in their own specific area.
 *
 * @package engage_article\local
 */
final class image_processor {
    /**
     * @var int
     */
    private $resource_id;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @param int $resource_id
     * @param int $context_id
     */
    public function __construct(int $resource_id, int $context_id) {
        $this->resource_id = $resource_id;
        $this->context_id = $context_id;
    }

    /**
     * @param int $resource_id
     * @param int $context_id
     * @return image_processor
     */
    public static function make(int $resource_id, int $context_id): image_processor {
        return new static($resource_id, $context_id);
    }

    /**
     * Loop through the document and find the first link_media
     * or image node with a valid image attribute
     *
     * @param document $document
     * @return image|link_media|node|null
     */
    public function find_first_valid_image_node(document $document): ?node {
        // Find the first valid image node (can be various types of node)
        $nodes = $document->find_nodes_by_types([image::get_type(), link_media::get_type()]);

        // Run through the selected nodes & return the first with an image
        foreach ($nodes as $node) {
            if ($node instanceof image) {
                return $node;
            } else if ($node instanceof link_media) {
                $info = $node->get_info();
                if (!empty($info['image'])) {
                    return $node;
                }
            }
        }

        return null;
    }

    /**
     * Delete the image that's attached to this resource
     */
    public function delete_existing_image(): void {
        $image = $this->get_image();
        if (null !== $image) {
            $image->delete();
        }
    }

    /**
     * Find the image for an article (if it exists) or return null
     *
     * @return stored_file|null
     */
    public function get_image(): ?stored_file {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $this->context_id,
            article::get_resource_type(),
            article::IMAGE_AREA,
            $this->resource_id
        );

        if (empty($files)) {
            return null;
        }

        $files = array_filter(
            $files,
            function (stored_file $file): bool {
                return !$file->is_directory();
            }
        );

        return reset($files) ?: null;
    }

    /**
     * Search through the item's content & find the image to use as the card image.
     * Compare with the article to see if it's already saved, if not, copy it & save against the article.
     * If it's remote, download & save it.
     *
     * @param string $content
     * @param int $format
     */
    public function extract_image_from_content(string $content, int $format): void {
        global $CFG;
        // Only json editor content is supposed, all others are skipped for now
        if (empty($content) || $format != FORMAT_JSON_EDITOR) {
            return;
        }

        $document = document::create($content);
        $existing_image = $this->get_image();

        // Scan the document for the first valid image node
        $node = $this->find_first_valid_image_node($document);
        $image = null;
        if ($node instanceof link_media) {
            // This means it's a hot-linked image from a remote source
            $info = $node->get_info();
            $remote_url = $info['image'];

            // It's possible the document was wrong and it was a bad element. If that's the case we'll return nothing
            if (empty($remote_url)) {
                return;
            }

            // Cheating for behat - don't talk to remote servers.
            // Totara test site is whitelisted, all others are assumed to be relative links
            if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
                if (!strstr($remote_url, 'https://test.totaralms.com') !== false) {
                    $remote_url = $CFG->wwwroot . $remote_url;
                }
            }

            $this->get_external_image($remote_url, $existing_image);
        } else if ($node instanceof image) {
            $filename = $node->get_filename();

            require_once("{$CFG->dirroot}/lib/filelib.php");
            $fs = get_file_storage();

            $found_image = $fs->get_file(
                $this->context_id,
                article::get_resource_type(),
                article::CONTENT_AREA,
                $this->resource_id,
                '/',
                $filename
            );

            // If the content hashes are the same, we've already copied this, so carry on
            $found_hash = $found_image ? $found_image->get_contenthash() : null;
            $existing_hash = $existing_image ? $existing_image->get_contenthash() : null;

            // If there is no found image, but there's an existing image, we need to delete it
            // as it means the user has dropped any/all images from their content
            $delete_existing = $existing_image && !$found_image;

            if ($found_hash && $found_hash !== $existing_hash) {
                $this->copy_image_to_storage($found_image);
                $delete_existing = true;
            }

            if ($delete_existing && $existing_image) {
                $existing_image->delete();
            }
        } else if ($existing_image) {
            // At this point we have no image left, but should delete the existing image
            $existing_image->delete();
        }
    }

    /**
     * Download the image (id needed) & return it as a moodle_url
     *
     * @param string $remote_url
     * @param stored_file|null $existing_image
     * @return stored_file|null
     */
    private function get_external_image(string $remote_url, ?stored_file $existing_image = null): ?stored_file {
        global $CFG;

        // We prefix with a hash as two different sites can still have the same basename.
        // This forces it to be  a bit more unique to the situation
        $filename = sha1($remote_url) . '_' . basename($remote_url);
        $info = $this->get_file_info($filename);
        $hash = sha1("/{$info['contextid']}/{$info['component']}/{$info['filearea']}/{$info['itemid']}/{$info['filename']}");

        // Use the hash to check if it's the same
        // If the same, don't download it again
        if ($existing_image && $hash === $existing_image->get_pathnamehash()) {
            return $existing_image;
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        // If the file already exists, don't download it again
        if ($fs->file_exists_by_hash($hash)) {
            $file = $fs->get_file_by_hash($hash);
        } else {
            try {
                $file = $fs->create_file_from_url($info, $remote_url, null, true);
            } catch (file_exception $file_exception) {
                // The image comes from a third-party site, which could be down for any reasons.
                // We have to block the exception (and act as if there's no image), otherwise updating/creating
                // articles could fail.
                debugging('Unable to download remote image ' . $file_exception->getMessage(), DEBUG_DEVELOPER);
                $file = null;
            }
        }

        // If we were success, delete the existing image
        if ($file && $existing_image) {
            $existing_image->delete();
        }

        return $file ?? null;
    }

    /**
     * @param $filename
     * @return array
     */
    private function get_file_info($filename): array {
        return [
            'contextid' => $this->context_id,
            'component' => article::get_resource_type(),
            'filearea' => article::IMAGE_AREA,
            'itemid' => $this->resource_id,
            'filepath' => '/',
            'filename' => $filename,
        ];
    }

    /**
     * Copy the provided image into the article storage location
     *
     * @param stored_file $image
     *
     * @return stored_file|null
     */
    private function copy_image_to_storage(stored_file $image): ?stored_file {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        $info = $this->get_file_info($image->get_filename());
        return $fs->create_file_from_storedfile($info, $image);
    }

    /**
     * @param string|array  $document
     * @return string
     */
    public function get_image_alt_text($document): string {
        $document = document::create($document);
        $node = $this->find_first_valid_image_node($document);

        if ($node instanceof image) {
            return $node->get_alt_text();
        }

        return '';
    }
}