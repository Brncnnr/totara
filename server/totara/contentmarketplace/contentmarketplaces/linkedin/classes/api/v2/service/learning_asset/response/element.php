<?php
/**
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\v2\service\learning_asset\response;

use contentmarketplace_linkedin\dto\classification;
use contentmarketplace_linkedin\dto\classification_with_path;
use stdClass;
use contentmarketplace_linkedin\api\response\element as base_element;
use contentmarketplace_linkedin\core_json\structure\learning_asset_element;
use contentmarketplace_linkedin\dto\locale;
use contentmarketplace_linkedin\dto\timespan;
use contentmarketplace_linkedin\dto\timestamp;

/**
 * @method static element create(stdClass $json_data, bool $skip_validation = false)
 */
class element extends base_element {
// phpcs:disable Totara.NamingConventions.ValidVariableName.LowerCaseUnderscores

    /**
     * @return string
     */
    protected static function get_json_structure(): string {
        return learning_asset_element::class;
    }

    /**
     * @return string
     */
    public function get_urn(): string {
        return $this->json_data->urn;
    }

    /**
     * @return string
     */
    public function get_title_value(): string {
        $title = $this->json_data->title;
        return $title->value;
    }

    /**
     * @return locale
     */
    public function get_title_locale(): locale {
        $locale = $this->json_data->title->locale;
        return new locale(
            $locale->language,
            $locale->country ?? null
        );
    }

    /**
     * @return stdClass
     */
    private function get_asset_details(): stdClass {
        return $this->json_data->details;
    }

    /**
     * Get the last updated at timestamp.
     * Unit is in milliseconds.
     *
     * @return timestamp
     */
    public function get_last_updated_at(): timestamp {
        $details = $this->get_asset_details();
        return new timestamp($details->lastUpdatedAt, timestamp::MILLISECONDS);
    }

    /**
     * Get the published at timestamp.
     * Unit is in milliseconds.
     *
     * @return timestamp
     */
    public function get_published_at(): timestamp {
        $details = $this->get_asset_details();
        return new timestamp($details->publishedAt, timestamp::MILLISECONDS);
    }

    /**
     * Get the retired at timestamp.
     * Unit is in milliseconds.
     *
     * @return timestamp|null
     */
    public function get_retired_at(): ?timestamp {
        $details = $this->get_asset_details();
        if (!isset($details->retiredAt)) {
            return null;
        }

        return new timestamp($details->retiredAt, timestamp::MILLISECONDS);
    }

    /**
     * @return string|null
     */
    public function get_description_value(): ?string {
        $details = $this->get_asset_details();
        if (!isset($details->description)) {
            return null;
        }

        return $details->description->value;
    }

    /**
     * @return locale|null
     */
    public function get_description_locale(): ?locale {
        $details = $this->get_asset_details();
        if (!isset($details->description)) {
            return null;
        }

        $locale = $details->description->locale;
        return new locale($locale->language, $locale->country ?? null);
    }

    /**
     * @return string|null
     */
    public function get_description_include_html(): ?string {
        $details = $this->get_asset_details();

        if (!isset($details->descriptionIncludingHtml)) {
            return null;
        }

        $description = $details->descriptionIncludingHtml;
        return $description->value;
    }

    /**
     * @return locale|null
     */
    public function get_description_include_html_locale(): ?locale {
        $details = $this->get_asset_details();

        if (!isset($details->descriptionIncludingHtml)) {
            return null;
        }

        $locale = $details->descriptionIncludingHtml->locale;
        return new locale($locale->language, $locale->country ?? null);
    }

    /**
     * @return string|null
     */
    public function get_short_description_value(): ?string {
        $details = $this->get_asset_details();
        if (!isset($details->shortDescription)) {
            return null;
        }

        return $details->shortDescription->value;
    }

    /**
     * @return locale|null
     */
    public function get_short_description_locale(): ?locale {
        $details = $this->get_asset_details();
        if (!isset($details->shortDescription)) {
            return null;
        }

        $locale = $details->shortDescription->locale;
        return new locale($locale->language, $locale->country ?? null);
    }

    /**
     * @return string|null
     */
    public function get_level(): ?string {
        $details = $this->get_asset_details();
        return $details->level ?? null;
    }

    /**
     * @return string|null
     */
    public function get_primary_image_url(): ?string {
        $details = $this->get_asset_details();
        $images = $details->images;

        return $images->primary ?? null;
    }

    /**
     * @return timespan|null
     */
    public function get_time_to_complete(): ?timespan {
        $details = $this->get_asset_details();
        if (!isset($details->timeToComplete)) {
            return null;
        }

        $time_to_complete = $details->timeToComplete;
        return new timespan($time_to_complete->duration, $time_to_complete->unit);
    }

    /**
     * @return string|null
     */
    public function get_web_launch_url(): ?string {
        $details = $this->get_asset_details();
        $urls = $details->urls;

        return $urls->webLaunch ?? null;
    }

    /**
     * @return string|null
     */
    public function get_sso_launch_url(): ?string {
        $details = $this->get_asset_details();
        $urls = $details->urls;

        return $urls->ssoLaunch ?? null;
    }

    /**
     * @return string|null
     */
    public function get_type(): string {
        return $this->json_data->type;
    }

    /**
     * @return string|null
     */
    public function get_availability(): ?string {
        $details = $this->get_asset_details();
        $availability = $details->availability;

        return isset($availability) ? $availability : null;
    }

    /**
     * @return classification_with_path[]
     */
    public function get_classifications(): array {
        $detail = $this->get_asset_details();
        if (!isset($detail->classifications)) {
            return [];
        }

        $classifications = [];
        foreach ($detail->classifications as $raw_classification) {
            if (!$raw_classification->associatedClassification) {
                // Skip for those classification record that does not have field associatedClassification.
                continue;
            }

            $raw_associated_classification = $raw_classification->associatedClassification;
            $classification = new classification(
                $raw_associated_classification->type,
                $raw_associated_classification->urn
            );

            $path = [];
            foreach ($raw_classification->path as $raw_parent_classification) {
                $path[] = new classification(
                    $raw_parent_classification->type,
                    $raw_parent_classification->urn
                );
            }

            $classifications[] = new classification_with_path($classification, $path);
        }

        return $classifications;
    }

// phpcs:enable
}
