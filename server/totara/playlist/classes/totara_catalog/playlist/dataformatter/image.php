<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_playlist
 * @category totara_catalog
 */

namespace totara_playlist\totara_catalog\playlist\dataformatter;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_playlist\local\image_processor;
use totara_playlist\playlist;

class image extends formatter {

    /**
     * @param string $plistidfield the database field containing the playlist id
     * @param string $altfield the database field containing the image alt text
     */
    public function __construct(string $plistidfield, string $altfield) {
        $this->add_required_field('plistid', $plistidfield);
        $this->add_required_field('alt', $altfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_IMAGE,
        ];
    }

    /**
     * Given a plist id, gets the image.
     *
     * @param array $data
     * @param \context $context
     * @return \stdClass
     */
    public function get_formatted_value(array $data, \context $context): \stdClass {
        global $OUTPUT, $PAGE;

        if (!array_key_exists('plistid', $data)) {
            throw new \coding_exception("plist image data formatter expects 'plistid'");
        }

        if (!array_key_exists('alt', $data)) {
            throw new \coding_exception("plist image data formatter expects 'alt'");
        }

        $image = new \stdClass();
        $processor = image_processor::make();
        $playlist = playlist::from_id($data['plistid']);
        if ($imagefile = $processor->get_image_for_playlist($playlist)) {
            $image->url = $processor->get_image_url($imagefile)->out(
                false,
                [
                    'hash' => $imagefile->get_contenthash(),
                    'theme' => $PAGE->theme->name,
                    'preview' => 'totara_catalog_medium'
                ]
            );
        } else {
            $image->url = $OUTPUT->image_url("default_collection", 'totara_playlist')->out();
        }

        $image->alt = format_string($data['alt'], true, ['context' => $context]);
        return $image;
    }
}
