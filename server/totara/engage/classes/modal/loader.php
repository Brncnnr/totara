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
 * @package totara_engage
 */
namespace totara_engage\modal;

use totara_engage\local\helper;

final class loader {
    /**
     * loader constructor.
     */
    private function __construct() {
        // Preventing the construction.
    }

    /**
     * Getting all the modals from other components first, then including the sub-plugins
     * @param array $exclude
     * @return modal[]
     */
    public static function get_all(?array $exclude = []): array {
        $classes = \core_component::get_namespace_classes(
            'totara_engage\\modal',
            modal::class
        );

        $modals = [];
        foreach ($classes as $cls) {
            $parts = explode('\\', $cls);
            if (in_array(reset($parts), $exclude)) {
                continue;
            }

            /** @var modal $modal */
            $instance = new $cls();
            if (!$instance->show_modal()) {
                continue;
            }

            $modals[] = new $cls();
        }

        usort(
            $modals,
            function (modal $a, modal $b) {
                $ordera = $a->get_order();
                $orderb = $b->get_order();

                if ($ordera == $orderb) {
                    return 0;
                } else if ($ordera > $orderb) {
                    return 1;
                }

                return -1;
            }
        );

        return $modals;
    }
}