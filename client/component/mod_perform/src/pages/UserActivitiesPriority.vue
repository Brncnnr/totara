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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->

<template>
  <Layout
    :loading="$apollo.loading"
    :title="$str('user_activities_page_title', 'mod_perform')"
    class="tui-performUserActivitiesPriority"
  >
    <template v-slot:content-nav>
      <PageBackLink
        :link="$url(userActivitiesUrl, { about_role: relationshipId })"
        :text="$str('back_to_user_activities', 'mod_perform')"
      />
    </template>

    <template v-slot:content>
      <div
        v-if="totalActivities"
        class="tui-performUserActivitiesPriority__content"
      >
        <h3 class="tui-performUserActivitiesPriority__heading">
          {{
            $str('user_activities_priority_page_heading', 'mod_perform', {
              number: totalActivities,
              role: relationshipName,
            })
          }}
        </h3>

        <Grid
          class="tui-performUserActivitiesPriority__grid"
          :max-units="gridItemsPerRow"
        >
          <GridItem
            v-for="item in subjectInstances"
            :key="item.id"
            class="tui-performUserActivitiesPriority__grid-card"
          >
            <ActivitiesPriorityCard
              :due-date="item.subject.due_on"
              :job-assignment="
                getJobAssignmentDescription(item.subject.job_assignment)
              "
              :overdue="item.subject.due_on && item.subject.due_on.is_overdue"
              :status="getProgressStatus(item.subject.participant_instances)"
              :subject-user="item.subject.subject_user"
              :title="getActivityTitle(item.subject)"
              :url="getViewActivityUrl(item)"
            />
          </GridItem>
        </Grid>

        <div
          v-if="nextPaginationCursor"
          class="tui-performUserActivitiesPriority__loadMore"
        >
          <Button :text="$str('loadmore', 'totara_core')" @click="loadMore" />
        </div>
      </div>
      <div v-else-if="!$apollo.loading">
        {{
          $str('user_activities_priority_page_heading', 'mod_perform', {
            number: totalActivities,
            role: relationshipName,
          })
        }}
      </div>
    </template>
  </Layout>
</template>

<script>
import ActivitiesPriorityCard from 'mod_perform/components/user_activities/list/ActivitiesPriorityCard';
import Button from 'tui/components/buttons/Button';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Layout from 'tui/components/layouts/LayoutOneColumn';
import PageBackLink from 'tui/components/layouts/PageBackLink';
import ResizeObserver from 'tui/polyfills/ResizeObserver';

import {
  getFirstSectionToParticipate,
  getYourProgressStatusValue,
  isRoleInstanceOverdue,
} from 'mod_perform/activities_util';
import { getOffsetRect } from 'tui/dom/position';
import { throttle } from 'tui/util';
// Query
import subjectInstancesQuery from 'mod_perform/graphql/my_subject_instances';

const THROTTLE_UPDATE = 150;

export default {
  components: {
    ActivitiesPriorityCard,
    Button,
    Grid,
    GridItem,
    Layout,
    PageBackLink,
  },

  props: {
    // The id of the current role
    relationshipId: Number,
    // The role the current user is viewing as
    relationshipName: String,
    // The url enables user navigate back to user activity list
    userActivitiesUrl: {
      required: true,
      type: String,
    },
    viewActivityUrl: String,
  },

  data() {
    return {
      // Predefined filters
      filters: {
        about_role: this.relationshipId,
        activity_type: null,
        exclude_complete: true,
        overdue: false,
        participant_progress: null,
        search_term: null,
      },
      gridItemsPerRow: 8,
      // Next cursor for load more button
      nextPaginationCursor: '',
      // Query filter options
      options: {
        sort_by: 'due_date',
      },
      // Number of items per page
      pageLimit: 24,
      // Activity data
      subjectInstances: [],
      // Total number of available activities
      totalActivities: 0,
    };
  },

  mounted() {
    this.resizeObserver = new ResizeObserver(
      throttle(this.$_measure, THROTTLE_UPDATE)
    );
    this.resizeObserver.observe(this.$el);
    this.$_measure();
  },

  destroyed() {
    this.resizeObserver.disconnect();
  },

  apollo: {
    subjectInstances: {
      query: subjectInstancesQuery,
      variables() {
        return {
          filters: this.filters,
          options: this.options,
          pagination: {
            limit: this.pageLimit,
          },
        };
      },
      update: data => data['mod_perform_my_subject_instances'].items,
      result({ data: { mod_perform_my_subject_instances: data } }) {
        this.nextPaginationCursor = data.next_cursor;
        this.totalActivities = data.total;
      },
    },
  },

  methods: {
    /**
     * Returns the activity title generated from the subject instance passed it.
     *
     * @param {Object} subject
     * @returns {string}
     */
    getActivityTitle(subject) {
      let title = subject.activity.name.trim();
      let suffix = subject.created_at ? subject.created_at.trim() : '';

      if (suffix) {
        return this.$str(
          'activity_title_with_subject_creation_date',
          'mod_perform',
          {
            title: title,
            date: suffix,
          }
        );
      }

      return title;
    },

    /**
     * Get text to describe the subject instance's job assignment.
     *
     * @param {Object} jobAssignment
     * @return {string|null}
     */
    getJobAssignmentDescription(jobAssignment) {
      if (!jobAssignment) {
        return;
      }

      const fullname = jobAssignment.fullname
        ? jobAssignment.fullname.trim()
        : null;

      // Fullname isn't a required field when creating a job assignment
      return fullname && fullname.length > 0
        ? fullname
        : this.$str(
            'unnamed_job_assignment',
            'mod_perform',
            jobAssignment.idnumber
          );
    },

    /**
     * Get "view" url for a specific user activity.
     *
     * @param subjectInstance {{Object}}
     * @returns {string}
     */
    getViewActivityUrl(subjectInstance) {
      const participantSection = getFirstSectionToParticipate(
        subjectInstance.sections,
        this.relationshipId
      );
      if (participantSection) {
        return this.$url(this.viewActivityUrl, {
          participant_section_id: participantSection.id,
        });
      }
      return '';
    },

    /**
     * Get users progress status
     * @param {Object} participantInstances
     * @returns {string}
     */
    getProgressStatus(participantInstances) {
      return getYourProgressStatusValue(
        participantInstances,
        this.relationshipId
      );
    },

    /**
     * Checks if participant instances for current role is overdue.
     *
     * @param {Array} participantInstances
     * @return {Boolean}
     */
    isCurrentRoleInstanceOverdue(participantInstances) {
      return isRoleInstanceOverdue(participantInstances, this.relationshipId);
    },

    /**
     * Load the next 'page' of results
     *
     */
    loadMore() {
      this.$apollo.queries.subjectInstances.fetchMore({
        variables: {
          filters: this.filters,
          options: this.options,
          pagination: {
            cursor: this.nextPaginationCursor,
            limit: this.pageLimit,
          },
        },

        updateQuery: (
          previousResult,
          {
            fetchMoreResult: {
              mod_perform_my_subject_instances: fetchMoreResult,
            },
          }
        ) => {
          const prev = previousResult.mod_perform_my_subject_instances.items;
          const newInstances = fetchMoreResult.items;

          return {
            mod_perform_my_subject_instances: {
              items: [...prev, ...newInstances],
              next_cursor: fetchMoreResult.next_cursor,
              total: fetchMoreResult.total,
            },
          };
        },
      });
    },

    $_measure() {
      let width = getOffsetRect(this.$el).width;
      this.gridItemsPerRow = Math.floor(width / 200);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_title_with_subject_creation_date",
      "back_to_user_activities",
      "user_activities_page_title",
      "user_activities_priority_page_heading"
    ],  
    "totara_core": [
      "loadmore"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivitiesPriority {
  &__content {
    padding-bottom: var(--gap-2);

    & > * + * {
      margin-top: var(--gap-6);
    }
  }

  &__heading {
    margin: 0;
    @include tui-font-heading-small();
  }

  &__loadMore {
    text-align: center;
  }
}
</style>
