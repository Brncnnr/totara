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
  <div class="tui-competencyDetailActivityLog">
    <h4 class="tui-competencyDetailActivityLog__title">
      {{ $str('activity_log', 'totara_competency') }}
    </h4>

    <FilterBar
      v-if="assignments.length > 1"
      :title="$str('activity_log_filter', 'totara_competency')"
    >
      <template v-slot:filters-left>
        <SelectFilter
          v-model="assignmentFilter"
          :label="$str('assignment', 'totara_competency')"
          :name="'activity_log_select'"
          :options="assignments"
          :show-label="true"
          :stacked="false"
        />
      </template>
    </FilterBar>

    <Loader :loading="$apollo.loading">
      <ActivityLogTable :log="log" />
    </Loader>
  </div>
</template>

<script>
// Components
import ActivityLogTable from 'totara_competency/components/details/ActivityLogTable';
import FilterBar from 'tui/components/filters/FilterBar';
import Loader from 'tui/components/loading/Loader';
import SelectFilter from 'tui/components/filters/SelectFilter';
// GraphQL
import ActivityLogQuery from 'totara_competency/graphql/activity_log';

export default {
  components: {
    ActivityLogTable,
    FilterBar,
    Loader,
    SelectFilter,
  },
  props: {
    userId: {
      required: true,
      type: Number,
    },
    competencyId: {
      required: true,
      type: Number,
    },
  },

  data: function() {
    return {
      log: [],
      assignments: [],
      assignmentFilter: 0,
    };
  },

  /**
   * Fetch the activity log content
   *
   */
  apollo: {
    log: {
      query: ActivityLogQuery,
      variables() {
        return {
          user_id: this.userId,
          competency_id: this.competencyId,
          filters: this.selectedFilter,
        };
      },
      update({ totara_competency_activity_log: log }) {
        this.assignments = this.refreshAssignmentList(log);
        return log;
      },
    },
  },

  computed: {
    /**
     * Provided filter value in correct format for apollo
     *
     * @return {Object}
     */
    selectedFilter() {
      if (this.assignmentFilter) {
        return { assignment_id: this.assignmentFilter };
      }
      return {};
    },
  },

  methods: {
    /**
     * Create an array of assignments to be used for the filter
     *
     * @return {Array}
     */
    refreshAssignmentList(log) {
      let assignmentList = {};

      log.forEach(entry => {
        if (entry.assignment != null) {
          assignmentList[entry.assignment.id] = {
            id: entry.assignment.id,
            label: this.getFilterLabel(entry),
          };
        }
      });

      assignmentList = Object.values(assignmentList);
      if (assignmentList.length >= this.assignments.length) {
        // Add placeholder 'all' option
        assignmentList.unshift({
          label: this.$str('all', 'core'),
          id: 0,
        });
        return assignmentList;
      } else {
        return this.assignments;
      }
    },

    /**
     * Modify the assignment label if it was directly assigned
     *
     * @param {Object} entry
     * @return {String}
     */
    getFilterLabel(entry) {
      let label = entry.assignment.progress_name;
      // Special handling for direct assignments which we list
      // as "Directly assigned", this adds some more information
      // to the string which includes the full name of the assigner and their role
      if (this.isDirectlyAssigned(entry.assignment)) {
        let str_options = {
          progress_name: entry.assignment.progress_name,
          user_fullname_role: entry.assignment.reason_assigned,
        };
        label = this.$str(
          'progress_name_by_user',
          'totara_competency',
          str_options
        );
      }

      return label;
    },

    /**
     * Calculate if label was directly assigned
     *
     * @param {Object} assignment
     * @return {Boolean}
     */
    isDirectlyAssigned(assignment) {
      if (assignment.user_group_type !== 'user') {
        return false;
      }

      return assignment.type === 'admin' || assignment.type === 'other';
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "all"
    ],
    "totara_competency": [
      "activity_log",
      "activity_log_filter",
      "assignment",
      "progress_name_by_user"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyDetailActivityLog {
  margin: var(--gap-2) var(--gap-4);

  &__title {
    @include tui-font-heading-small();
    padding-top: var(--gap-2);
  }
}

@media screen and (min-width: $tui-screen-xs) {
  .tui-competencyDetailActivityLog {
    &__proficient {
      font-weight: bold;
      &-srOnly {
        @include sr-only();
      }
    }
  }
}
</style>
