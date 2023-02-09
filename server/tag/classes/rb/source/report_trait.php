<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package core_tag
 */

namespace core_tag\rb\source;

defined('MOODLE_INTERNAL') || die();

trait report_trait {
    /** @var array */
    private $core_tag_join = [];
    /** @var array */
    private $core_tag_field = [];

    /**
     * Adds the tag tables to the $joinlist array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     $type table
     * @param string $field Name of course id field to join on
     * @return bool True
     */
    protected function add_core_tag_tables($component, $itemtype, &$joinlist, $join, $field) {
        $this->core_tag_join[$component][$itemtype] = $join;
        $this->core_tag_field[$component][$itemtype] = $field;

        // Create a join for each tag in the collection.
        $tags = \core_tag\report_builder_tag_loader::get_tags($component, $itemtype);
        foreach ($tags as $tag) {
            $tagid = $tag->id;
            $name = "{$itemtype}_tag_$tagid";
            $joinlist[] = new \rb_join(
                $name,
                'LEFT',
                '{tag_instance}',
                "($name.itemid = $join.$field AND $name.tagid = $tagid " .
                    "AND $name.itemtype = '{$itemtype}' AND $name.component = '{$component}')",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                $join
            );
        }

        return true;
    }

    /**
     * Adds some common tag info to the $columnoptions array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @return bool True
     */
    protected function add_core_tag_columns($component, $itemtype, &$columnoptions) {
        $join = $this->core_tag_join[$component][$itemtype];
        $field = $this->core_tag_field[$component][$itemtype];
        if ($component === 'core' && ($itemtype === 'course' || $itemtype === 'cohort')) {
            $prefix = '';
        } else {
            $prefix = $itemtype . '_';
        }
        $columnoptions[] = new \rb_column_option(
            $prefix . 'tags',
            'tagnames',
            get_string('tags', 'totara_reportbuilder'),
            "{$join}.{$field}",
            array(
                'nosort' => true,
                'displayfunc' => 'tag_list',
                'extracontext' => ['component' => $component, 'itemtype' => $itemtype],
                'iscompound' => true,
                'joins' => $join,
            )
        );

        // Only get the tags in the collection for this item type.
        $tags = \core_tag\report_builder_tag_loader::get_tags($component, $itemtype);

        // Create a on/off field for every official tag.
        foreach ($tags as $tag) {
            $tagid = $tag->id;
            $name = format_string($tag->name);
            $ajoin = "{$itemtype}_tag_$tagid";
            $columnoptions[] = new \rb_column_option(
                $prefix . 'tags',
                $ajoin,
                get_string('taggedx', 'totara_reportbuilder', $name),
                "CASE WHEN $ajoin.id IS NOT NULL THEN 1 ELSE 0 END",
                array(
                    'joins' => $ajoin,
                    'displayfunc' => 'yes_or_no',
                )
            );
        }
        return true;
    }

    /**
     * Adds some common tag filters to the $filteroptions array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_core_tag_filters($component, $itemtype, &$filteroptions) {
        $join = $this->core_tag_join[$component][$itemtype];
        $field = $this->core_tag_field[$component][$itemtype];

        if ($component === 'core' && ($itemtype === 'course' || $itemtype === 'cohort')) {
            $prefix = '';
        } else {
            $prefix = $itemtype . '_';
        }

        // Only get the tags in the collection for this item type.
        $tags = \core_tag\report_builder_tag_loader::get_tags($component, $itemtype);
        $tag_objects = \core_tag_tag::get_bulk(array_keys($tags));

        $tagoptions = array();
        foreach ($tag_objects as $tag) {
            $tagid = $tag->id;
            $name = $tag->get_display_name();

            // Create a yes/no filter for every official tag
            $filteroptions[] = new \rb_filter_option(
                $prefix . 'tags',
                "{$itemtype}_tag_{$tagid}",
                get_string('taggedx', 'totara_reportbuilder', $name),
                'select',
                array(
                    'selectchoices' => array(1 => get_string('yes'), 0 => get_string('no')),
                    'simplemode' => true,
                )
            );

            $tagoptions[$tagid] = $name;
        }

        // Build filter list from tag list.
        $filteroptions[] = new \rb_filter_option(
            $prefix . 'tags',
            'tagid',
            get_string('tags', 'totara_reportbuilder'),
            'correlated_subquery_select',
            array(
                'simplemode' => true,
                'selectchoices' => $tagoptions,
                'searchfield' => "ti.tagid",
                'subquery' => "EXISTS(SELECT 'x'
                                        FROM {tag_instance} ti
                                       WHERE ti.itemtype = '{$itemtype}' AND ti.component = '{$component}'
                                             AND ti.itemid = (%1\$s) AND (%2\$s) )",
            ),
            "{$join}.{$field}",
            $join
        );
        return true;
    }
}
