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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

use totara_cohort\rules\ui\none_min_max_exactly as none_min_max_exactly;

/**
 * UI for dynamic audience based on the fact if the person has direct reports.
 */
class has_direct_reports extends none_min_max_exactly {

    /**
     * Number of direct reports
     */
    public function __construct() {
        $this->description = get_string('ruledesc-alljobassign-hasdirectreports', 'totara_cohort');
        $this->label = get_string('rulelegend-alljobassign-hasdirectreports', 'totara_cohort');
        parent::__construct();
    }
}

/**
 * UI for dynamic audience based on the fact if the person has temporary reports.
 */
class has_temporary_reports extends none_min_max_exactly {

    /**
     * Number of temporary reports
     */
    public function __construct() {
        $this->description = get_string('ruledesc-alljobassign-hastemporaryreports', 'totara_cohort');
        $this->label = get_string('rulelegend-alljobassign-hastemporaryreports', 'totara_cohort');
        parent::__construct();
    }
}

/**
 * Number of indirect reports
 */
class has_indirect_reports extends none_min_max_exactly {

    /** @var string select box legend */
    public $label;

    public function __construct() {
        $this->description = get_string('ruledesc-alljobassign-hasindirectreports', 'totara_cohort');
        $this->label = get_string('rulelegend-alljobassign-hasindirectreports', 'totara_cohort');
        parent::__construct();
    }
}

/**
 * UI for dynamic audience based on the fact if the person has appraisal reports.
 */
class has_appraisees extends none_min_max_exactly {

    /**
     * Number of appraisers
     */
    public function __construct() {
        $this->description = get_string('ruledesc-alljobassign-hasappraisees', 'totara_cohort');
        $this->label = get_string('rulelegend-alljobassign-hasappraisees', 'totara_cohort');
        parent::__construct();
    }
}
