<?php

namespace totara_competency\webapi\resolver\query;

use core\webapi\execution_context;
use totara_competency\data_providers;

class activity_log extends profile_resolver {

    public static function resolve(array $args, execution_context $ec) {
        $user_id = static::authorize($args['user_id'] ?? null);

        return data_providers\activity_log::create($user_id, $args['competency_id'])
            ->set_filters($args['filters'] ?? [])
            ->fetch();
    }

}