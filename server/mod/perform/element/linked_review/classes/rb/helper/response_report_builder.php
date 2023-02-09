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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\rb\helper;

use mod_perform\rb\helper\element_plugin_response_report_builder;
use performelement_linked_review\content_type_factory;
use rb_column_option;
use rb_filter_option;
use rb_join;

/**
 * A helper which inject linked_review plugin specific columns and data into the response report
 *
 * @package performelement_linked_review\rb
 */
class response_report_builder extends element_plugin_response_report_builder {

    /**
     * Array of content types available
     *
     * @var array
     */
    private $content_types = [];

    public function __construct() {
        // Loading all, not only enabled, to make sure that if there's data we get sensible values
        $this->content_types = content_type_factory::get_all();
    }

    /**
     * @inheritDoc
     */
    public function get_joins(): array {
        $joins = [
            new rb_join(
                'linked_review_content_response',
                'LEFT',
                '(
                    SELECT rc.section_element_id, rcr.*
                    FROM {perform_element_linked_review_content_response} rcr
                    JOIN {perform_element_linked_review_content} rc ON rcr.linked_review_content_id = rc.id
                )',
                'section_element.id = linked_review_content_response.section_element_id 
                    AND participant_instance.id = linked_review_content_response.participant_instance_id',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                ['section_element', 'participant_instance']
            ),
            new rb_join(
                'linked_review_content',
                'LEFT',
                '{perform_element_linked_review_content}',
                'linked_review_content_response.linked_review_content_id = linked_review_content.id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'linked_review_content_response'
            ),
            new rb_join(
                'linked_review_content_selector',
                'LEFT',
                '{user}',
                'linked_review_content.selector_id = linked_review_content_selector.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'linked_review_content'
            ),
            new rb_join(
                'linked_review_content_element',
                'LEFT',
                '{perform_element}',
                'linked_review_content_response.child_element_id = linked_review_content_element.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'linked_review_content_response'
            ),
            new rb_join(
                'linked_review_parent_element',
                'LEFT',
                '{perform_element}',
                'linked_review_content_element.parent = linked_review_parent_element.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'linked_review_content_element'
            ),
            // Add in element_identifier table so we can add identifier columns/filters too.
            new rb_join(
                "linked_review_parent_element_identifier",
                'LEFT',
                '{perform_element_identifier}',
                "linked_review_content_element.identifier_id = linked_review_parent_element_identifier.id",
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'linked_review_content_element'
            )
        ];

        foreach ($this->content_types as $content_type) {
            if ($helper = $content_type::get_response_report_helper()) {
                $content_joins = $helper->get_content_joins();
                foreach ($helper->get_content_joins() as $content_join) {
                    if (!$content_join instanceof rb_join) {
                        throw new \coding_exception(
                            '\performelement_linked_review\content_type_response_report_helper::get_content_joins() '.
                            'for \''.$content_type.'\' does not return instances of rb_join'
                        );
                    }
                }
                $joins = array_merge($joins, $content_joins);
            }
        }

        return $joins;
    }

    /**
     * @inheritDoc
     */
    public function get_columns(): array {
        global $DB;

        $user_name_fields = \totara_get_all_user_name_fields_join('linked_review_content_selector', null, true);
        $all_user_name_fields = \totara_get_all_user_name_fields_join('linked_review_content_selector');

        return [
            new rb_column_option(
                'additional',
                'linked_review_content_type',
                get_string('review_type', 'performelement_linked_review'),
                'linked_review_content.content_type',
                [
                    'joins' => ['linked_review_content'],
                    'displayfunc' => 'content_type',
                ]
            ),
            new rb_column_option(
                'additional',
                'linked_review_content_name',
                get_string('content_name', 'performelement_linked_review'),
                $this->get_content_name_field(),
                [
                    'joins' => $this->get_content_type_joins(),
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'additional',
                'linked_review_parent_element_title',
                get_string('parent_element_title', 'performelement_linked_review'),
                'linked_review_parent_element.title',
                [
                    'joins' => ['linked_review_parent_element'],
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'additional',
                'linked_review_parent_element_type',
                get_string('parent_element_type', 'performelement_linked_review'),
                'linked_review_parent_element.plugin_name',
                [
                    'joins' => ['linked_review_parent_element'],
                    'displayfunc' => 'element_type',
                    'extrafields' => ['data' => "linked_review_parent_element.data"]
                ]
            ),
            new rb_column_option(
                'additional',
                'linked_review_content_selected_by',
                get_string('content_selected_by', 'performelement_linked_review'),
                $DB->sql_concat_join("' '", $user_name_fields),
                [
                    'joins' => ['linked_review_content_selector'],
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'user',
                    'extrafields' => $all_user_name_fields
                ]
            ),
            new rb_column_option(
                'additional',
                'linked_review_content_selected_at',
                get_string('content_selected_at', 'performelement_linked_review'),
                'linked_review_content.created_at',
                [
                    'joins' => ['linked_review_content'],
                    'displayfunc' => 'nice_date'
                ]
            ),
        ];
    }

    /**
     * Each content type can provides their own joins we use for the name column
     *
     * @return string[]
     */
    private function get_content_type_joins(): array {
        $joins = ['linked_review_content'];
        foreach ($this->content_types as $content_type) {
            $helper = $content_type::get_response_report_helper();
            $content_joins = $helper->get_content_joins();
            foreach ($content_joins as $content_join) {
                $joins[] = $content_join->name;
            }
        }

        return $joins;
    }

    /**
     * Build field needed for the content name. Each content type can provide it's own field definition
     *
     * @return string
     */
    private function get_content_name_field(): string {
        $content_name_fields = [];
        foreach ($this->content_types as $content_type) {
            $helper = $content_type::get_response_report_helper();
            $content_name_field = $helper->get_content_name_field();
            if (!empty($content_name_field)) {
                $content_name_fields[$content_type::get_identifier()] = $content_name_field;
            }
        }

        $name_field = "CASE";
        foreach ($content_name_fields as $plugin_identifier => $content_name_field) {
            $name_field .= " WHEN linked_review_content.content_type LIKE '{$plugin_identifier}%' THEN {$content_name_field}";
        }
        $name_field .= " END";

        return $name_field;
    }

    /**
     * @inheritDoc
     */
    public function get_filters(): array {
        return [
            new rb_filter_option(
                'additional',
                'linked_review_content_type',
                get_string('review_type', 'performelement_linked_review'),
                'performelement_linked_review_type'
            ),
            new rb_filter_option(
                'additional',
                'linked_review_parent_element_title',
                get_string('parent_element_title', 'performelement_linked_review'),
                'text'
            ),
            new rb_filter_option(
                'additional',
                'linked_review_content_name',
                get_string('content_name', 'performelement_linked_review'),
                'text'
            )
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_default_columns(): array {
        return [
            [
                'type' => 'additional',
                'value' => 'linked_review_parent_element_type',
                'heading' => get_string('parent_element_type', 'performelement_linked_review'),
            ],
            [
                'type' => 'additional',
                'value' => 'linked_review_parent_element_title',
                'heading' => get_string('parent_element_title', 'performelement_linked_review'),
            ],
            [
                'type' => 'additional',
                'value' => 'linked_review_content_type',
                'heading' => get_string('review_type', 'performelement_linked_review'),
            ],
            [
                'type' => 'additional',
                'value' => 'linked_review_content_name',
                'heading' => get_string('content_name', 'performelement_linked_review'),
            ],
            [
                'type' => 'additional',
                'value' => 'linked_review_content_selected_by',
                'heading' => get_string('content_selected_by', 'performelement_linked_review'),
            ],
            [
                'type' => 'additional',
                'value' => 'linked_review_content_selected_at',
                'heading' => get_string('content_selected_at', 'performelement_linked_review'),
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_default_filters(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function get_response_extra_fields(): array {
        return [
            'linked_review_id' => "linked_review_content_element.id",
            'linked_review_plugin_name' => "linked_review_content_element.plugin_name",
            'linked_review_data' => "linked_review_content_element.data",
            'linked_review_context_id' => "linked_review_content_element.context_id",
            'linked_review_response_id' => "linked_review_content_response.id",
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_element_override_title(): string {
        return "linked_review_content_element.title";
    }

    /**
     * @inheritDoc
     */
    public function get_element_override_identifier(): string {
        return 'linked_review_parent_element_identifier.identifier';
    }

    /**
     * @inheritDoc
     */
    public function get_element_override_response(): string {
        return 'linked_review_content_response.response_data';
    }

    /**
     * @inheritDoc
     */
    public function get_element_override_type(): string {
        return 'linked_review_content_element.plugin_name';
    }

    /**
     * @inheritDoc
     */
    public function get_element_override_required(): string {
        return 'linked_review_content_element.is_required';
    }

    /**
     * @inheritDoc
     */
    public function get_additional_sourcejoins(): array {
        return [
            'linked_review_parent_element_identifier'
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_used_components(): array {
        $components = ['performelement_linked_review'];
        foreach ($this->content_types as $content_type) {
            $components[] = $content_type;
        }

        return $components;
    }

    /**
     * @inheritDoc
     */
    public function get_element_id_filter_column(): string {
        return 'linked_review_content_element.id';
    }


    /**
     * @inheritDoc
     */
    public function get_element_identifier_filter_column(): string {
        return 'linked_review_content_element.identifier_id';
    }

}