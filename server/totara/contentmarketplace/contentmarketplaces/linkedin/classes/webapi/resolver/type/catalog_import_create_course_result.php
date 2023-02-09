<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\webapi\resolver\type;

use coding_exception;
use contentmarketplace_linkedin\dto\course_creation_result as result;
use core\webapi\execution_context;
use core\webapi\type_resolver;

class catalog_import_create_course_result extends type_resolver {
    /**
     * @param string            $field
     * @param result            $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof result)) {
            throw new coding_exception(
                sprintf(
                    "Expect the second argument is a type of '%s'",
                    result::class
                )
            );
        }

        switch ($field) {
            case 'success':
                return $source->is_successful();

            case 'message':
                return $source->get_message();

            case 'redirect_url':
                $url = $source->get_redirect_url();
                if (null !== $url) {
                    return $url->out();
                }

                return null;

            default:
                throw new coding_exception("Field '{$field}' is not supported yet");
        }
    }
}
