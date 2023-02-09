<?php

namespace totara_competency\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\query\builder;
use totara_competency\aggregation_users_table;
use totara_competency\models\assignment as assignment_model;
use totara_competency\hook\competency_configuration_changed;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_assignment;

/**
 * @property-read int $id ID
 * @property int $competency_id
 * @property int $assignment_id
 * @property int $time_changed
 * @property string $change_type
 * @property string $related_info
 *
 * @property-read assignment $assignment
 * @property-read competency $competency
 */
class configuration_change extends entity {

    public const TABLE = 'totara_competency_configuration_change';

    // Configuration change constants
    public const CHANGED_CRITERIA = 'criteria_changed';
    public const CHANGED_COMPETENCY_AGGREGATION = 'competency_aggregation_changed';
    public const CHANGED_AGGREGATION = 'aggregation_changed';
    public const CHANGED_MIN_PROFICIENCY = 'min_proficiency_changed';
    public const OVERRIDE_MIN_PROFICIENCY = 'override_min_proficiency';

    /**
     * Log a configuration change
     *
     * @param int $competency_id
     * @param string $change_type Type of change to log
     * @param int|null $action_time
     * @param bool $queue defaults to true, st to false to skip queueing
     * @return configuration_change
     */
    public static function add_competency_entry(
        int $competency_id,
        string $change_type,
        ?int $action_time = null,
        bool $queue = false
    ): configuration_change {
        $valid_types = [
            self::CHANGED_AGGREGATION,
            self::CHANGED_CRITERIA,
            self::CHANGED_MIN_PROFICIENCY,
            self::CHANGED_COMPETENCY_AGGREGATION,
        ];

        if (!in_array($change_type, $valid_types)) {
            throw new \coding_exception('Invalid configuration change type');
        }

        if (!is_null($action_time)) {
            $logged = configuration_change::repository()
                ->where('competency_id', '=', $competency_id)
                ->where('change_type', '=', $change_type)
                ->where('time_changed', '=', $action_time)
                ->one();

            if (!is_null($logged)) {
                /** @var configuration_change $logged */
                // We only log a change once - expecting client (ui) to use the same action time when applying changes
                return $logged;
            }
        }

        // In some cases we want to do special queueing outside of this function,
        // TODO TO BE RESOLVED IN TL-28190: Move this somewehere else, it should not be in the entity
        if ($queue) {
            // Adding each assigned user to the aggregation queue
            (new aggregation_users_table())->queue_all_assigned_users_for_aggregation($competency_id);
        }

        $entry = new configuration_change();
        $entry->competency_id = $competency_id;
        $entry->change_type = $change_type;
        $entry->time_changed = $action_time ?? time();
        $entry->save();

        $hook = new competency_configuration_changed($competency_id, $change_type);
        $hook->execute();

        return $entry;
    }

    /**
     * Add entries for each competency that uses a scale where the min proficiency has changed.
     *
     * @param int $scale_id
     * @param int $new_min_proficiency_id
     */
    public static function min_proficiency_change(int $scale_id, int $new_min_proficiency_id) {
        // Get all competencies using this scale.
        $competencies = competency::repository()
            ->join([scale_assignment::TABLE, 'csa'], 'frameworkid', 'csa.frameworkid')
            ->where('csa.scaleid', $scale_id)
            ->get_lazy();

        $time = time();
        $related_info = json_encode(['new_min_proficiency_id' => $new_min_proficiency_id]);
        $records = [];

        foreach ($competencies as $competency) {
            $entry = new \stdClass();
            $entry->competency_id = $competency->id;
            $entry->change_type = self::CHANGED_MIN_PROFICIENCY;
            $entry->time_changed = $time;
            $entry->related_info = $related_info;
            $records[] = $entry;
        }

        if (!empty($records)) {
            // For performance reasons we do a batch insert here
            // as potentially a lot of records could be affected
            builder::get_db()->insert_records_via_batch(configuration_change::TABLE, $records);
        }
    }

    /**
     * Add entries for user assigned via the changed assignments where the min proficiency was overridden.
     *
     * @param assignment_model|int $assignment
     * @param int|null $new_min_proficiency_id
     */
    public static function min_proficiency_override($assignment, ?int $new_min_proficiency_id) {
        if (!$assignment instanceof assignment_model && !is_number($assignment)) {
            throw new \coding_exception('assignment_model or assignment id expected. Instance of ' . get_class($assignment) . ' received');
        }

        if (!$assignment instanceof assignment_model) {
            $assignment = assignment_model::load_by_id($assignment);
        }

        $related_info = json_encode(['new_min_proficiency_id' => $new_min_proficiency_id]);

        $entry = new configuration_change();
        $entry->competency_id = $assignment->competency->id;
        $entry->assignment_id = $assignment->get_id();
        $entry->change_type = self::OVERRIDE_MIN_PROFICIENCY;
        $entry->time_changed = time();
        $entry->related_info = $related_info;
        $entry->save();
    }

    /**
     * Get the related info for this configuration change as an associative array.
     *
     * @return array
     */
    public function get_decoded_related_info(): array {
        if (empty($this->related_info)) {
            return [];
        }

        $decoded = json_decode($this->related_info, true);

        // related_info may have been 'NULL' in which case the above empty check would have returned false.
        if (empty($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Competency relation
     *
     * @return belongs_to
     */
    public function competency(): belongs_to {
        return $this->belongs_to(competency::class, 'competency_id');
    }

    /**
     * Assignment relation
     *
     * @return belongs_to
     */
    public function assignment(): belongs_to {
        return $this->belongs_to(assignment::class, 'assignment_id');
    }

}
