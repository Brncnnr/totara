<?php

namespace totara_tenant\output;

use html_table;
use html_table_cell;
use html_table_row;

final class upload_results_table extends html_table {
    /** The name of the mustache template */
    const TEMPLATE_NAME = 'core/table';

    /**
     * Retrieve the template name which should be used to render this table.
     * @return string
     */
    public function get_template(): string {
        return self::TEMPLATE_NAME;
    }

    public static function create(array $results): upload_results_table {

        $self = new self();
        $self->initialise($results);

        return $self;
    }

    private function initialise(array $results) {
        $this->summary = get_string('useruploadresultstable', 'totara_tenant');

        $tableheader = $this->table_header();

        $this->attributes['class'] = 'tenant_upload_user_table generaltable boxaligncenter';
        $this->head = array_map(
            function ($key, $value) {
                return new \html_table_cell($value);
            },
            array_keys($tableheader),
            $tableheader
        );

        $this->data = [];

        foreach ($results as $result) {
            $row = [];

            $row[] = new html_table_cell($result->line);
            $row[] = new html_table_cell($result->fullname);
            $row[] = new html_table_cell($result->username);
            $row[] = new html_table_cell($result->email);
            $row[] = new html_table_cell($result->idnumber);
            $row[] = new html_table_cell($result->suspended);
            $row[] = new html_table_cell($result->errors);

            $tablerow = new html_table_row($row);
            $this->data[] = $tablerow;
        }
    }

    private function table_header(): array {
        $tableheader = [];

        $tableheader[] = get_string('uucsvline', 'tool_uploaduser');
        $tableheader[] = get_string('userfullname', 'totara_reportbuilder');
        $tableheader[] = get_string('username');
        $tableheader[] = get_string('email');
        $tableheader[] = get_string('idnumber');
        $tableheader[] = get_string('suspended', 'auth');
        $tableheader[] = get_string('useruploaderrors', 'totara_tenant');

        return $tableheader;
    }
}
