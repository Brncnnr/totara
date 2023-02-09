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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * File entity
 *
 * @property-read int $id ID
 * @property string $contenthash
 * @property string $pathnamehash
 * @property int $contextid
 * @property string $component
 * @property string $filearea
 * @property int $itemid
 * @property string $filepath
 * @property string $filename
 * @property int $userid
 * @property int $filesize
 * @property string $mimetype
 * @property int $status
 * @property string $source
 * @property string $author
 * @property string $license
 * @property int $timecreated
 * @property int $timemodified
 * @property int $sortorder
 * @property int $referencefileid
 *
 * @property-read context $context
 *
 * @package core\entity
 */
class file extends entity {

    public const TABLE = 'files';

    public const CREATED_TIMESTAMP = 'timecreated';

    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @return belongs_to
     */
    public function context(): belongs_to {
        return $this->belongs_to(context::class, 'contextid');
    }

}
