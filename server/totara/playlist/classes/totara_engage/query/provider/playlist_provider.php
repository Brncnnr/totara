<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */

namespace totara_playlist\totara_engage\query\provider;

use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\raw_field;
use core\orm\query\sql\where;
use core\orm\query\subquery;
use core\orm\query\table;
use core_tag\entity\tag_instance;
use core_user\totara_engage\share\recipient\user;
use totara_engage\access\access;
use totara_engage\entity\engage_bookmark;
use totara_engage\entity\engage_resource;
use totara_engage\entity\rating;
use totara_engage\entity\share;
use totara_engage\entity\share_recipient;
use totara_engage\local\helper as local_helper;
use totara_engage\query\option\sort;
use totara_engage\query\provider\container;
use totara_engage\query\provider\resource_builder;
use totara_engage\query\query;
use totara_engage\query\provider\queryable;
use totara_engage\query\provider\helper as query_provider_helper;
use totara_playlist\entity\playlist as playlist_entity;
use totara_playlist\entity\playlist_resource;
use totara_playlist\totara_engage\query\option\section;
use totara_playlist\playlist;

/**
 * A callback class.
 */
final class playlist_provider implements queryable, container {

    /**
     * @var string
     */
    private $section;

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
        if ($query->is_search() || $query->is_shared() || $query->is_adder() || $query->is_library_from_workspace() || $query->is_other_users_resources()) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_builder(query $query): ?builder {
        // Playlist cards are included in your library.
        if (!$query->include_entire_library()) {
            // Playlist cards are only included in search and shared queries and in the adder.
            // In container workspace as well.
            $result = $query->is_search() ||
                    $query->is_shared() ||
                    $query->is_adder() ||
                    $query->is_library_from_workspace() ||
                    $query->is_other_users_resources();
            if (!$result) {
                return null;
            }
        }

        // If this plugin is not included in section then leave.
        $section = $query->get_section();
        if ($section) {
            if (!section::is_valid($section)) {
                return null;
            }
            $this->section = $section;
        }

        // Fetch records for your entire library.
        $this->entire_library = $query->include_entire_library();

        return $this->create_builders($query);
    }

    /**
     * @param query $query
     * @return builder|null
     */
    public function create_builders(query $query): ?builder {
        $builder = $this->create_playlist_builder($query);
        $saved_builders = $this->get_linked_builders($query);

        // Add all builders into one.
        if ($saved_builders) {
            foreach ($saved_builders as $saved_builder) {
                if (empty($saved_builder)) {
                    continue;
                }

                if (empty($builder)) {
                    $builder = $saved_builder;
                } else {
                    $builder->union_all($saved_builder);
                }
            }
        }

        return $builder;
    }

    /**
     * @param query $query
     * @return builder|null
     */
    private function create_playlist_builder(query $query): ?builder {
        global $DB;

        // Leave when filtered by type and type is not playlist.
        $type = $query->get_type();
        if (!empty($type) && $type !== 'totara_playlist') {
            return null;
        }

        $userid = $query->get_userid();
        $unique = builder::concat("p.id", "'-totara_playlist'");
        $builder = builder::table(playlist_entity::TABLE, 'p');

        // This is for supporting old version of database
        $playlist_component_value = $DB->sql_cast_2char("'totara_playlist'");
        $builder->select(
            [
                new raw_field("{$unique} AS uniqueid"),
                "p.id AS instanceid",
                "p.name AS name",
                "p.summary AS summary",
                "p.userid AS userid",
                "p.access AS access",
                "p.timecreated AS timecreated",
                "p.timemodified AS timemodified",
                new raw_field("NULL AS extra"),
                new raw_field("{$playlist_component_value} AS component")
            ]
        );

        // Add likes.
        $builder = $this->add_popularity($builder, $query);

        // Apply visibility.
        $access = $query->get_access();
        if (null !== $access) {
            $builder->where('p.access', $access);
        }

        // Filter by topic.
        $builder = $this->add_topic($builder, $query->get_topic());

        // Don't include if sub query.
        if (!$this->sub_query) {
            // All site.
            if ($query->is_allsite()) {
                $this->sub_conditions[] = function(builder $builder) {
                    $builder->or_where('p.access', access::PUBLIC);
                };
            }

            // Include restricted.
            if ($query->include_restricted()) {
                $this->sub_conditions[] = function(builder $builder) {
                    $builder->or_where('p.access', access::RESTRICTED);
                };
            }

            // Owned.
            if ($query->is_owned() || section::is_yourplaylists($this->section)) {
                $builder = $this->add_owned($builder, $userid);
            } elseif ($this->entire_library) {
                $this->sub_conditions[] = function(builder $builder) use($userid) {
                    $builder->or_where('p.userid', $userid);
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
                $builder->where('p.userid', $userid);
                // Playlists shared with the recipient
                $builder = $this->add_shared($builder, $query->get_share_recipient_id());

                // Add public
                $builder->or_where(function (builder $builder) use ($userid) {
                    $builder->where('p.access', access::PUBLIC);
                    $builder->where('p.userid', $userid);
                });
            }

            // Saved.
            if ($query->is_saved() || section::is_savedplaylists($this->section) || $this->entire_library) {
                $builder = $this->add_saved($builder, $userid, !$this->entire_library);
            }
        }

        // Search.
        if ($query->is_search()) {
            $builder = $this->search_library($builder, $query->get_search());
        } else if ($query->is_adder() && !empty($query->get_search())) {
            // For adder just add simple search for names/summary.
            $builder = $this->add_search($builder, $query->get_search());
            $this->add_sub_conditions($builder);
        } else {
            // Add conditions so that we fetch records that exists in either
            // shared, saved, or owned.
            $this->add_sub_conditions($builder);
        }

        return $builder;
    }

    /**
     * @param builder $builder
     * @param int|null $topic
     * @return builder
     */
    private function add_topic(builder $builder, ?int $topic): builder {
        if (!empty($topic)) {
            $builder->join([tag_instance::TABLE, 'ti1'], function(builder $joining) use($topic) {
                $joining->where('ti1.component', playlist::get_resource_type())
                    ->where('ti1.itemtype', 'playlist')
                    ->where_raw('ti1.itemid = p.id')
                    ->where('ti1.tagid', $topic);
            });
        }

        return $builder;
    }

    /**
     * @param builder $builder
     * @param query $query
     * @return builder
     */
    private function add_popularity(builder $builder, query $query): builder {
        // If we are sorting according to popularity then we need to add total likes.
        if ($query->get_sort() === sort::POPULAR) {
            // Get rating.
            $ratings = new subquery(
                builder::table(rating::TABLE, 'r1')
                    ->select([
                        'r1.instanceid',
                        new raw_field('SUM(CASE WHEN rating > 3 THEN 1 ELSE 0 END) as total')
                    ])
                    ->where_raw("r1.component = 'totara_playlist'")
                    ->where_raw("r1.area = 'playlist'")
                    ->group_by([
                        'r1.instanceid'
                    ])
            );
            $ratings->as('ratings');
            $ratings_table = new table($ratings);
            $builder->left_join($ratings_table, 'ratings.instanceid', '=', 'p.id');

            // Get total resharers.
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
                $joining->where_raw('resharers.itemid = p.id')
                    ->where_raw("resharers.component = 'totara_playlist'");
            });

            // Add popularity column.
            $builder->add_select_raw(
                'COALESCE(ratings.total, 0) + COALESCE(resharers.total, 0) as popularity'
            );
        }

        return $builder;
    }

    /**
     * Search is special as we need to search across our library which only includes:
     *   - your playlists (owned)
     *   - shared with you (shared)
     *   - saved playlists (saved)
     *
     * @param builder $builder
     * @param string $search
     * @return builder
     */
    private function search_library(builder $builder, string $search): builder {
        $builder = $this->add_topic_search($builder, $search, false);

        // Add conditions so that we fetch records that exists in either
        // shared, saved, or owned and matches a resource or topic linked
        // to a resource.
        $builder->where(function (builder $builder) use($search) {
            // Add where groupings for playlist search.
            $builder->where(function (builder $builder) use($search) {
                $builder = $this->add_search($builder, $search);
                $this->add_sub_conditions($builder);
            });

            // Add where groupings for topic search.
            $builder->or_where(function (builder $builder) use($search) {
                $builder->where_not_null('t2.id');
                $this->add_sub_conditions($builder);
            });
        });

        return $builder;
    }

    /**
     * @param builder $builder
     */
    private function add_sub_conditions(builder $builder): void {
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
     * We also have to include the resources of the playlists that were saved.
     *
     * @param query $query
     * @return builder[]
     */
    private function get_linked_builders(query $query): ?array {
        // Only get linked resources when we are searching.
        if (!$query->is_search()) {
            return null;
        }

        // Only include saved resources when searching across all sections or when
        // saved playlists section filter is selected.
        if (!empty($this->section) && !section::is_savedplaylists($this->section)) {
            return null;
        }

        // Include playlist resources in the search.
        $providers = query_provider_helper::get_resource_providers();

        $builders = [];
        $userid = $query->get_userid();

        /** @var queryable $provider */
        foreach ($providers as $provider) {
            $resource_builder = $provider->get_linked_builder($query);
            if ($resource_builder) {
                $resource = $resource_builder->get_builder();
                $resource->join(
                    [playlist_resource::TABLE, 'pr'],
                    'pr.resourceid',
                    '=',
                    $resource_builder->get_key()
                );
                $resource->join(
                    [playlist_entity::TABLE, 'p'],
                    'p.id',
                    '=',
                    'pr.playlistid'
                );
                $resource->join([engage_bookmark::TABLE, 'p_eb'], function (builder $joining) use($userid) {
                    $joining->where_raw('p_eb.itemid = p.id')
                        ->where('p_eb.component', playlist::get_resource_type())
                        ->where('p_eb.userid', $userid);
                });

                $builders[] = $resource;
            }
        }

        return $builders;
    }

    /**
     * @param builder $builder
     * @param int $userid
     * @return builder
     */
    private function add_owned(builder $builder, int $userid): builder {
        $builder->where_raw("p.userid = {$userid}");
        return $builder;
    }

    /**
     * @param builder $builder
     * @param int $userid
     * @param bool $must_exist
     * @return builder
     */
    private function add_shared(builder $builder, int $userid, bool $must_exist = true): builder {
        // Get all playlists that were shared with me.
        $builder->left_join([share::TABLE, 'es'], function(builder $joining) {
            $joining->where_raw('es.itemid = p.id')
                ->where('es.component', playlist::get_resource_type());
        });

        $builder->left_join([share_recipient::TABLE, 'esr'], function(builder $joining) use($userid) {
            $joining->where_raw('esr.shareid = es.id')
                ->where('esr.instanceid', $userid)
                ->where('esr.area', user::AREA)
                ->where('esr.component', local_helper::get_component_name(user::class))
                ->where('esr.visibility', 1);
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
     * Saved playlists are all the playlists that I bookmarked.
     *
     * @param builder $builder
     * @param int $userid
     * @param bool $must_exist
     * @return builder
     */
    private function add_saved(builder $builder, int $userid, bool $must_exist = true): builder {
        $builder->left_join([engage_bookmark::TABLE, 'eb'], function(builder $joining) use($userid) {
            $joining->where_raw('eb.itemid = p.id')
                ->where('eb.component', playlist::get_resource_type())
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
     * @param builder $builder
     * @param string $search
     * @return builder
     */
    private function add_search(builder $builder, string $search): builder {
        global $DB;

        $builder->where(function (builder $builder) use($DB, $search) {
            $uniqueparam1 = $DB->get_unique_param();
            $uniqueparam2 = $DB->get_unique_param();
            $builder->where_raw(
                    $DB->sql_like('p.name', ":{$uniqueparam1}", false),
                    [$uniqueparam1 => "%{$search}%"]
                )
                ->or_where_raw(
                    $DB->sql_like('p.summary', ":{$uniqueparam2}", false),
                    [$uniqueparam2 => "%{$search}%"]
                );
        });

        return $builder;
    }

    /**
     * @param builder $builder
     * @param string $search
     * @param bool $must_exist
     * @return builder
     */
    private function add_topic_search(builder $builder, string $search, bool $must_exist = true): builder {
        global $DB;

        $builder->left_join([tag_instance::TABLE, 'ti2'], function(builder $joining) {
            $joining->where('ti2.component', playlist::get_resource_type())
                ->where('ti2.itemtype', 'playlist')
                ->where_raw('ti2.itemid = p.id');

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
     * @inheritDoc
     */
    public function get_section_options(query $query): array {
        // Playlists cannot be added to playlists so no use in showing
        // playlist section filter options.
        if ($query->get_component() !== 'totara_playlist'
            || $query->get_area() !== 'adder') {
            return section::get_options();
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function get_container_builder(): builder {
        $builder = builder::table(playlist_entity::TABLE, 'p')
            ->select([
                field::raw('p.id AS id'),
                field::raw("'totara_playlist' AS type"),
                field::raw('p.userid AS owner_id'),
                field::raw('pr.resourceid AS resource_id'),
                'p.name'
            ])
            ->join([playlist_resource::TABLE, 'pr'], 'pr.playlistid', '=', 'p.id');

        return $builder;

    }

    /**
     * @inheritDoc
     */
    public function get_container_details(int $resourceid, $component): array {
        if ($component === 'totara_playlist') {
            return [];
        }

        $url = new \moodle_url('/totara/playlist/index.php', ['id' => '0']);
        $url = $url->out();
        $url_concat = builder::concat(':url', 'p.id');

        $builder = builder::table(playlist_entity::TABLE, 'p')
            ->select(
                [
                    'p.id',
                    'p.name',
                    new raw_field("'totara_playlist' as type"),
                    raw_field::raw("{$url_concat} as url", ['url' => $url]),
                ]
            )
            ->join([playlist_resource::TABLE, 'pr'], 'pr.playlistid', '=', 'p.id')
            ->join([engage_resource::TABLE, 'er'], 'er.id', '=', 'pr.resourceid')
            ->where('er.id', $resourceid)
            ->where('er.resourcetype', $component);

        return $builder->fetch();
    }

    /**
     * @inheritDoc
     */
    public function get_linked_builder(query $query, bool $sub_query = true): ?resource_builder {
        $this->sub_query = $sub_query;
        $builder = $this->get_builder($query);
        if ($builder) {
            return new resource_builder($builder, 'p.id', 'p');
        }
        return null;
    }
}