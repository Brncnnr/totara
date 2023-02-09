<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

namespace mobile_findlearning;
use totara_catalog\output\item;
use totara_catalog\provider;
use totara_catalog\provider_handler;
use totara_catalog\dataformatter\formatter;
use totara_catalog\local\required_dataholder;
use mobile_findlearning\config;

defined('MOODLE_INTERNAL') || die();

class item_mobile extends item {
    /**
     * Gets all of the dataholders which are required to populate the item template.
     *
     * @param provider $provider
     * @return required_dataholder[]
     */
    public static function get_required_dataholders(provider $provider): array {
        $titledataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
        $textdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TEXT);
        $icondataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_ICON);
        $iconsdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_ICONS);

        $config = config::instance();

        $requireddataholders = [];

        $titledataholderkey = $provider->get_config('item_title');
        if (empty($titledataholders[$titledataholderkey])) {
            // Default to first title dataholder.
            $firsttitledataholder = reset($titledataholders);
            $titledataholderkey = $firsttitledataholder->key;
        }
        $dataholder = $titledataholders[$titledataholderkey];
        $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TITLE);

        if (!empty($textdataholders[$provider->get_config('item_description')])) {
            $dataholder = $textdataholders[$provider->get_config('item_description')];
            $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TEXT);
        }

        $additionaltexts = $provider->get_config('item_additional_text');
        $additionaltextcount = $config->get_value('item_additional_text_count');
        $i = 0;
        foreach ($additionaltexts as $additionaltext) {
            if (!empty($textdataholders[$additionaltext])) {
                $dataholder = $textdataholders[$additionaltext];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TEXT);
            }

            $i++;
            if ($i == $additionaltextcount) {
                break;
            }
        }

        $additionaliconsenabled = $config->get_value('item_additional_icons_enabled');
        $additionalicons = $provider->get_config('item_additional_icons');
        if ($additionaliconsenabled) {
            foreach ($additionalicons as $additionalicon) {
                if (empty($iconsdataholders[$additionalicon])) {
                    continue;
                }

                $dataholder = $iconsdataholders[$additionalicon];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_ICONS);
            }
        }

        $imagedataholderkey = $provider->get_data_holder_config('image');
        if (!empty($imagedataholderkey) && $config->get_value('image_enabled')) {
            $imagedataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_IMAGE);
            if (!empty($imagedataholders[$imagedataholderkey])) {
                $dataholder = $imagedataholders[$imagedataholderkey];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_IMAGE);
            }
        }

        $progressdataholderkey = $provider->get_data_holder_config('progressbar');
        if (!empty($progressdataholderkey) && $config->get_value('progress_bar_enabled')) {
            $progressdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_PROGRESS);
            if (!empty($progressdataholders[$progressdataholderkey])) {
                $dataholder = $progressdataholders[$progressdataholderkey];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_PROGRESS);
            }
        }

        /**
         * NOTE: Overriden - we now fetch the database values instead as this pre-formats summaries in unexpected ways.
         * $richtextdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_RICH_TEXT);
         * if (!empty($richtextdataholders['summary_rich'])) {
         *     $dataholder = $richtextdataholders['summary_rich'];
         *     $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_RICH_TEXT);
         * }
         */

        return $requireddataholders;
    }

    /**
     * $object contains:
     * - int id (from catalog table)
     * - int objectid
     * - string objecttype
     * - int contextid
     * - bool featured (optional, depending on configuration)
     * - mixed[$dataholder->type][$dataholder->key] data (which has already been formatted)
     *
     * @param \stdClass $object
     * @return item
     */
    public static function create(\stdClass $object) {
        $provider = provider_handler::instance()->get_provider($object->objecttype);

        $config = config::instance();

        $data = new \stdClass();
        $data->itemid = $object->id;
        $data->featured = !empty($object->featured);
        $data->objecttype = $object->objecttype;

        $titledataholderkey = $provider->get_config('item_title');
        $titledataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
        if (empty($titledataholders[$titledataholderkey])) {
            // Default to first title dataholder.
            $titledataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
            $firsttitledataholder = reset($titledataholders);
            $titledataholderkey = $firsttitledataholder->key;
        }
        $data->title = $object->data[formatter::TYPE_PLACEHOLDER_TITLE][$titledataholderkey] ?? '';

        // Provide the URL to redirect to if details popup is disabled.
        $detailslink = $provider->get_details_link($object->objectid);
        $data->redirecturl = $detailslink->button->url;

        // Note:: Hero data removed.

        $data->image_enabled = true; // Hardcoded, could be a setting.
        $imagedataholderkey = $provider->get_data_holder_config('image');
        $data->image = $object->data[formatter::TYPE_PLACEHOLDER_IMAGE][$imagedataholderkey] ?? '';

        $data->description_enabled = true; // Hardcoded, could be a setting.
        $descriptiondataholderkey = $provider->get_config('item_description');
        $data->description = $object->data[formatter::TYPE_PLACEHOLDER_TEXT][$descriptiondataholderkey] ?? '';

        // Note:: Progress data removed.
        // Note:: Custom fields removed.
        // Note:: Icon placeholdes removed.

        return new static((array)$data);
    }
}
