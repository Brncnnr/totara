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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_survey
 */

namespace totara_engage\query\provider;

use core\orm\query\builder;
use core\orm\query\raw_field;
use core\orm\query\subquery;
use core\orm\query\table;
use core_tag\entity\tag_instance;
use core_user\totara_engage\share\recipient\user;
use totara_engage\access\access;
use totara_engage\entity\engage_bookmark;
use totara_engage\entity\engage_resource;
use totara_engage\entity\share;
use totara_engage\entity\share_recipient;
use totara_engage\query\option\section;
use totara_engage\query\option\sort;
use totara_engage\query\query;
use totara_reaction\entity\reaction;

abstract class resource_provider implements queryable {

    /**
     * @var string
     */
    protected $section;

    /** @var bool */
    protected $sub_query = false;

    /** @var bool */
    protected $entire_library = false;

    /** @var array */
    protected $sub_conditions = [];

    /**
     * @inheritDoc
     */
    public static function provide_query_type(query $query): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_section_options(query $query): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function get_builder(query $query): ?builder {
        // If this plugin is not included in section then leave.
        if (!$this->sub_query && !$this->in_section($query->get_section())) {
            return null;
        }

        // Leave when this is not the resource type we are looking for.
        $type = $query->get_type();
        if (!empty($type) && $type !== $this->get_resource_type()) {
            return null;
        }

        // Fetch records for your entire library.
        $this->entire_library = $query->include_entire_library();

        return $this->create_builder($query);
    }

    /**
     * Confirm if this plugin is included in section filter.
     *
     * @param int|null $section
     * @return bool
     */
    protected function in_section(?int $section) {
        if ($section) {
            if (!section::is_valid($section)) {
                return false;
            }
            $this->section = $section;
        }

        return true;
    }

    /**
     * @return builder
     */
    protected function get_base_builder(): builder {
        $unique = builder::concat("er.id", "'-'", "er.resourcetype");
        $builder = builder::table(engage_resource::TABLE, 'er');
        $builder->select(
            [
                new raw_field("DISTINCT {$unique} AS uniqueid"),
                "er.id AS instanceid",
                "er.name AS name",
                new raw_field("NULL AS summary"),
                "er.userid AS userid",
                "er.access AS access",
                "er.timecreated AS timecreated",
                "er.timemodified AS timemodified",
                "er.extra AS extra",
                "er.resourcetype AS component"
            ]
        );

        // Excluding those resources from deleted and suspended users.
        $unique_alias = uniqid("user_table_");
        $builder->join(
            ['user', $unique_alias],
            function (builder $join) : void {
                $join->where_field("er.userid", "id");
                $join->where("deleted", 0);

                // And check for confirmed user.
                $join->where("confirmed", 1);
            }
        );

        return $builder;
    }

    /**
     * @param query $query
     * @return builder|null
     */
    public function create_builder(query $query): ?builder {
        $userid = $query->get_userid();
        $builder = $this->get_base_builder();
        $builder = $this->add_popularity($builder, $query);

        $access = $query->get_access();
        if (null !== $access) {
            $builder->where('er.access', $access);
        }

        // Topic.
        $builder = $this->add_topic($builder, $query->get_topic());

        // Don't include if sub query.
        if (!$this->sub_query) {
            // All site.
            if ($query->is_allsite()) {
                $this->sub_conditions[] = function(builder $builder) {
                    $builder->or_where('er.access', access::PUBLIC);
                };
            }

            // Include restricted.
            if ($query->include_restricted()) {
                $this->sub_conditions[] = function(builder $builder) {
                    $builder->or_where('er.access', access::RESTRICTED);
                };
            }

            // Owned.
            if ($query->is_owned() || section::is_yourresources($this->section)) {
                $builder->where_raw("er.userid = {$userid}");
            } elseif ($this->entire_library) {
                $this->sub_conditions[] = function(builder $builder) use($userid) {
                    $builder->or_where('er.userid', $userid);
                };
            }

            // Shared with you.
            if ($query->is_shared() || section::is_sharedwithyou($this->section) || $this->entire_library) {
                // Add extra select as we will be sorting according to this.
                if ($query->is_shared()) {
                    $builder->add_select('esr.timecreated AS dateshared');
                }
                $builder = $this->add_shared($builder, $userid, !$this->entire_library);
            }

            // Shared via other user's library
            if ($query->is_other_users_resources()) {
                // Resources owned by userid
                $builder->where('er.userid', $userid);
                // Resources shared with the recipient
                $builder = $this->add_shared($builder, $query->get_share_recipient_id());

                // Add public
                $builder->or_where(function (builder $builder) use ($userid) {
                    $builder->where('er.access', access::PUBLIC);
                    $builder->where('er.userid', $userid);
                });
            }

            // Saved.
            if ($query->is_saved() || section::is_savedresources($this->section) || $this->entire_library) {
                $builder = $this->add_saved($builder, $userid, !$this->entire_library);
            }
        }

        // Search.
        if ($query->is_search()) {
            $builder = $this->search_library($builder, $query);
        } else if ($query->is_adder() && !empty($query->get_search())) {
            // For adder just add simple search for name.
            $builder = $this->add_search($builder, $query->get_search());
            $this->add_sub_conditions($builder);
        } else {
            $this->add_sub_conditions($builder);
        }

        return $builder;
    }

    /**
     * @param builder $builder
     * @param query $query
     * @return builder
     */
    protected function add_popularity(builder $builder, query $query): builder {
        // If we are sorting according to popularity then we need to add total likes.
        if ($query->get_sort() === sort::POPULAR) {
            $builder = $this->join_likes($builder);
            $builder = $this->join_resharers($builder);
            $builder = $this->join_containers($builder);

            // Add popularity column.
            $builder->add_select_raw('
                COALESCE(likes.total, 0) 
                + COALESCE(resharers.total, 0) 
                + COALESCE(containers.total, 0) 
                as popularity
            ');
        }

        return $builder;
    }

    /**
     * @param builder $builder
     * @return builder
     */
    protected function join_likes(builder $builder): builder {
        $likes = new subquery(
            builder::table(reaction::TABLE, 'r1')
                ->select([
                    'r1.instanceid',
                    'r1.component',
                    'COUNT(r1.id) as total'
                ])
                ->where_raw("r1.area = 'media'")
                ->group_by([
                    'r1.instanceid',
                    'r1.component'
                ])
        );
        $likes->as('likes');
        $likes_table = new table($likes);
        $builder->left_join($likes_table, function(builder $joining) {
            $joining->where_raw('likes.instanceid = er.id')
                ->where_raw('likes.component = er.resourcetype');
        });

        return $builder;
    }

    /**
     * @param builder $builder
     * @return builder
     */
    protected function join_resharers(builder $builder): builder {
        $resharers = new subquery(
            builder::table(share::TABLE, 's1')
                ->select([
                    's1.itemid',
                    's1.component',
                    'COUNT(sr1.id) as total'
                ])
                ->join([share_recipient::TABLE, 'sr1'], function(builder $joining) {
                    $joining->where_raw('sr1.shareid = s1.id')
                        ->where_raw('sr1.sharerid != s1.ownerid');
                })
                ->group_by([
                    's1.itemid',
                    's1.component'
                ])
        );
        $resharers->as('resharers');
        $resharers_table = new table($resharers);
        $builder->left_join($resharers_table, function(builder $joining) {
            $joining->where_raw('resharers.itemid = er.id')
                ->where_raw('resharers.component = er.resourcetype');
        });

        return $builder;
    }

    /**
     * @param builder $builder
     * @return builder
     */
    protected function join_containers(builder $builder): builder {
        $providers = helper::get_resource_providers(true);

        /** @var builder $containers_builder */
        $containers_builder = null;

        /** @var container $provider */
        foreach($providers as $provider) {
            $container_builder = $provider->get_container_builder();
            if (empty($containers_builder)) {
                $containers_builder = $container_builder;
            } else {
                $containers_builder->union_all($container_builder);
            }
        }

        $containers = new subquery(
            builder::table($containers_builder, 'sub1')
                ->select([
                    'sub1.resource_id',
                    'sub1.owner_id',
                    'COUNT(sub1.id) as total'
                ])
                ->group_by([
                    'sub1.resource_id',
                    'sub1.owner_id'
                ])
        );

        $containers->as('containers');
        $containers_table = new table($containers);
        $builder->left_join($containers_table, function(builder $joining) {
            $joining->where_raw('containers.resource_id = er.id')
                ->where_raw('containers.owner_id != er.userid');
        });

        return $builder;
    }

    /**
     * Search is special as we need to search across our library which only includes:
     *   - your resources (owned)
     *   - shared with you (shared)
     *   - saved resources (saved)
     *
     * @param builder $builder
     * @param query $query
     * @return builder|null
     */
    protected function search_library(builder $builder, query $query): ?builder {
        $search = $query->get_search();

        $builder = $this->add_topic_search($builder, $search, false);
        $builder = $this->add_search_groups($builder, $search);

        return $builder;
    }

    /**
     * Add conditions so that we fetch records that exists in either shared, saved, or owned
     * and matches a resource or topic linked to a resource.
     * @param builder $builder
     * @param string $search
     * @return builder
     */
    protected function add_search_groups(builder $builder, string $search): builder {
        $builder->where(function (builder $builder) use($search) {

            // Add where groupings for resource search.
            $builder->where(function (builder $builder) use($search) {
                $builder = $this->add_search($builder, $search);
                $this->add_sub_conditions($builder);
            });

            // Add where groupings for topic search.
            $builder->or_where(function (builder $builder) {
                $builder->where_not_null('t2.id');
                $this->add_sub_conditions($builder);
            });

        });

        return $builder;
    }

    /**
     * @param builder $builder
     */
    protected function add_sub_conditions(builder $builder): void {
        // Do not include this if a sub-query.
        if ($this->sub_query) {
            return;
        }

        // Add sub conditions.
        $builder->where(function (builder $builder) {
            foreach ($this->sub_conditions as $sub_condition) {
                if ($sub_condition instanceof \Closure) {
                    $sub_condition($builder);
                }
            }
        });
    }

    /**
     * @param builder $builder
     * @param int|null $topic
     * @return builder
     */
    protected function add_topic(builder $builder, ?int $topic): builder {
        if (!empty($topic)) {
            $builder->join([tag_instance::TABLE, 'ti1'], function(builder $joining) use($topic) {
                $joining->where_raw('ti1.component = er.resourcetype')
                    ->where('ti1.itemtype', 'engage_resource')
                    ->where_raw('ti1.itemid = er.id')
                    ->where('ti1.tagid', $topic);
            });
        }

        return $builder;
    }

    /**
     * @param builder $builder
     * @param string $search
     * @param bool $must_exist
     * @return builder
     */
    protected function add_topic_search(builder $builder, string $search, bool $must_exist = true): builder {
        global $DB;

        $builder->left_join([tag_instance::TABLE, 'ti2'], function(builder $joining) {
            $joining->where_raw('ti2.component = er.resourcetype')
                ->where('ti2.itemtype', 'engage_resource')
                ->where_raw('ti2.itemid = er.instanceid');

        });

        $builder->left_join(['tag', 't2'], function(builder $joining) use ($DB, $search) {
            $uniqueparam = $DB->get_unique_param();
            $joining->where_raw('t2.id = ti2.tagid')
                ->where_raw(
                    $DB->sql_like('t2.name', ":{$uniqueparam}", false),
                    [$uniqueparam => "%{$search}%"]
                );
        });

        if ($must_exist) {
            $builder->where_not_null('ti2.id');
        }

        return $builder;
    }

    /**
     * @param builder $builder
     * @param int $userid
     * @return builder
     */
    protected function add_owned(builder $builder, int $userid): builder {
        $builder->where_raw("er.userid = {$userid}");
        return $builder;
    }

    /**
     * @param builder $builder
     * @param int $userid
     * @param bool $must_exist
     * @return builder
     */
    protected function add_shared(builder $builder, int $userid, bool $must_exist = true): builder {
        // Get all resources that was shared with me.
        $builder->left_join([share::TABLE, 'es'], function(builder $joining) {
            $joining->where_raw('es.itemid = er.id')
                ->where_raw('es.component = er.resourcetype');
        });

        $builder->left_join([share_recipient::TABLE, 'esr'], function(builder $joining) use($userid) {
            $joining->where_raw('esr.shareid = es.id')
                ->where('esr.instanceid', $userid)
                ->where('esr.area', user::AREA)
                ->where('esr.component', 'core_user')
                ->where('esr.visibility', \totara_engage\share\share::VISIBILITY_VISIBLE);
        });

        if ($must_exist) {
            $builder->where_not_null('esr.id');
        } else {
            $this->sub_conditions[] = function(builder $builder) {
                $builder->or_where_not_null('esr.id');
            };
        }

        return $builder;
    }

    /**
     * Saved resources are all the resources that I bookmarked.
     *
     * @param builder $builder
     * @param int $userid
     * @param bool $must_exist
     * @return builder
     */
    protected function add_saved(builder $builder, int $userid, bool $must_exist = true): builder {
        $builder->left_join([engage_bookmark::TABLE, 'eb'], function(builder $joining) use($userid) {
            $joining->where_raw('eb.itemid = er.id')
                ->where_raw('eb.component = er.resourcetype')
                ->where('eb.userid', $userid);
        });

        if ($must_exist) {
            $builder->where_not_null('eb.id');
        } else {
            $this->sub_conditions[] = function(builder $builder) {
                $builder->or_where_not_null('eb.id');
            };
        }

        return $builder;
    }

    /**
     * @inheritDoc
     */
    public function get_linked_builder(query $query, bool $sub_query = true): ?resource_builder {
        $this->sub_query = $sub_query;
        $builder = $this->get_builder($query);
        if ($builder) {
            return new resource_builder($builder, 'er.id', 'er');
        }
        return null;
    }

    /**
     * Search your library.
     *
     * @param builder $builder
     * @param string $search
     * @return builder
     */
    protected function add_search(builder $builder, string $search): builder {
        global $DB;
        $uniqueparam = $DB->get_unique_param();
        $builder->where_raw(
            $DB->sql_like('er.name', ":{$uniqueparam}", false),
            [$uniqueparam => "%{$search}%"]
        );

        return $builder;
    }

    /**
     * @return string
     */
    abstract protected function get_resource_type(): string;

}
