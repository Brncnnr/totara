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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_learning_plan
 */

namespace pathway_learning_plan\watcher;

use core\hook\admin_setting_changed;
use core\orm\query\builder;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\hook\competency_validity_changed;
use totara_competency\pathway;
use totara_core\advanced_feature;

class totara_core {

    public static function admin_settings_changed(admin_setting_changed $hook) {
        if ($hook->name !== 'enablelearningplans') {
            return;
        }

        if (advanced_feature::check('learningplans', (int)$hook->oldvalue)) {
            return;
        }

        $affected_competency_ids = static::get_competencies_with_learning_plan_pathways();
        if (empty($affected_competency_ids)) {
            return;
        }

        static::update_pathway_validity(advanced_feature::is_enabled('learningplans'));

        $hook = new competency_validity_changed($affected_competency_ids);
        $hook->execute();
    }


    /**
     * @return int[]
     */
    private static function get_competencies_with_learning_plan_pathways(): array {
        return pathway_entity::repository()
            ->as('pw')
            ->select_raw('DISTINCT pw.competency_id AS competency_id')
            ->where('pw.path_type', 'learning_plan')
            ->where('pw.status', pathway::PATHWAY_STATUS_ACTIVE)
            ->get()
            ->pluck('competency_id');
    }

    /**
     * @param bool $new_validity
     */
    private static function update_pathway_validity(bool $valid) {
        builder::table(pathway_entity::TABLE)
            ->where('path_type', 'learning_plan')
            ->where('status', pathway::PATHWAY_STATUS_ACTIVE)
            ->update(['valid' => (int)$valid]);
    }
}
