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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\data_provider;

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\dto\tree_filter_select_option;
use contentmarketplace_linkedin\entity\classification;
use core\collection;

class learning_objects_selected_filters extends learning_objects_filter_options {

    /**
     * @var int[][] Associative array of filter type => IDs array
     */
    private $selected_filters;

    /**
     * learning_objects_selected_filter_labels constructor.
     * @param array $selected_filters Associative array of filter type => IDs array
     */
    public function __construct(array $selected_filters) {
        parent::__construct($selected_filters['language']);
        $this->selected_filters = $selected_filters;
    }

    /**
     * Get a flat list of subject labels.
     *
     * @return string[]
     */
    private function get_subject_labels(): array {
        return classification::repository()
            ->select('name')
            ->where('locale_language', $this->language)
            ->where('type', constants::CLASSIFICATION_TYPE_SUBJECT)
            ->where_in('id', $this->selected_filters['subjects'])
            ->order_by('name')
            ->get()
            ->pluck('name');
    }

    /**
     * Get a flat list of the filter labels from the selected filter IDs.
     *
     * @return string[]
     */
    public function get(): array {
        $subject_labels = $this->get_subject_labels();

        $asset_type_labels = $this->get_option_labels(
            $this->get_asset_type_options(),
            $this->selected_filters['asset_type'] ?? []
        );

        $time_to_complete_labels = $this->get_option_labels(
            $this->get_time_to_complete_options(),
            $this->selected_filters['time_to_complete'] ?? []
        );

        $in_catalog_labels = $this->get_option_labels(
            $this->get_in_catalog_options(),
            $this->selected_filters['in_catalog'] ?? []
        );

        return array_merge($subject_labels, $asset_type_labels, $time_to_complete_labels, $in_catalog_labels);
    }

    /**
     * Get a filtered set of filter option labels from a set of selected IDs.
     *
     * @return string[]
     */
    private function get_option_labels(collection $options, array $selected_ids): array {
        return $options
            ->filter(function (tree_filter_select_option $option) use ($selected_ids) {
                return in_array($option->get_id(), $selected_ids);
            })
            ->sort(function (tree_filter_select_option $option_a, tree_filter_select_option $option_b) {
                return $option_a->get_label() <=> $option_b->get_label();
            })
            ->map(function (tree_filter_select_option $option) {
                return $option->get_label();
            })
            ->all();
    }

}
