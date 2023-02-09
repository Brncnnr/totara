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

use criteria_linkedcourses\linkedcourses;
use criteria_onactivate\onactivate;
use pathway_criteria_group\criteria_group;
use pathway_manual\manual;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use totara_criteria\criterion;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;

/**
 * Class with generic configuration methods
 */

class achievement_criteria {

    /**
     * Get available overall aggregation methods
     *
     * @return array containing name, description and has_ui attributes for each available aggregation method
     */
    public static function get_available_overall_aggregation_methods(): array {
        $methods = [];
        $enabledtypes = plugin_types::get_enabled_plugins('aggregation', 'totara_competency');
        foreach ($enabledtypes as $agg_type) {
            $methods[] = overall_aggregation_factory::create($agg_type);
        }

        return $methods;
    }

    /**
     * Get the default pathways to use with bulk configuration for the specific scale
     * For now hardcoded. Can later be obtained from configuration or table if needed
     * Returned structure same as for competency specific pathways
     *
     * @param scale $scale Scale containing the default value and lowest proficient value
     * @param int $competency_id Competency id
     * @return array of pathways
     */
    public static function get_default_pathways(scale $scale, ?int $competency_id = null): array {
        // Manager rating
        // Self rating
        // Completion of linked coursed for minumum proficiency value
        // Assignment activation for lowest scale rating

        $pathways = [];

        $pw = new manual();
        $pw->set_sortorder(1)
            ->set_roles([manager::get_name()]);
        $pathways[] = $pw;

        $pw = new manual();
        $pw->set_sortorder(2)
            ->set_roles([self_role::get_name()]);
        $pathways[] = $pw;

        $crit = new linkedcourses();
        $crit->set_aggregation_method(criterion::AGGREGATE_ALL);
        if (!is_null($competency_id)) {
            $crit->set_competency_id($competency_id);
        }

        $pw = new criteria_group();
        $pw->set_sortorder(3)
            ->set_scale_value($scale->min_proficient_value)
            ->add_criterion($crit);
        $pathways[] = $pw;

        $crit = new onactivate();
        if (!is_null($competency_id)) {
            $crit->set_competency_id($competency_id);
        }

        $pw = new criteria_group();
        $pw->set_sortorder(4)
            ->set_scale_value(new scale_value($scale->get_attribute('defaultid')))
            ->add_criterion($crit);
        $pathways[] = $pw;

        return $pathways;
    }

}
