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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\type;

use core\entity\user;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_approval\controllers\application\{edit, preview, view};
use mod_approval\exception\helper\validation;
use mod_approval\formatter\application\application_formatter;
use mod_approval\interactor\cached_application_interactor;
use mod_approval\model\application\application as application_model;
use mod_approval\model\application\application_workflow_stage;

/**
 * Note: It is the responsibility of the query to ensure the user is permitted to see an application.
 */
class application extends type_resolver {
    /**
     * @param string $field
     * @param application_model|object $application
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $application, array $args, execution_context $ec) {
        validation::instance_of($application, application_model::class, 'Expected application model');

        if ($field === 'workflow_stages') {
            return application_workflow_stage::load_all_by_application($application);
        }

        if ($field === 'interactor') {
            return cached_application_interactor::from_application_model($application, user::logged_in()->id);
        }

        if ($field === 'is_unsubmitted') {
            return $application->is_unsubmitted();
        }

        if ($field === 'page_urls') {
            return [
                'edit' => edit::get_url_for($application->id),
                'preview' => preview::get_url_for($application->id),
                'view' => view::get_url_for($application->id),
            ];
        }

        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $formatter = new application_formatter($application, $ec->get_relevant_context());
        return $formatter->format($field, $format);
    }
}
