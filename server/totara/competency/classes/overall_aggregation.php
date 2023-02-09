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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use core\orm\collection;
use totara_competency\entity\pathway_achievement;
use totara_hierarchy\entity\scale_value;

abstract class overall_aggregation {

    /** @var string $agg_type Type of aggregatioon. Obtained from class */
    private $agg_type;

    /** @var pathway[] Pathways to aggregate over*/
    protected $pathways = [];

    /** @var array Achievement information per user */
    private $user_achievement = [];

    final public function __construct() {
        $reflect = new \ReflectionClass($this);
        $this->agg_type = $reflect->getShortName();
    }

    public static function aggregation_type(): string {
        $reflect = new \ReflectionClass(static::class);
        return $reflect->getShortName();
    }

    /**
     * Returns the type of the aggregation, i.e. highest or first
     *
     * @return string
     */
    public function get_agg_type(): string {
        return $this->agg_type;
    }

    /**
     * Set the pathways to aggregate over
     *
     * @param array $pathways
     * @return overall_aggregation
     */
    public function set_pathways(array $pathways): overall_aggregation {
        $this->pathways = $pathways;
        return $this;
    }

    /**
     * @return pathway[]
     */
    protected function get_pathways(): array {
        return $this->pathways;
    }

    /**
     * @param int $user_id Id of user to aggregate
     * @return array [int, array] Keys: scale_value, achieved_via
     */
    public function aggregate_for_user(int $user_id): array {
        if (!isset($this->user_achievement[$user_id])) {
            $this->do_aggregation($user_id);
        }

        return empty($this->user_achievement[$user_id])
            ? ['scale_value' => null, 'achieved_via' => []]
            : $this->user_achievement[$user_id];
    }

    /**
     * Aggregate a users value for a competency
     *
     * @param int $user_id
     * @return void
     */
    abstract protected function do_aggregation(int $user_id): void;

    /**
     * Get all current achievements for the pathways given
     *
     * @param array $pathways
     * @param int $user_id
     * @return collection|pathway_achievement[]
     */
    protected function get_current_pathway_achievements_for_user(array $pathways, int $user_id) {
        $pathway_ids = array_map(function (pathway $pathway) {
            return $pathway->get_id();
        }, $pathways);

        return pathway_achievement::repository()
            ->where('pathway_id', $pathway_ids)
            ->where('user_id', $user_id)
            ->where('status', pathway_achievement::STATUS_CURRENT)
            ->order_by('pathway_id', 'asc')
            ->order_by('last_aggregated','desc')
            ->with('scale_value')
            ->get();
    }

    /**
     * Filter the collection for achievements of the given pathway and
     * if there's none found, create a new one
     *
     * @param collection $current_achievements
     * @param pathway $pathway
     * @param int $user_id
     * @return pathway_achievement
     */
    protected function get_or_create_current_pathway_achievement(collection $current_achievements, pathway $pathway, int $user_id): pathway_achievement {
        $achievements = $current_achievements->filter('pathway_id', $pathway->get_id());

        switch ($achievements->count()) {
            case 0:
                $achievement = new pathway_achievement();
                $achievement->pathway_id = $pathway->get_id();
                $achievement->user_id = $user_id;
                $achievement->scale_value_id = null;
                $achievement->status = pathway_achievement::STATUS_CURRENT;
                $achievement->date_achieved = time();
                // It has not been aggregated yet. If we set this to now, we might miss anything that would have
                // prompted aggregation in the recent past, but maybe cron hasn't run since then.
                $achievement->last_aggregated = null;
                break;
            case 1:
                $achievement = $achievements->first();
                break;
            default:
                // There's more than one which is not right. But let's not make the whole system stop working because of this.
                debugging('User has multiple current achievements for pathway with id ' . $pathway->get_id());
                // We ordered by last_aggregated and that would be our best guess at the correct one.
                $achievement = $achievements->first();
                break;
        }

        return $achievement;
    }

    /**
     * @param $user_id
     * @param array|pathway_achievement[] $achieved_via
     * @param scale_value $scale_value
     */
    protected function set_user_achievement($user_id, array $achieved_via, scale_value $scale_value = null) {
        // For now taking the last value set
        $this->user_achievement[$user_id] = ['scale_value' => $scale_value, 'achieved_via' => $achieved_via];
    }

    /**
     * Return id of the achieved scale value
     *
     * @return int|null
     */
    public function get_achieved_value_id($user_id): ?int {
        if (!isset($this->user_achievement[$user_id])) {
            return null;
        }

        $value = $this->user_achievement[$user_id]['scale_value'];
        return $value ? $value->id : null;
    }

    /**
     * Return the list of pathway achievement through which the user achieved this
     * @return pathway_achievement[]
     */
    public function get_achieved_via($user_id): array {
        if (!isset($this->user_achievement[$user_id])) {
            return [];
        }

        return $this->user_achievement[$user_id]['achieved_via'];
    }

    /**
     * Human readable title for this aggregation
     *
     * @return string
     */
    public function get_title(): string {
        $namespace = (new \ReflectionClass($this))->getNamespaceName();
        return get_string('title', $namespace);
    }

    /**
     * Get the human readable description of the aggregation type for display purposes
     *
     * @return string
     */
    public function get_description(): string {
        $namespace = (new \ReflectionClass($this))->getNamespaceName();
        return get_string('description', $namespace);
    }

    /**
     * Return the name of the javascript function handling pathway aggregation editing
     *
     * @return string|null Javascript function name. In v1, this must be the name of an existing
     *                     function in achievement_paths.js. Null or an empty string indicates
     *                     that no user interaction is required / allowed when changing to this
     *                     aggregation type
     */
    public function get_aggregation_js_function(): ?string {
        return null;
    }

}
