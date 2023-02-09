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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query\event;

use core\orm\query\sql\query as sql_query;
use mod_facetoface\query\event\filter\filter;
use mod_facetoface\query\statement;

class query_notifications  implements event_query {
    /**
     * @var filter[]
     */
    private $filters;

    /**
     * @var status[]
     */
    private $status;

    /**
     * @var \facetoface_notification
     */
    private $facetoface_notification;

    /**
     * @var integer
     */
    private $time = 0;

    /**
     * query constructor.
     *
     * @param ?int $sessionid
     * @param \facetoface_notification $facetoface_notification
     */
    public function __construct(?int $sessionid, $facetoface_notification) {
        $this->sessionid = $sessionid;
        $this->facetoface_notification = $facetoface_notification;
        $this->filters = [];
    }

    /**
     * Injecting the filter object to build WHERE clause sql statement. If the filter is already existing in this
     * object, just overriding the existing one.
     *
     * @param filter $filter
     *
     * @return query
     */
    public function with_filter(filter $filter): query_notifications {
        $this->filters[$filter->get_name()] = $filter;
        return $this;
    }

    /**
     * Add multiple filter object to build WHERE clause sql statement in one go.
     *
     * @param filter ...$filters
     *
     * @return query
     */
    public function with_filters(filter ...$filters): query_notifications {
        foreach ($filters as $filter) {
            $this->with_filter($filter);
        }

        return $this;
    }

    /**
     * Injecting the status code to build WHERE clause sql statement.
     *
     * @param string $state mod_facetoface\signup\state\classname
     *
     * @return query
     */
    public function with_status(string $state): query_notifications {
        $classname = '\mod_facetoface\signup\state\\'.$state;
        $this->status[$state] = $classname::get_code();
        return $this;
    }

    /**
     * @param integer $time
     *
     * @return query
     */
    public function with_time(int $time): query_notifications {
        $this->time = $time;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function get_statement(): statement {
        $builder = filter_factory::query_notifications_sessions_and_dates($this->sessionid, $this->facetoface_notification);

        if (!empty($this->filters)) {
            // Start building our where clause here, if there are any filters provided for this query object.
            $time = $this->time > 0 ? $this->time : time();
            foreach ($this->filters as $filter) {
                $filter->apply($builder, $time);
            }
        }

        if (!empty($this->status)) {
            $builder->where_in('sis.statuscode', array_values($this->status));
        }

        [$sql, $params] = sql_query::from_builder($builder)->build();
        return new statement($sql, $params);
    }
}