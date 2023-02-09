<?php
/*
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use totara_competency\entity\configuration_change;
use totara_competency\entity\configuration_history;
use totara_competency\entity\scale_aggregation;
use totara_hierarchy\entity\competency;
use context_system;

/**
 * Class containing all relvant configuration information for a specific competency
 */
class achievement_configuration {

    public const DEFAULT_AGGREGATION = 'highest';

    /** @var string aggregation_type */
    private $aggregation_type = null;

    /** @var pathway[]|null These are active pathways t*/
    private $active_pathways = null;

    /** @var competency */
    private $competency;

    public function __construct(competency $competency) {
        $this->competency = $competency;
    }

    public function get_competency(): competency {
        return $this->competency;
    }

    /**
     * Returns true if the aggregation type is set for this competency
     *
     * @param string|null $aggregation_type specify if a specific type should be checked
     * @return bool
     */
    public function has_aggregation_type(string $aggregation_type = null): bool {
        return scale_aggregation::repository()
            ->where('competency_id', $this->get_competency()->id)
            ->when(!empty($aggregation_type), function (repository $repository) use ($aggregation_type) {
                $repository->where('type', $aggregation_type);
            })
            ->exists();
    }

    /**
     * Get the scale aggregation type used in this competency
     *
     * @return string Scale aggregation type
     */
    public function get_aggregation_type(): string {
        if (empty($this->aggregation_type)) {
            /** @var scale_aggregation $aggregation */
            $aggregation = scale_aggregation::repository()
                ->where('competency_id', $this->get_competency()->id)
                ->one();

            if ($aggregation) {
                $this->aggregation_type = $aggregation->type;
            }
        }

        // For now default to 'highest'
        return $this->aggregation_type ?? self::DEFAULT_AGGREGATION;
    }

    /**
     * Set the scale aggregation type used in this competency
     *
     * @param string $type Scale aggregation type
     * @return $this
     */
    public function set_aggregation_type(string $type): achievement_configuration {
        // Check that the type is valid - this will throw an error if invalid
        overall_aggregation_factory::get_classname($type);

        $this->aggregation_type = $type;
        return $this;
    }

    /**
     * Get the active pathways associated with this configuration.
     *
     * Loads from the database if necessary.
     *
     * @return pathway[]
     */
    public function get_active_pathways(): array {
        if (is_null($this->active_pathways)) {
            $pathways = $this->get_competency()->active_pathways;

            $this->active_pathways = [];
            foreach ($pathways as $pathway) {
                // We already have the competency entity so attach it to the pathway
                // to avoid another query further down the line
                $pathway->relate('competency', $this->get_competency());
                $this->active_pathways[$pathway->id] = pathway_factory::from_entity($pathway);
            }
        }

        return $this->active_pathways;
    }

    /**
     * Delete the specified pathways from the competency
     *
     * @param array $pathways
     * @param int|null $action_time
     */
    public function delete_pathways(array $pathways, ?int $action_time = null) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $this->save_configuration_history($action_time);

        $pathway_ids = [];
        foreach ($pathways as $pathway) {
            try {
                $pw = pathway_factory::fetch($pathway['type'], $pathway['id']);
            } catch (\Exception $e) {
                // Ignore non-existent pathways
                continue;
            }

            $pathway_ids[] = $pathway['id'];
            $pw->delete();
        }

        if (!empty($pathway_ids)) {
            // Assigned users will be queued through competency's watcher of pathways_deleted
            configuration_change::add_competency_entry(
                $this->competency->id,
                configuration_change::CHANGED_CRITERIA,
                $action_time
            );
        }

        $transaction->allow_commit();
    }

    /**
     * Save an aggregation type
     *
     * @param int|null $action_time Time when this action was initiated.
     * @return $this
     */
    public function save_aggregation(?int $action_time = null): achievement_configuration {
        builder::get_db()->transaction(function () use ($action_time) {
            $type = $this->get_aggregation_type();

            /** @var scale_aggregation $aggregation */
            $aggregation = scale_aggregation::repository()
                ->where('competency_id', $this->get_competency()->id)
                ->one();

            if (!$aggregation) {
                $aggregation = new scale_aggregation();
                $aggregation->competency_id = $this->get_competency()->id;
            } else if ($type == $aggregation->type) {
                // In case it did not change don't do anything
                return;
            }

            $this->save_configuration_history($action_time);

            $aggregation->type = $type;
            $aggregation->timemodified = time();
            $aggregation->save();

            configuration_change::add_competency_entry(
                $this->competency->id,
                configuration_change::CHANGED_AGGREGATION,
                $action_time
            );
        });

        return $this;
    }

    /**
     * Determine whether any single-use criterion is used in this competency's criteria
     *
     * @return bool
     */
    public function has_singleuse_criteria() {
        foreach ($this->get_active_pathways() as $pw) {
            if ($pw->get_classification() == pathway::PATHWAY_SINGLE_VALUE && $pw->has_singleuse_criteria()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dump the current configuration in json format for historical purposes in the history table
     *
     * @param int|null $action_time
     * @param string|null $configuration_dump
     * @return $this
     */
    public function save_configuration_history(?int $action_time = null, ?string $configuration_dump = null) {
        global $DB;

        if (!is_null($action_time)) {
            $saved = configuration_history::repository()
                ->where('competency_id', '=', $this->competency->id)
                ->where('active_from', '=', $action_time)
                ->one();

            if (!is_null($saved)) {
                // We only log a change once - expecting client (ui) to use the same action time when applying changes
                return $this;
            }
        }

        $action_time = $action_time ?? time();

        $transaction = $DB->start_delegated_transaction();

        // First set the last dump's 'active_to' timestamp
        $last_history = configuration_history::repository()
            ->where('competency_id', '=', $this->competency->id)
            ->where('active_to', '=', null)
            ->one();

        if (!is_null($last_history)) {
            $last_history->active_to = $action_time;
            $last_history->save();
        }

        $configuration_dump = $configuration_dump ?? self::get_current_configuration_dump($this->competency->id);

        $entry = new configuration_history();
        $entry->competency_id = $this->competency->id;
        $entry->active_from = $action_time ?? time();
        $entry->configuration = $configuration_dump;
        $entry->save();

        $transaction->allow_commit();

        return $this;
    }

    /**
     * Get a dump of the current configuration.
     * Data is read from the database and not the instance as the instance may
     * already have been updated
     *
     * @param int $competency_id Competency id
     * @return string Configuration dump
     */
    public static function get_current_configuration_dump(int $competency_id): string {
        global $DB;

        $params = ['competency_id' => $competency_id];

        $dumpobj = [
            'aggregation' => $DB->get_field('totara_competency_scale_aggregation', 'type', $params),
            'pathways' => pathway::dump_competency_pathways($competency_id),
        ];

        if ($dumpobj['aggregation'] === false) {
            $dumpobj['aggregation'] = self::DEFAULT_AGGREGATION;
        }

        return json_encode($dumpobj);
    }

    /**
     * Determine whether the user can become proficient through this configuration
     * @return bool
     */
    public function user_can_become_proficient(): bool {
        if (!$this->is_aggregation_type_enabled()) {
            return false;
        }

        $pathways = $this->get_active_pathways();
        foreach ($pathways as $pathway) {
            if ($pathway->leads_to_proficiency()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Export detail of all active pathways for the UI.
     * Pathways are grouped per resulting scale value.
     * Multi-value pathways (e.g. manual, learning plan) are grouped together in a single scale value with id 0
     *
     * @return array
     */
    public function export_pathway_groups(): array {
        $pathways = $this->get_active_pathways();
        $pathways = array_map(function (pathway $pathway) {
            return $pathway->export_edit_detail();
        }, $pathways);

        $singlevalue_pathways = array_filter($pathways, function ($pathway) {
            return $pathway['classification'] === pathway::PATHWAY_SINGLE_VALUE;
        });

        // Active pathways are already ordered by sortorder
        $first_singlevalue_sortorder = 0;
        if (!empty($singlevalue_pathways)) {
            $first_singlevalue_pw = reset($singlevalue_pathways);
            $first_singlevalue_sortorder = $first_singlevalue_pw['sortorder'];
        }

        /** @var collection $scale_values */
        $scale_values = $this->competency->scale->sorted_values_high_to_low
            ->all();

        $scale_values = array_map(function ($scale_value) use ($singlevalue_pathways) {
            $sv_pathways = array_values(array_filter($singlevalue_pathways, function ($pathway) use ($scale_value) {
                return $pathway['scalevalue'] === $scale_value->id;
            }));

            array_walk($sv_pathways, function (&$pathway, $idx) {
                if ($idx > 0) {
                    $pathway['showor'] = true;
                }
            });

            return [
                'id' => $scale_value->id,
                'name' => format_string($scale_value->name, true, ['context' => context_system::instance()]),
                'proficient' => (int)$scale_value->proficient,
                'sortorder' => $scale_value->sortorder,
                'pathways' => array_values($sv_pathways),
                'num_pathways' => count($sv_pathways),
                'criteria_type_level' => 'scalevalue',
            ];
        }, $scale_values);

        $low_multivalue_pathways = array_filter($pathways, function ($pathway) use ($first_singlevalue_sortorder) {
            return $pathway['classification'] === pathway::PATHWAY_MULTI_VALUE
                && ($first_singlevalue_sortorder == 0
                || $pathway['sortorder'] < $first_singlevalue_sortorder);
        });

        $high_multivalue_pathways = array_filter($pathways, function ($pathway) use ($first_singlevalue_sortorder) {
            return $pathway['classification'] === pathway::PATHWAY_MULTI_VALUE
                && $first_singlevalue_sortorder > 0
                && $pathway['sortorder'] >= $first_singlevalue_sortorder;
        });

        $groups = [
            [
                'id' => 'low-sortorder',
                'name' => get_string('any_scale_value', 'totara_competency'),
                'hidden' => false,
                'pathways' => array_values($low_multivalue_pathways),
            ],
            [
                'id' => 'singlevalue',
                'group_templatename' => 'totara_competency/scalevalue_pathways_edit',
                'hidden' => count($singlevalue_pathways) == 0,
                'scale_values' => array_values($scale_values),
            ],
            [
                'id' => 'high-sortorder',
                'name' => get_string('any_scale_value', 'totara_competency'),
                'hidden' => false,
                'pathways' => array_values($high_multivalue_pathways),
            ],
        ];

        return $groups;
    }

    /**
     * Check whether the used aggregation type plugin is enabled
     * @return bool
     */
    private function is_aggregation_type_enabled(): bool {
        $enabledtypes = plugin_types::get_enabled_plugins('aggregation', 'totara_competency');
        return in_array($this->get_aggregation_type(), $enabledtypes);
    }

}
