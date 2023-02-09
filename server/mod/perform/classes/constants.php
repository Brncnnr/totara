<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform;

abstract class constants {
    public const SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT = 'ONE_PER_SUBJECT';
    public const SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB = 'ONE_PER_JOB';

    /**
     * @deprecated since Totara 16; use models\activity\trigger\repeating classes
     * instead.
     */
    public const SCHEDULE_REPEATING_AFTER_CREATION = 'AFTER_CREATION';
    /**
     * @deprecated since Totara 16; use models\activity\trigger\repeating classes
     * instead.
     */
    public const SCHEDULE_REPEATING_AFTER_CREATION_WHEN_COMPLETE = 'AFTER_CREATION_WHEN_COMPLETE';
    /**
     * @deprecated since Totara 16; use models\activity\trigger\repeating classes
     * instead.
     */
    public const SCHEDULE_REPEATING_AFTER_COMPLETION = 'AFTER_COMPLETION';
    /**
     * @deprecated since Totara 16; temporary placeholder for other new
     * models\activity\trigger\repeating classes.
     */
    public const SCHEDULE_REPEATING_UNSET = 'UNSET';

    public const RELATIONSHIP_SUBJECT = 'subject';
    public const RELATIONSHIP_MANAGER = 'manager';
    public const RELATIONSHIP_MANAGERS_MANAGER = 'managers_manager';
    public const RELATIONSHIP_APPRAISER = 'appraiser';
    public const RELATIONSHIP_DIRECT_REPORT = 'direct_report';
    public const RELATIONSHIP_PEER = 'perform_peer';
    public const RELATIONSHIP_REVIEWER = 'perform_reviewer';
    public const RELATIONSHIP_MENTOR = 'perform_mentor';
    public const RELATIONSHIP_EXTERNAL = 'perform_external';

}
