<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module hierarchy_goal
-->

<template>
  <ParticipantContent
    v-if="!$apollo.loading"
    :content="getPreviewData()"
    :preview="true"
  />
</template>

<script>
import ParticipantContent from 'hierarchy_goal/components/performelement_linked_review/ParticipantContent';

// GraphQL
import dateTodayQuery from 'totara_webapi/graphql/status';
import {
  COMPANY_GOAL,
  GOAL_SCOPE_COMPANY,
  GOAL_SCOPE_PERSONAL,
  PERSONAL_GOAL,
} from '../../js/constants';

export default {
  components: {
    ParticipantContent,
  },

  props: {
    data: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      dateToday: '01/01/1970',
    };
  },

  apollo: {
    dateToday: {
      query: dateTodayQuery,
      update({ totara_webapi_status }) {
        return totara_webapi_status.date;
      },
    },
  },

  computed: {
    /**
     * The goal type subset.
     * @return {?string}
     */
    goalScope() {
      switch (this.data.content_type) {
        case PERSONAL_GOAL:
          return GOAL_SCOPE_PERSONAL;
        case COMPANY_GOAL:
          return GOAL_SCOPE_COMPANY;
        default:
          return null;
      }
    },
  },

  methods: {
    /**
     * Set placeholder data for preview view
     *
     * @return {Object}
     */
    getPreviewData() {
      return {
        goal: {
          display_name: this.$str('example_goal_title', 'hierarchy_goal'),
          description: this.$str('example_goal_description', 'hierarchy_goal'),
          goal_scope: this.goalScope,
        },
        target_date: this.dateToday,
        status: this.$str('example_goal_status', 'hierarchy_goal'),
        completed: false,
      };
    },
  },
};
</script>

<lang-strings>
{
  "hierarchy_goal": [
    "example_goal_description",
    "example_goal_status",
    "example_goal_title"
  ]
}
</lang-strings>
