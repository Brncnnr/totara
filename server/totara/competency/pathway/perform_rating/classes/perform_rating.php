<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating;

use core_component;
use pathway_perform_rating\models\perform_rating as perform_rating_model;
use totara_competency\base_achievement_detail;
use totara_competency\pathway;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;

/**
 * This pathway uses the rating given in a performance activity for a competency and user.
 *
 * Only the latest rating will be used for the aggregation.
 */
class perform_rating extends pathway {

    public const CLASSIFICATION = self::PATHWAY_MULTI_VALUE;

    /********************************************************************
     * Instantiation
     ********************************************************************/

    /**
     * Load the manual configuration from the database
     */
    protected function fetch_configuration(): void {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function is_enabled(): bool {
        // This pathway only works if perform is enabled and the linked review plugin is present
        return advanced_feature::is_enabled('performance_activities')
            && core_component::get_plugin_directory('performelement', 'linked_review');
    }

    /**
     * @inheritDoc
     */
    public function is_singleuse(): bool {
        return true;
    }

    /****************************************************************************
     * Saving
     ****************************************************************************/

    /**
     * Save the configuration
     */
    protected function save_configuration() {
        // Do nothing.
    }

    /**
     * Determine whether there are any difference between the instance
     * and the stored values
     *
     * @return bool
     */
    protected function configuration_is_dirty(): bool {
        return false;
    }


    /**
     * Delete the pathway specific detail
     */
    protected function delete_configuration(): void {
    }


    /**************************************************************************
     * Aggregation
     **************************************************************************/

    /**
     * @param int $user_id
     * @return achievement_detail
     */
    public function aggregate_current_value(int $user_id): base_achievement_detail {
        $rating = perform_rating_model::get_latest($this->get_competency()->id, $user_id);

        $achievement_detail = new achievement_detail();
        $achievement_detail->add_rating($rating);

        return $achievement_detail;
    }


    /****************************************************************************
     * Getters and setters
     ****************************************************************************/

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('achievement_path_group_label', 'pathway_perform_rating');
    }

    /*******************************************************************************************************
     * Mustache template data exporting
     *******************************************************************************************************/

    /**
     * Return the name of the template to use for editing this pathway
     *
     * @return string Template name
     */
    public function get_edit_template(): string {
        return 'pathway_perform_rating/pathway_perform_rating_edit';
    }

    /*******************************************************************************************************
     * Pathway copying
     *******************************************************************************************************/
    /**
     * @inheritDoc
     */
    public function copy_to_competency(competency $competency): competency {
        (new self())
            ->set_competency($competency)
            ->set_sortorder($this->get_sortorder())
            ->set_path_instance_id(null)
            ->set_status($this->get_status())
            ->save();

        return $competency;
    }
}
