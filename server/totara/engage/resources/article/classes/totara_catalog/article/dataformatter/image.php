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
 * @package engage_article
 * @category totara_catalog
 */

namespace engage_article\totara_catalog\article\dataformatter;

defined('MOODLE_INTERNAL') || die();

use engage_article\theme\file\article_image;
use totara_catalog\dataformatter\formatter;
use engage_article\local\image_processor;

class image extends formatter {

    /**
     * @param string $ridfield the database field containing the resource id associated with the article
     * @param string $altfield the database field containing the image alt text
     * @param string $owner    the database field containing the user id that created the article
     */
    public function __construct(string $ridfield, string $altfield, string $owner) {
        $this->add_required_field('resourceid', $ridfield);
        $this->add_required_field('alt', $altfield);
        $this->add_required_field('owner', $owner);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_IMAGE,
        ];
    }

    /**
     * Given a article id, gets the image.
     *
     * @param array $data
     * @param \context $context
     * @return \stdClass
     */
    public function get_formatted_value(array $data, \context $context): \stdClass {
        global $PAGE, $USER;

        if (!array_key_exists('resourceid', $data)) {
            throw new \coding_exception("article image data formatter expects 'resourceid'");
        }

        if (!array_key_exists('owner', $data)) {
            throw new \coding_exception("article image data formatter expects 'owner'");
        }

        if (!array_key_exists('alt', $data)) {
            throw new \coding_exception("article image data formatter expects 'alt'");
        }

        $image = new \stdClass();
        $context = \context_user::instance($data['owner']);
        $processor = image_processor::make($data['resourceid'], $context->id);
        if ($imagefile = $processor->get_image()) {
            $url = \moodle_url::make_pluginfile_url(
                $context->id,
                $imagefile->get_component(),
                $imagefile->get_filearea(),
                $data['resourceid'],
                '/',
                $imagefile->get_filename(),
                false
            );

            $image->url = $url->out(false,
                [
                    'hash' => $imagefile->get_contenthash(),
                    'theme' => $PAGE->theme->name,
                    'preview' => 'totara_catalog_medium'
                ]
            );
        } else {
            $article_image = new article_image($PAGE->theme);
            $article_image->set_tenant_id($USER->tenantid ?? 0);
            $image->url = $article_image->get_current_or_default_url()->out();
        }

        $image->alt = format_string($data['alt'], true, ['context' => $context]);
        return $image;
    }
}
