<?php

namespace totara_hierarchy\webapi\resolver\type;

use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use hierarchy_competency\formatter\competency_achievement_path as competency_achievement_path_formatter;
use totara_competency\entity\pathway;

/**
 * Populates a GraphQL totara_hierarchy_competency_achievement_path type.
 */
class competency_achievement_path extends type_resolver {

    private const DEFAULT_FORMATS = [
        'id' => format::FORMAT_PLAIN,
        'type' => format::FORMAT_PLAIN,
        'name' => format::FORMAT_PLAIN,
        'instance_id' => format::FORMAT_PLAIN,
    ];

    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof pathway) {
            throw new \coding_exception(__METHOD__ . ' requires a pathway entity');
        }
        $format = $args['format'] ?? self::DEFAULT_FORMATS[$field] ?? null;
        $context = $ec->has_relevant_context()
            ? $ec->get_relevant_context()
            : context_system::instance();
        $formatter = new competency_achievement_path_formatter($source, $context);

        return $formatter->format($field, $format);
    }
}