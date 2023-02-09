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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencyDetailArchivedAssignments">
    <h4 class="tui-competencyDetailArchivedAssignments__title">
      {{ $str('archived_assignments', 'totara_competency') }}
    </h4>

    <Loader :loading="$apollo.loading">
      <ArchivedAssignmentsTable :assignments="archivedAssignmentList" />
    </Loader>
  </div>
</template>

<script>
// Components
import ArchivedAssignmentsTable from 'totara_competency/components/details/ArchivedAssignmentsTable';
import Loader from 'tui/components/loading/Loader';
import { ASSIGNMENT_ARCHIVED } from 'totara_competency/constants';

// GraphQL
import CompetencyProfileDetailsQuery from 'totara_competency/graphql/profile_competency_details';

export default {
  components: {
    ArchivedAssignmentsTable,
    Loader,
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
      data: {
        competency: {},
        items: [],
      },
    };
  },

  /**
   * Fetch competency details assignments
   *
   */
  apollo: {
    data: {
      query: CompetencyProfileDetailsQuery,
      fetchPolicy: 'network-only',
      variables() {
        return {
          user_id: this.userId,
          competency_id: this.competencyId,
          status: ASSIGNMENT_ARCHIVED,
        };
      },
      update({
        totara_competency_profile_competency_details: { competency, items },
      }) {
        return { competency, items };
      },
    },
  },

  computed: {
    /**
     * Create an array of archieved assignments
     *
     * @return {Array}
     */
    archivedAssignmentList() {
      let archivedAssignmentList = [];

      // Filter for only archived assignments
      archivedAssignmentList = this.data.items.map(function(elem) {
        let archivedDate = null;
        if (elem.assignment.archived_at) {
          archivedDate = elem.assignment.archived_at;
        } else {
          archivedDate = elem.assignment.unassigned_at;
        }

        return {
          name: elem.assignment.progress_name,
          archivedAt: archivedDate,
          legacy: elem.assignment.type === 'legacy',
          proficient: elem.my_value.proficient,
        };
      });
      return archivedAssignmentList;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "archived_assignments"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyDetailArchivedAssignments {
  margin: var(--gap-2) var(--gap-4);

  &__title {
    @include tui-font-heading-small();
    padding-top: var(--gap-2);
  }
}
</style>
