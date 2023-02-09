<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module totara_competency
-->

<template>
  <div v-if="!$apollo.loading" class="tui-competencySummaryGeneral">
    <SectionHeader :edit-url="editUrl" :title="$str('general', 'core')" />

    <Table
      :data="rows"
      :border-bottom-hidden="true"
      :border-top-hidden="true"
      :border-separator-hidden="true"
      :hover-off="true"
    >
      <template v-slot:row="{ row }">
        <Cell size="6" :heavy="true">
          <div
            class="tui-competencySummaryGeneral__list-label"
            role="rowheader"
          >
            {{ row.label }}
          </div>
        </Cell>

        <Cell size="10">
          <div class="tui-competencySummaryGeneral__list-value" role="cell">
            <template v-if="row.values">
              <div v-for="(item, index) in row.values" :key="'general' + index">
                {{ item }}
              </div>
            </template>
            <div v-else v-html="row.value" />
          </div>
        </Cell>
      </template>
    </Table>
  </div>
</template>

<script>
// Components
import Cell from 'tui/components/datatable/Cell';
import SectionHeader from 'totara_competency/components/summary/CompetencySummarySectionHeader';
import Table from 'tui/components/datatable/Table';
// Queries
import competencyQuery from 'totara_competency/graphql/competency';

export default {
  components: {
    Cell,
    SectionHeader,
    Table,
  },

  props: {
    competencyId: {
      type: Number,
      required: true,
    },
  },

  data() {
    return {
      competency: {},
      editUrl: '',
    };
  },

  computed: {
    /**
     * Get data for each row
     *
     * @return {Array}
     */
    rows() {
      let rows = [
        {
          label: this.$str('fullname', 'totara_competency'),
          value: this.competency.display_name,
        },
        {
          label: this.$str('idnumber', 'totara_competency'),
          value: this.competency.idnumber,
        },
        {
          label: this.$str('description', 'totara_competency'),
          value: this.competency.description,
        },
        {
          label: this.$str('type', 'totara_competency'),
          value: this.competency.type
            ? this.competency.type.display_name
            : false,
        },
        {
          label: this.$str('aggregationmethod', 'totara_hierarchy'),
          value: this.aggregationMethodName,
        },
        {
          label: this.$str(
            'assignment_creation_availability',
            'totara_competency'
          ),
          values: this.assignAvailabilityNames,
          value: false,
        },
      ];

      // Add custom fields (if any)
      if (this.competency.display_custom_fields) {
        this.competency.display_custom_fields.forEach(item => {
          rows.push({
            label: item.title,
            value: item.value,
          });
        });
      }

      rows = rows.filter(function(row) {
        return row.value || row.values;
      });

      return rows;
    },

    assignAvailabilityNames() {
      if (
        !this.competency.assign_availability ||
        !this.competency.assign_availability.length > 0
      ) {
        return false;
      }

      let assignments = [];

      //ASSIGNMENT_CREATE_SELF = 1
      //ASSIGNMENT_CREATE_OTHER = 2;
      this.competency.assign_availability.forEach(item => {
        assignments.push(
          this.$str(
            item === 1
              ? 'competency_assign_availability_self_simple'
              : 'competency_assign_availability_other_simple',
            'totara_competency'
          )
        );
      });

      return assignments;
    },

    aggregationMethodName() {
      if (!this.competency.aggregation_method) {
        return false;
      }

      let aggregationString =
        this.competency.aggregation_method === 1
          ? 'all'
          : this.competency.aggregation_method === 2
          ? 'any'
          : 'off';

      return this.$str(aggregationString, 'totara_competency');
    },
  },

  apollo: {
    competency: {
      query: competencyQuery,
      variables() {
        return {
          competency_id: this.competencyId,
        };
      },
      update({ totara_competency_competency: data }) {
        this.editUrl = this.$url('/totara/hierarchy/item/edit.php', {
          prefix: 'competency',
          id: data.id,
        });
        return data;
      },
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "general"
    ],
    "totara_competency": [
      "all",
      "any",
      "assignment_creation_availability",
      "competency_assign_availability_self_simple",
      "competency_assign_availability_other_simple",
      "description",
      "fullname",
      "idnumber",
      "off",
      "type"
    ],
    "totara_hierarchy": [
      "aggregationmethod"
    ]
  }
</lang-strings>
