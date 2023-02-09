<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

namespace mod_facetoface\dashboard;

use context;
use moodle_url;
use mod_facetoface\seminar;
use mod_facetoface\query\event\query;
use mod_facetoface\dashboard\filters\filter;
use mod_facetoface\output\builder\seminarevent_filterbar_builder;
use mod_facetoface\query\event\filter\event_times_filter;
use mod_facetoface\query\event\sortorder\future_sortorder;
use mod_facetoface\query\event\sortorder\past_sortorder;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide filter management on the event dashboard.
 */
class filter_list {
    const PARAM_FILTER_F2FID = 'f';

    /** @var filter[] */
    protected $filters = [];

    /** @var callable */
    protected $param_loader;

    /**
     * Constructor.
     *
     * @param callable|null $param_loader   An external parameter loader callback function as:
     *                                      function (string $parname, mixed $default, string $type)
     *                                      In practice, this argument will be 'optional_param'
     */
    public function __construct(callable $param_loader = null) {
        if ($param_loader === null) {
            $param_loader = function (string $parname, $default, string $type) {
                return $default;
            };
        }
        $this->param_loader = $param_loader;
    }

    /**
     * Create a new filter_list instance by taking parameters from GET or POST.
     *
     * @return filter_list
     */
    public static function from_query_params(): filter_list {
        return (new static('optional_param'))->add_default_filters();
    }

    /**
     * Add a filter. If the same filter already exists, the existing filter will be overwritten.
     *
     * @param filter $filter
     * @return filter_list
     */
    public function add_filter(filter $filter): filter_list {
        $filter->load_param($this->param_loader);
        $this->filters[get_class($filter)] = $filter;
        return $this;
    }

    /**
     * Add filters found in the mod_facetoface\dashboard\filters namespace.
     *
     * @return filter_list
     */
    public function add_default_filters(): filter_list {
        $classes = \core_component::get_namespace_classes(
            'dashboard\filters',
            filter::class,
            'mod_facetoface'
        );
        foreach ($classes as $class) {
            $inst = new $class();
            /** @var filter $inst */
            $this->add_filter($inst);
        }
        return $this;
    }

    /**
     * Return true if all parameter values are default.
     *
     * @return boolean
     */
    public function are_default(): bool {
        $is_default = true;
        foreach ($this->filters as $unused => $filter) {
            $is_default &= $filter->get_default_value() == $filter->get_param_value();
        }
        return $is_default;
    }

    /**
     * Get the filter value of a given filter class.
     *
     * @param string $class     The name of a filter class
     * @return string|integer   The current filter value
     */
    public function get_filter_value(string $class) {
        if (!array_key_exists($class, $this->filters)) {
            throw new \coding_exception("The filter class '$class' is not registered.");
        }
        return $this->filters[$class]->get_param_value();
    }

    /**
     * Set the filter value of a given filter class.
     *
     * @param string            $class      The name of a filter class
     * @param string|integer    $value      A new filter value
     * @return filter_list
     */
    public function set_filter_value(string $class, $value): filter_list {
        if (!array_key_exists($class, $this->filters)) {
            throw new \coding_exception("The filter class '$class' is not registered.");
        }
        $this->filters[$class]->set_param_value($value);
        return $this;
    }

    /**
     * See if the filter is available.
     *
     * @param filter            $filter     A filter instance
     * @param seminar           $seminar    A seminar instance
     * @param context           $context    A context
     * @param integer|null      $userid     A user ID or null to use the current user
     * @return boolean
     */
    private static function is_filter_available(filter $filter, seminar $seminar, context $context, int $userid = null): bool {
        if ($filter->is_visible($seminar, $context, $userid)) {
            return true;
        }
        return $filter->get_param_value() !== $filter->get_default_value();
    }

    /**
     * Walk the filter list.
     *
     * @param callable $callback
     * @return void
     */
    public function walk(callable $callback): void {
        foreach ($this->filters as $class => $filter) {
            $name = $filter->get_param_name();
            $type = $filter->get_filterbar_option(filter::OPTION_PARAMTYPE) ?: PARAM_INT;
            $callback($name, $type, $filter);
        }
    }

    /**
     * Create a new seminarevent_filterbar_builder instance and supply filter values.
     *
     * @param seminar           $seminar    A seminar instance
     * @param string            $id         A unique ID used by seminarevent_filterbar
     * @param context           $context    A context
     * @param integer|null      $userid     A user ID or null to use the current user
     * @return seminarevent_filterbar_builder
     */
    public function to_filterbar_builder(seminar $seminar, string $id, context $context, int $userid = null): seminarevent_filterbar_builder {
        $filterbar = \mod_facetoface\output\seminarevent_filterbar::builder($id);
        $filterbar->add_param(self::PARAM_FILTER_F2FID, $seminar->get_id());
        // Sort filters by sort order defined by filter classes
        $filters = $this->filters;
        usort($filters, function ($x, $y) {
            /** @var filter $x */
            /** @var filter $y */
            $ox = $x->get_filterbar_option(filter::OPTION_ORDER) ?? 0;
            $oy = $y->get_filterbar_option(filter::OPTION_ORDER) ?? 0;
            return $ox <=> $oy;
        });
        foreach ($filters as $unused => $filter) {
            $name = $filter->get_param_name();
            $value = $filter->get_param_value();
            if ($filter->is_visible($seminar, $context, $userid)) {
                $options = $filter->get_options($seminar);
                // Load a default value.
                if (!array_key_exists($filter->get_param_value(), $options)) {
                    $value = $filter->get_default_value();
                }
                $filterbar->add_filter(
                    $name, $options, $filter->get_class(), $filter->get_label(), count($options) == 0,
                    $filter->get_filterbar_option(filter::OPTION_TOOLTIPS) ?? false
                );
            }
            if (self::is_filter_available($filter, $seminar, $context, $userid)) {
                $filterbar->add_param($name, $value);
            }
        }
        return $filterbar;
    }

    /**
     * Build an SQL query.
     *
     * @param seminar           $seminar    A seminar instance
     * @param context           $context    NOT USED
     * @param integer|null      $userid     NOT USED
     * @return query
     */
    public function to_query(seminar $seminar, context $context, int $userid = null): query {
        $query = new query($seminar);
        foreach ($this->filters as $unused => $filter) {
            $filter->modify_query($query);
        }
        return $query;
    }

    /**
     * Build an SQL query with option.
     *
     * @param seminar $seminar
     * @param context $context
     * @param integer|null $userid
     * @param render_session_option|null $option
     * @return query
     */
    public function to_query_with_option(seminar $seminar, context $context, int $userid = null, render_session_option $option = null): query {
        $query = $this->to_query($seminar, $context, $userid);
        if ($option === null) {
            return $query;
        }
        $query->with_filter(new event_times_filter($option->get_eventtimes()));
        if ($option->get_eventascendingorder()) {
            $query->with_sortorder(new past_sortorder());
        } else {
            $query->with_sortorder(new future_sortorder());
        }
        return $query;
    }

    /**
     * Build a URL with query parameters.
     *
     * @param seminar $seminar      A seminar instance
     * @param string  $baseurl      A page url
     * @return moodle_url
     */
    public function to_url(seminar $seminar, string $baseurl = '/mod/facetoface/view.php'): moodle_url {
        $params = [
            'id' => $seminar->get_coursemodule()->id,
            self::PARAM_FILTER_F2FID => $seminar->get_id()
        ];
        foreach ($this->filters as $unused => $filter) {
            $name = $filter->get_param_name();
            $value = $filter->get_param_value();
            if ($value !== $filter->get_default_value()) {
                $params[$name] = $value;
            }
        }
        return new moodle_url($baseurl, $params);
    }

    /**
     * Convert to an object for debugging purposes.
     *
     * @return \stdClass
     */
    public function to_object(): \stdClass {
        return (object)get_object_vars($this);
    }
}
