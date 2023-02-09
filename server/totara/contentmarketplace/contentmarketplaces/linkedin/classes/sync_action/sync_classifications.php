<?php
/**
 * This file is part of Totara Core
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
namespace contentmarketplace_linkedin\sync_action;

use contentmarketplace_linkedin\api\v2\api;
use contentmarketplace_linkedin\api\v2\service\learning_classification\query\locale_and_type;
use contentmarketplace_linkedin\api\v2\service\learning_classification\response\collection;
use contentmarketplace_linkedin\api\v2\service\learning_classification\service;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\data_provider\locales;
use contentmarketplace_linkedin\dto\locale;
use contentmarketplace_linkedin\entity\classification;
use progress_trace;
use coding_exception;
use totara_contentmarketplace\sync\external_sync;
use totara_contentmarketplace\sync\sync_action;
use totara_core\http\client;

class sync_classifications extends sync_action implements external_sync {
    /**
     * @var client|null
     */
    private $client;

    /**
     * An array of classification types, the values can be either or all of the following:
     * + @see constants::CLASSIFICATION_TYPE_LIBRARY
     * + @see constants::CLASSIFICATION_TYPE_SUBJECT
     * + @see constants::CLASSIFICATION_TYPE_TOPIC
     *
     * Please note the sort order of the constants.
     *
     * @var string[]
     */
    private $classification_types;

    /**
     * The target locales that we would want to sync from linkedin learning
     * for the classifications.
     *
     * @var locale[]
     */
    private $target_locales;

    /**
     * sync_classifications constructor.
     * @param bool $is_initial_run
     * @param progress_trace|null $trace
     * @param client|null $client
     */
    public function __construct(
        bool $is_initial_run = false,
        ?progress_trace $trace = null,
        ?client $client = null
    ) {
        parent::__construct($is_initial_run, $trace);
        $this->client = $client;

        $this->classification_types = [
            constants::CLASSIFICATION_TYPE_LIBRARY,
            constants::CLASSIFICATION_TYPE_SUBJECT,
            constants::CLASSIFICATION_TYPE_TOPIC
        ];

        $this->target_locales = locales::get_default();
    }

    /**
     * @param locale[] $locales
     * @return void
     */
    public function set_locales(locale ...$locales): void {
        if (empty($locales)) {
            debugging("Cannot set the list of target locales to empty", DEBUG_DEVELOPER);
            return;
        }

        $this->target_locales = $locales;
    }

    /**
     * The value of $classification_types can be either of the following:
     * + @see constants::CLASSIFICATION_TYPE_LIBRARY
     * + @see constants::CLASSIFICATION_TYPE_SUBJECT
     * + @see constants::CLASSIFICATION_TYPE_TOPIC
     *
     * @param string[] $classification_types
     * @return void
     */
    public function set_classification_types(string ...$classification_types): void {
        if (empty($classification_types)) {
            return;
        }

        $this->classification_types = [];

        foreach ($classification_types as $classification_type) {
            constants::validate_classification_type($classification_type);

            if (constants::CLASSIFICATION_TYPE_SKILL === $classification_type) {
                debugging(
                    "The sync action for classification does not accept '{$classification_type}'",
                    DEBUG_DEVELOPER
                );
                continue;
            }

            $this->classification_types[] = $classification_type;
        }
    }

    /**
     * @return bool
     */
    public function is_skipped(): bool {
        if (!config::client_id() || !config::client_secret()) {
            return true;
        }

        if ($this->is_initial_run && config::completed_initial_sync_classification()) {
            return true;
        }

        if (!$this->is_initial_run && !config::completed_initial_sync_classification()) {
            return true;
        }

        return false;
    }

    /**
     * @param client $client
     * @return void
     */
    public function set_api_client(client $client): void {
        $this->client = $client;
    }

    /**
     * @return void
     */
    public function invoke(): void {
        if ($this->is_skipped()) {
            // The sync action should be skipped.
            return;
        } else if (null === $this->client) {
            throw new coding_exception("Cannot run sync when client is not set");
        }

        $api = api::create($this->client);
        $repository = classification::repository();

        foreach ($this->classification_types as $classification_type) {
            $this->trace->output("Sync for classification type {$classification_type}");

            foreach ($this->target_locales as $target_locale) {
                $this->trace->output("Sync for locale {$target_locale}", 4);

                $locale_and_type = new locale_and_type($target_locale, $classification_type);
                $locale_and_type->set_count(100);

                $service = new service($locale_and_type);

                while (true) {
                    /** @var collection $collection */
                    $collection = $api->execute($service);
                    $elements = $collection->get_elements();

                    if (empty($elements)) {
                        $this->trace->output(
                            "There are no records for classification {$classification_type} with locale {$target_locale}",
                            4
                        );

                        break;
                    }

                    foreach ($elements as $element) {
                        $classification = null;
                        $urn = $element->get_urn();

                        if (!$this->is_initial_run) {
                            // Not an initial run, hence we run thru database look up.
                            $classification = $repository->find_by_urn($urn);
                        }

                        if (null === $classification) {
                            $classification = new classification();
                        }

                        $locale = $element->get_name_locale();

                        $classification->type = $element->get_type();
                        $classification->urn = $urn;
                        $classification->locale_language = $locale->get_lang();
                        $classification->locale_country = $locale->get_country();
                        $classification->name = $element->get_name_value();

                        $classification->save();
                    }

                    $pagination = $collection->get_paging();
                    if (!$pagination->has_next()) {
                        // Break out the while loop.
                        break;
                    }

                    $next_url = $pagination->get_next_link();

                    $locale_and_type->clear();
                    $locale_and_type->set_parameters_from_paging_url($next_url);
                }
            }
        }

        if ($this->is_initial_run) {
            config::save_completed_initial_sync_classification(true);
        }
    }
}