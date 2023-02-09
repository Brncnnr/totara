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
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_competency\overall_aggregation_factory;

/**
 * General totata competency achievement configuration
 *
 * Please be aware that it is the responsibility of the query to ensure that the user is allowed to
 * see this.
 */
class achievement_configuration extends type_resolver {

    /**
     * Resolves a competency achievement configuration field.
     *
     * @param string $field
     * @param \totara_competency\achievement_configuration $configuration
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $configuration, array $args, execution_context $ec) {

        if (!$configuration instanceof \totara_competency\achievement_configuration) {
            throw new \coding_exception('Only \totara_competency\achievement_configuration objects are accepted: ' . gettype($configuration));
        }

        switch ($field) {
            case 'competency_id':
                return $configuration->get_competency()->id;
            case 'overall_aggregation':
                /** @var string $atype */
                $atype = $configuration->get_aggregation_type();
                return overall_aggregation_factory::create($atype);
            case 'paths':
                return $configuration->get_active_pathways();
        }

        throw new \coding_exception('Unknown field', $field);
    }
}
