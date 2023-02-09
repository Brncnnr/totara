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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\totara_catalog;

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataholder;
use totara_contentmarketplace\entity\course_module_source;
use totara_contentmarketplace\totara_catalog\dataformatter\course_logo;

class course_logo_dataholder_factory {
    /**
     * @var string
     */
    public const DATAHOLDER_KEY = "content_marketplace";

    /**
     * @var string
     */
    public const DATAHOLDER_NAME = "marketplace course logo";

    /**
     * @return dataholder[]
     */
    public static function get_dataholders(): array {
        global $DB;
        return [
            // Content marketplace logo
            new dataholder(
                self::DATAHOLDER_KEY,
                self::DATAHOLDER_NAME,
                [
                    formatter::TYPE_PLACEHOLDER_IMAGE => new course_logo(
                        'cm_source.marketplace_component',
                        'cm_source.cm_ids'
                    )
                ],
                [
                    self::DATAHOLDER_KEY => "
                        LEFT JOIN (
                            SELECT
                                cm.course,
                                " . $DB->sql_group_concat('cm_source.marketplace_component', '|') . " marketplace_component,
                                " . $DB->sql_group_concat('cm_source.cm_id', '|') . " cm_ids
                            FROM {course_modules} cm
                            INNER JOIN {totara_contentmarketplace_course_module_source} cm_source ON cm_source.cm_id = cm.id
                            GROUP BY cm.course
                        ) cm_source ON cm_source.course = base.id
                    "
                ]
            )
        ];
    }

    /**
     * @return string|null
     */
    public static function get_course_logo_key(): ?string {
        return course_module_source::repository()->count() === 0 ? null : self::DATAHOLDER_KEY;
    }
}