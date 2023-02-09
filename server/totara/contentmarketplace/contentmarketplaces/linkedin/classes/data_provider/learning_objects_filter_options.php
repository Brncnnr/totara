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
use contentmarketplace_linkedin\dto\timespan;
use contentmarketplace_linkedin\dto\tree_filter_select_option;
use contentmarketplace_linkedin\dto\tree_filter_timespan_option;
use contentmarketplace_linkedin\model\classification as classification_model;
use core\collection;
use core_string_manager;
use totara_core\tui\tree\tree_node;

class learning_objects_filter_options {

    /**
     * @var string
     */
    protected $language;

    /**
     * @var core_string_manager
     */
    private $string_manager;

    /**
     * learning_objects_filter_options constructor.
     * @param string $language
     */
    public function __construct(string $language) {
        $this->language = $language;
        $this->string_manager = get_string_manager();
    }

    /**
     * Get a tree structure of subject filter options.
     *
     * @return tree_node
     */
    private function get_subject_tree(): tree_node {
        $root_node = new tree_node('subjects', $this->get_string('catalog_filter_subjects'));

        // We just want the top level of classifications (library), so we can then build the tree based upon it.
        $libraries = (new classifications())
            ->with_children()
            ->add_filters([
                'locale_language' => $this->language,
                'classification_types' => [constants::CLASSIFICATION_TYPE_LIBRARY],
            ])
            ->sort_by(classifications::SORT_BY_ALPHABETICAL)
            ->get();

        foreach ($libraries as $library) {
            $library_node = new tree_node($library->id, $library->name);
            $root_node->add_children($library_node);

            $subject_nodes = $library
                ->children
                ->map(function (classification_model $subject) {
                    return new tree_filter_select_option($subject->id, $subject->name);
                })
                ->all();
            $library_node->set_content($subject_nodes);
        }

        return $root_node;
    }

    /**
     * Get the filterable asset types.
     *
     * @return collection|tree_filter_select_option[]
     */
    protected function get_asset_type_options(): collection {
        return collection::new([
            new tree_filter_select_option(
                constants::ASSET_TYPE_COURSE,
                $this->get_string('asset_type_course_plural')
            ),
            new tree_filter_select_option(
                constants::ASSET_TYPE_VIDEO,
                $this->get_string('asset_type_video_plural')
            ),
        ]);
    }

    /**
     * Get a tree structure of asset type options.
     *
     * @return tree_node
     */
    private function get_asset_type_tree(): tree_node {
        $asset_types_root_node = new tree_node('asset_types', $this->get_string('catalog_filter_asset_type'));
        return $asset_types_root_node->set_content($this->get_asset_type_options()->all());
    }

    /**
     * Get the filterable time to complete options.
     *
     * @return collection|tree_filter_select_option[]
     */
    protected function get_time_to_complete_options(): collection {
        return collection::new([
            new tree_filter_timespan_option(
                null,
                timespan::minutes(10),
                $this->get_string('catalog_filter_timespan_under_10_minutes')
            ),
            new tree_filter_timespan_option(
                timespan::minutes(10),
                timespan::minutes(30),
                $this->get_string('catalog_filter_timespan_10_to_30_minutes')
            ),
            new tree_filter_timespan_option(
                timespan::minutes(30),
                timespan::minutes(60),
                $this->get_string('catalog_filter_timespan_30_to_60_minutes')
            ),
            new tree_filter_timespan_option(
                timespan::hours(1),
                timespan::hours(2),
                $this->get_string('catalog_filter_timespan_1_to_2_hours')
            ),
            new tree_filter_timespan_option(
                timespan::hours(2),
                timespan::hours(3),
                $this->get_string('catalog_filter_timespan_2_to_3_hours')
            ),
            new tree_filter_timespan_option(
                timespan::hours(3),
                null,
                $this->get_string('catalog_filter_timespan_over_3_hours')
            ),
        ]);
    }

    /**
     * Get a tree structure of time to complete filter options.
     *
     * @return tree_node
     */
    private function get_time_to_complete_tree(): tree_node {
        $root_node = new tree_node('time_to_complete', $this->get_string('catalog_filter_time_to_complete'));
        return $root_node->set_content($this->get_time_to_complete_options()->all());
    }

    /**
     * Get the filterable in catalog options.
     *
     * @return collection|tree_filter_select_option[]
     */
    protected function get_in_catalog_options(): collection {
        return collection::new(
            [
                new tree_filter_select_option('yes', $this->get_string('catalog_filter_in_catalog')),
                new tree_filter_select_option('no', $this->get_string('catalog_filter_not_in_catalog')),
            ]
        );
    }

    /**
     * Get a tree structure of in catalog filter options.
     *
     * @return tree_node
     */
    private function get_in_catalog_tree(): tree_node {
        $root_node = new tree_node('in_catalog', $this->get_string('catalog_filter_in_catalog'));
        return $root_node->set_content($this->get_in_catalog_options()->all());
    }

    /**
     * Get the filter option available for filtering learning objects.
     *
     * @return tree_node[][]
     */
    public function get(): array {
        return [
            'subjects' => [$this->get_subject_tree()],
            'asset_type' => [$this->get_asset_type_tree()],
            'time_to_complete' => [$this->get_time_to_complete_tree()],
            'in_catalog' => [$this->get_in_catalog_tree()],
        ];
    }

    /**
     * Get the specified language string in the language that this provider was requested in.
     *
     * @param string $identifier
     * @param string $component
     * @param mixed $a
     * @return string
     */
    protected function get_string(string $identifier, string $component = 'contentmarketplace_linkedin', $a = null): string {
        return $this->string_manager->get_string($identifier, $component, $a, $this->language);
    }

}
