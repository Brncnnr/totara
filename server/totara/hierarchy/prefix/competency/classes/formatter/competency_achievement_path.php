<?php

namespace hierarchy_competency\formatter;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;
use totara_competency\entity\pathway;

/**
 * @property pathway $object
 */
class competency_achievement_path extends formatter {

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'type' => string_field_formatter::class,
            'name' => string_field_formatter::class,
            'instance_id' => null
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'type':
                return $this->object->path_type;
            case 'instance_id':
                return $this->object->path_instance_id;
            case 'name':
                return pathway::get_type_name($this->object->path_type);
            default:
                return parent::get_field($field);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }
}