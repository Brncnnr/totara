<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_phpunit
 */

namespace core_phpunit;

use totara_webapi\endpoint_type\factory as endpoint_type_factory;
use totara_webapi\graphql;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/cache/lib.php");
require_once(__DIR__ . '/cache.php');

/**
 * Fast PHPUnit cache factory.
 */
class cache_factory extends \cache_factory {
    protected $alldefinitions;
    protected $primedcaches;

    public function __construct() {
        global $CFG;

        parent::__construct();

        // Note that $CFG->altcacheconfigpath is NOT supported in phpunit!

        \cache_config_writer::create_default_configuration(true);
        $configuration = array();
        require("$CFG->dataroot/muc/config.php");
        foreach ($configuration['definitions'] as $id => $def) {
            $this->alldefinitions[$id] = \cache_definition::load($id, $def);
        }

        $this->definitions = $this->alldefinitions;
        $this->state = self::STATE_READY;
    }

    /**
     * Common public method to create a cache instance given a definition.
     *
     * This is used by the static make methods.
     *
     * @param \cache_definition $definition
     * @return \cache_loader
     */
    public function create_cache(\cache_definition $definition) {
        $loader = null;
        if ($definition->has_data_source()) {
            $loader = $definition->get_data_source();
        }
        return new cache($definition, $loader);
    }

    /**
     * Called at the end of \cache_helper::purge_all(),
     */
    public function purged_all_stores() {
        foreach ($this->cachesfromdefinitions as $cache) {
            /** @var cache $cache */
            $cache->purge();
        }
        foreach ($this->cachesfromparams as $cache) {
            /** @var cache $cache */
            $cache->purge();
        }
    }

    /**
     * Preload data into caches and store it for later resets.
     */
    public function prime_caches() {
        global $DB;

        // Database caches are the most expensive!
        $tables = $DB->get_tables(true);
        foreach ($tables as $table) {
            $DB->get_columns($table, true);
        }

        // Small perf only, but still one query.
        get_all_capabilities();

        // Cache all configs!
        $plugins = $DB->get_records_sql("SELECT DISTINCT plugin, plugin FROM {config_plugins}");
        foreach ($plugins as $plugin) {
            get_config($plugin->plugin);
        }

        // Prime the hook watchers:
        \totara_core\hook\manager::phpunit_get_watchers();

        // Prime the event observers.
        \core\event\manager::get_all_observers();

        // Cache the GraphQL schemas - this will be hit numerous times and can't be changed during a run.
        if (!defined('PHPUNIT_NO_CACHE_GRAPHQL_SCHEMA') || PHPUNIT_NO_CACHE_GRAPHQL_SCHEMA === false) {
            $types = endpoint_type_factory::get_all_types();
            foreach ($types as $type_class_name) {
                graphql::get_schema(endpoint_type_factory::get_instance_by_class_name($type_class_name));
            }
        }

        // And finally store the cache as serialised data to unreference the objects!
        $this->primedcaches = array();
        foreach ($this->cachesfromdefinitions as $id => $cache) {
            $cache->phpunitmodified = false;
            $this->primedcaches[$id] = $cache->phpunitcache;
        }
    }

    public function phpunit_reset() {
        $this->cachesfromparams = array();
        $this->stores = array();

        $this->definitions = $this->alldefinitions;

        if (!$this->primedcaches) {
            $this->cachesfromdefinitions = array();
        } else {
            foreach ($this->cachesfromdefinitions as $id => $cache) {
                /** @var cache $cache */
                if ($cache->phpunitmodified) {
                    if (isset($this->primedcaches[$id])) {
                        $cache->phpunitcache = $this->primedcaches[$id];
                        $cache->phpunitmodified = false;
                    } else {
                        $cache->phpunitcache = array();
                        $cache->phpunitmodified = false;
                    }
                }
            }
        }

        $this->state = self::STATE_READY;
        self::$instance = $this;
    }
}