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
 * @package totara_comment
 */
namespace totara_comment;

use coding_exception;

final class resolver_factory {
    /**
     * @param string $component
     * @return resolver
     */
    public static function create_resolver(string $component): resolver {
        global $CFG;

        $clean = clean_param($component, PARAM_COMPONENT);
        if ($clean != $component) {
            throw new coding_exception("Invalid component parameter '{$component}'");
        }

        $class_name = "\\{$component}\\totara_comment\\comment_resolver";
        if (class_exists($class_name) && is_subclass_of($class_name, resolver::class)) {
            return new $class_name();
        }

        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            // We use our own default resolver, if the resolver for the component is not found.
            require_once("{$CFG->dirroot}/totara/comment/tests/fixtures/totara_comment_default_resolver.php");
            $resolver = new \totara_comment_default_resolver();
            $resolver->set_component($component);

            return $resolver;
        }

        throw new coding_exception("There is no such resolver for component '{$component}'");
    }
}