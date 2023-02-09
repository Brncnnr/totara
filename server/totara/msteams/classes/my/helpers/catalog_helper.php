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

namespace totara_msteams\my\helpers;

use totara_catalog\catalog_retrieval;
use totara_catalog\dataformatter\formatter;
use totara_catalog\local\filter_handler;
use totara_catalog\local\required_dataholder;
use totara_catalog\provider;
use totara_catalog\provider_handler;
use totara_core\advanced_feature;

/**
 * A helper class to retrieve catalog entries.
 */
final class catalog_helper {
    /**
     * The required data holders.
     * @var integer[]
     */
    private const DATA_HOLDERS = [
        'fullname' => formatter::TYPE_PLACEHOLDER_TITLE,
        'name' => formatter::TYPE_PLACEHOLDER_TITLE,
        'catalog_learning_type' => formatter::TYPE_PLACEHOLDER_TEXT,
        'course_category' => formatter::TYPE_PLACEHOLDER_TEXT,
        'summary_rich' => formatter::TYPE_PLACEHOLDER_RICH_TEXT,
        'image' => formatter::TYPE_PLACEHOLDER_IMAGE,
    ];

    /**
     * Search the catalog as the current user.
     *
     * @param string|null $query set null or empty string to return everything available
     * @param integer $from
     * @param integer $limit
     * @param string $orderbykey featured, score, etc. default to featured
     * @return array of objects comprising {name, type, category, summary, image?: {url, alt}, link?: {url, label}}
     */
    public static function search(?string $query, int $from, int $limit, string $orderbykey = 'featured'): array {
        if (advanced_feature::is_disabled('totara_msteams')) {
            return [];
        }

        $fts = filter_handler::instance()->get_full_text_search_filter()->datafilter;
        // Treat empty string as null, so that the function could return all items.
        if ($query === '') {
            $query = null;
        }
        $fts->set_current_data($query);

        $catalog = new catalog_retrieval();
        $obj = $catalog->get_page_of_objects($limit, $from, -1, $orderbykey);
        $objects = $obj->objects;

        if (empty($objects)) {
            return [];
        }

        $providerhandler = provider_handler::instance();
        $providers = [];
        $requireddataholders = [];
        foreach ($objects as $object) {
            $type = $object->objecttype;
            if (!isset($providers[$type])) {
                $providers[$type] = $providerhandler->get_provider($type);
                $requireddataholders[$type] = self::get_required_dataholders($providers[$type]);
            }
        }

        $formattedobjects = $providerhandler->get_data_for_objects($objects, $requireddataholders);
        return self::format_formatted_objects($formattedobjects, $providers);
    }

    /**
     * Get the array of data holders.
     *
     * @param provider $provider
     * @return array
     */
    private static function get_required_dataholders(provider $provider): array {
        $requireddataholders = [];

        foreach (self::DATA_HOLDERS as $key => $type) {
            $dataholders = $provider->get_dataholders($type);
            if (!empty($dataholders[$key])) {
                $dataholder = $dataholders[$key];
                $requireddataholders[] = new required_dataholder($dataholder, $type);
            }
        }

        return $requireddataholders;
    }

    /**
     * Return only interesting information from the formatted objects.
     *
     * @param array $formattedobjects
     * @param provider[] $providers
     * @return array
     */
    private static function format_formatted_objects(array $formattedobjects, array $providers): array {
        return array_map(function($object) use ($providers) {
            $name = '';
            $type = '';
            $category = '';
            $summary = '';
            $image = null;
            $link = null;
            $provider = $providers[$object->objecttype] ?? null;
            if ($provider) {
                $obj = $provider->get_details_link($object->objectid);
                if (isset($obj->button->url)) {
                    $link = (object)['url' => $obj->button->url, 'label' => $obj->button->label ?? 'Open'];
                }
            }
            foreach ($object->data as $data) {
                if (isset($data['fullname'])) {
                    $name = (string)$data['fullname'];
                }
                // NOTE: The data provider for engage contents sets 'name' instead of 'fullname'
                if (isset($data['name']) && $name === '') {
                    $name = (string)$data['name'];
                }
                if (isset($data['catalog_learning_type'])) {
                    $type = (string)$data['catalog_learning_type'];
                }
                if (isset($data['course_category'])) {
                    $category = (string)$data['course_category'];
                }
                if (isset($data['summary_rich'])) {
                    $summary = strip_tags($data['summary_rich'], '<br><a>');
                }
                if (isset($data['image'])) {
                    $image = (object)$data['image'];
                }
            }
            return (object)['name' => $name, 'type' => $type, 'objecttype' => $object->objecttype, 'category' => $category, 'summary' => $summary, 'image' => $image, 'link' => $link];
        }, $formattedobjects);
    }
}
