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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performUserActivityList">
    <!-- Priority list of activities -->
    <div
      v-if="totalPrioritisedActivities"
      class="tui-performUserActivityList__priority"
    >
      <h3 class="tui-performUserActivityList__priority-heading">
        {{
          $str(
            'user_activities_priority_heading',
            'mod_perform',
            totalPrioritisedActivities
          )
        }}
      </h3>

      <OverflowContainer
        :items="prioritisedInstances"
        :total="totalPrioritisedActivities"
        :view-all-link="$url(priorityUrl, { relationship_id: aboutRole })"
      >
        <template v-slot:default="{ item }">
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
        </template>
      </OverflowContainer>
    </div>

    <div class="tui-performUserActivityList__content">
      <h3 class="tui-performUserActivityList__heading">
        {{
          $str('user_activities_header', 'mod_perform', subjectInstances.length)
        }}
      </h3>

      <ActivitiesFilter
        v-model="userFilters"
        :about-others="isAboutOthers"
        :filter-options="filterOptions"
        :has-completed="hasCompleted"
        :has-overdue="hasOverdue"
        @filter-change="filterChange"
      />

      <ActivitiesCount
        v-model="sortByFilter"
        :about-others="isAboutOthers"
        :displayed-count="subjectInstances.length"
        :loading="$apollo.queries.subjectInstances.loading"
        :sort-by-options="sortByOptions"
        :total="totalActivities"
      />

      <Loader :loading="$apollo.queries.subjectInstances.loading">
        <!-- Table -->
        <Table
          ref="activity-table"
          :data="subjectInstances"
          :hover-off="true"
          :loading-preview="$apollo.queries.subjectInstances.loading"
          :no-items-text="emptyListText"
        >
          <!-- Row -->
          <template v-slot:row="{ row }">
            <Cell size="12">
              <template v-slot:custom-loader>
                <ActivitiesTableItem
                  :about-role="aboutRole"
                  :is-about-others="isAboutOthers"
                  :loading="true"
                  print-url=""
                  :row="{}"
                  view-url=""
                />
              </template>

              <template v-slot:default>
                <ActivitiesTableItem
                  :about-role="aboutRole"
                  :active-filter-count="activeFilterCount"
                  :is-about-others="isAboutOthers"
                  :print-url="printUrl"
                  :row="row"
                  :view-url="viewUrl"
                  @show-sections-details="triggerActivitySectionsModal"
                />
              </template>
            </Cell>
          </template>
        </Table>

        <div class="tui-performUserActivityList__paging">
          <Paging
            v-if="totalActivities"
            :items-per-page="paginationLimit"
            :page="paginationPage"
            :total-items="totalActivities"
            @count-change="setItemsPerPage"
            @page-change="setPaginationPage"
          />
        </div>
      </Loader>
    </div>

    <!-- Details modal -->
    <SectionsModal
      :about-role="aboutRole"
      :activity="sectionsModalActivity"
      :open="showActivitySectionsModal"
      :view-url="viewUrl"
      @request-close="showActivitySectionsModal = false"
    />
  </div>
</template>
<script>
import ActivitiesCount from 'mod_perform/components/user_activities/list/ActivitiesCount';
import ActivitiesFilter from 'mod_perform/components/user_activities/list/ActivitiesFilter';
import ActivitiesPriorityCard from 'mod_perform/components/user_activities/list/ActivitiesPriorityCard';
import ActivitiesTableItem from 'mod_perform/components/user_activities/list/ActivitiesTableItem';
import Cell from 'tui/components/datatable/Cell';
import Loader from 'tui/components/loading/Loader';
import OverflowContainer from 'tui/components/overflow_container/OverflowContainer';
import Paging from 'tui/components/paging/Paging';
import SectionsModal from 'mod_perform/components/user_activities/list/SectionsModal';
import Table from 'tui/components/datatable/Table';

import {
  getFirstSectionToParticipate,
  getYourProgressStatusValue,
  isRoleInstanceClosed,
  isRoleInstanceOverdue,
} from 'mod_perform/activities_util';
// Query
import subjectInstancesQuery from 'mod_perform/graphql/my_subject_instances';
import {
  PARTICIPANT_INSTANCE_PROGRESS_STATUS_IN_PROGRESS,
  PARTICIPANT_INSTANCE_PROGRESS_STATUS_NOT_STARTED,
} from 'mod_perform/constants';

export default {
  components: {
    ActivitiesCount,
    ActivitiesFilter,
    ActivitiesPriorityCard,
    ActivitiesTableItem,
    Cell,
    Loader,
    OverflowContainer,
    Paging,
    SectionsModal,
    Table,
  },

  props: {
    aboutRole: {
      required: true,
      type: Number,
    },
    filterOptions: Object,
    // Is the tab about others activities or the users own
    isAboutOthers: Boolean,
    printUrl: {
      required: true,
      type: String,
    },
    priorityUrl: {
      required: true,
      type: String,
    },
    // An Array of sort options
    sortByOptions: Array,
    tabFilters: Object,
    viewUrl: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      activeFilterCount: 0,
      // items per page limit
      paginationLimit: 20,
      // Current pagination page
      paginationPage: 1,
      // Prioritised subject instances
      prioritisedInstances: [],
      sectionsModalActivity: {},
      // Show the sections modal for an activity
      showActivitySectionsModal: false,
      singleSectionViewOnlyActivities: [],
      sortByFilter: 'created_at',
      subjectInstances: [],
      // Count of activities across all pages
      totalActivities: 0,
      // Count of prioritised activities
      totalPrioritisedActivities: 0,
      userFilters: {
        activityType: null,
        excludeCompleted: false,
        ownProgress: null,
        overdueOnly: false,
        search: null,
      },
      hasCompleted: false,
      hasOverdue: false,
    };
  },

  computed: {
    /**
     * Active filter options (reactive value for the query)
     *
     * @return {Object}
     */
    currentFilterOptions() {
      return {
        about_role: this.aboutRole,
        activity_type: this.userFilters.activityType,
        exclude_complete: this.userFilters.excludeCompleted || false,
        overdue: this.userFilters.overdueOnly || false,
        participant_progress: this.userFilters.ownProgress,
        search_term: this.userFilters.search,
      };
    },

    /**
     * Active filter options with sort order (used for URL params)
     *
     * @return {Object}
     */
    currentFilterWithSortOptions() {
      return Object.assign({}, this.currentFilterOptions, {
        sort_by_filter: this.sortByFilter,
      });
    },

    /**
     * Return empty list string, either it is empty due to the active filters
     * Or you haven't been assigned any activities yet.
     *
     * @return {Boolean}
     */
    emptyListText() {
      return this.activeFilterCount
        ? this.$str('user_activities_list_none_filtered', 'mod_perform')
        : this.$str('user_activities_list_none_about_self', 'mod_perform');
    },
  },

  watch: {
    /**
     * Check for any filter or sort order changes
     *
     * @param {Object} filters
     */
    currentFilterWithSortOptions(filters) {
      this.$emit('update-url-params', filters);
    },

    /**
     * Called when browser back/forward button has been clicked
     * changing the filters but not the tab
     *
     * @param {Object} filters
     */
    tabFilters(filters) {
      this.setPageFilters(filters);
    },
  },

  mounted() {
    if (this.tabFilters) {
      this.setPageFilters(this.tabFilters);
    } else {
      this.$emit('update-url-params', this.currentFilterWithSortOptions);
    }
  },

  apollo: {
    // Query for prioritised subject instances
    prioritisedInstances: {
      query: subjectInstancesQuery,
      variables() {
        return {
          filters: {
            about_role: this.aboutRole,
            activity_type: null,
            exclude_complete: true,
            overdue: false,
            participant_progress: [
              PARTICIPANT_INSTANCE_PROGRESS_STATUS_NOT_STARTED,
              PARTICIPANT_INSTANCE_PROGRESS_STATUS_IN_PROGRESS,
            ],
            search_term: null,
          },
          options: {
            sort_by: 'due_date',
          },
          pagination: {
            limit: 8,
            page: 1,
          },
        };
      },
      update: data => data['mod_perform_my_subject_instances'].items,
      result({ data: { mod_perform_my_subject_instances: data } }) {
        this.totalPrioritisedActivities = data.total;
      },
    },

    subjectInstances: {
      query: subjectInstancesQuery,
      variables() {
        return {
          filters: this.currentFilterOptions,
          options: {
            sort_by: this.sortByFilter,
          },
          pagination: {
            limit: this.paginationLimit,
            page: this.paginationPage,
          },
        };
      },
      update: data => data['mod_perform_my_subject_instances'].items,
      result({ data: { mod_perform_my_subject_instances: data } }) {
        this.totalActivities = data.total;
        this.hasCompleted = data.completed_count > 0;
        this.hasOverdue = data.overdue_count > 0;

        // Prevent the case where there are no overdue activities but the filter is set to show overdue only
        //  (which would therefore mean the user can't see the filter to disable it)
        if (!this.hasOverdue && this.userFilters.overdueOnly) {
          this.userFilters.overdueOnly = false;
        }
      },
    },
  },

  methods: {
    /**
     * Get "view" url for a specific user activity.
     *
     * @param subjectInstance {{Object}}
     * @returns {string}
     */
    getViewActivityUrl(subjectInstance) {
      const participantSection = getFirstSectionToParticipate(
        subjectInstance.sections,
        this.aboutRole
      );
      if (participantSection) {
        return this.$url(this.viewUrl, {
          participant_section_id: participantSection.id,
        });
      }
      return '';
    },

    /**
     * Get text to describe the subject instance's job assignment.
     *
     * @param {Object|NULL} jobAssignment
     * @return {string|null}
     */
    getJobAssignmentDescription(jobAssignment) {
      if (!jobAssignment) {
        return;
      }
      let fullname = jobAssignment.fullname;

      if (fullname) {
        fullname = fullname.trim();
      }

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
     * Get users progress status value
     * @param {Object} participantInstances
     * @returns {string}
     */
    getProgressStatus(participantInstances) {
      return getYourProgressStatusValue(participantInstances, this.aboutRole);
    },

    /**
     * Returns the activity title generated from the subject instance passed it.
     *
     * @param {Object} subject
     * @returns {string}
     */
    getActivityTitle(subject) {
      var title = subject.activity.name.trim();
      var suffix = subject.created_at ? subject.created_at.trim() : '';

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
     * Update active filter count and reset page
     *
     * @param {Number} activeFilterCount
     */
    filterChange(activeFilterCount) {
      this.activeFilterCount = activeFilterCount;
      this.paginationPage = 1;
    },

    /**
     * Update number of items displayed per page
     *
     * @param {Number} limit
     */
    setItemsPerPage(limit) {
      if (this.$refs['activity-table']) {
        this.$refs['activity-table'].$el.scrollIntoView();
      }

      this.paginationPage = 1;
      this.paginationLimit = limit;
    },

    /**
     * Update current page filters to match URL variables
     *
     * @param {Object} values
     */
    setPageFilters(values) {
      // Reset filters
      if (!values) {
        this.sortByFilter = 'created_at';
        this.userFilters = {
          activityType: null,
          excludeCompleted: false,
          ownProgress: null,
          overdueOnly: false,
          search: null,
        };
        return;
      }

      if (!values.search_term) {
        values.search_term = null;
      }

      this.sortByFilter = values.sort_by_filter
        ? values.sort_by_filter
        : 'created_at';

      Object.keys(values).forEach(key => {
        const value = values[key];
        switch (key) {
          case 'activity_type':
            this.userFilters.activityType = value;
            break;
          case 'exclude_complete':
            this.userFilters.excludeCompleted = value;
            break;
          case 'participant_progress':
            this.userFilters.ownProgress = value;
            break;
          case 'overdue': {
            this.userFilters.overdueOnly = value;
            break;
          }
          case 'search_term':
            this.userFilters.search = value;
            break;
        }
      });
    },

    /**
     * Update current paginated page
     *
     * @param {Number} page
     */
    setPaginationPage(page) {
      if (this.$refs['activity-table']) {
        this.$refs['activity-table'].$el.scrollIntoView();
      }

      this.paginationPage = page;
    },

    /**
     * Display the activity sections modal
     *
     * @param {Object} activity
     */
    triggerActivitySectionsModal(activity) {
      this.sectionsModalActivity = activity;
      this.showActivitySectionsModal = true;
    },

    /* Deprecated methods */

    /**
     * Add to the list of activities that only have one section where current user has view-only access.
     *
     * @param {Number} activityId
     * @deprecated since 17.0
     */
    flagActivitySingleSectionViewOnly(activityId) {
      this.singleSectionViewOnlyActivities.push(activityId);
    },

    /**
     * The label to show for the expand row button.
     *
     * @param {Object} subjectInstance
     * @return {string}
     * @deprecated since 17.0
     */
    getExpandLabel(subjectInstance) {
      if (!subjectInstance.subject) {
        return;
      }

      const activityTitle = this.getActivityTitle(subjectInstance.subject);
      if (!this.isAboutOthers) {
        return activityTitle;
      }
      return this.$str('activity_title_for_subject', 'mod_perform', {
        activity: activityTitle,
        user: subjectInstance.subject.subject_user.fullname,
      });
    },

    /**
     * Get the localized status text for a particular participant .
     *
     * @param status {String}
     * @returns {string}
     * @deprecated since 17.0
     */
    getParticipantStatusText(status) {
      switch (status) {
        case 'NOT_STARTED':
          return this.$str(
            'participant_instance_status_not_started',
            'mod_perform'
          );
        case 'IN_PROGRESS':
          return this.$str(
            'participant_instance_status_in_progress',
            'mod_perform'
          );
        case 'COMPLETE':
          return this.$str(
            'participant_instance_status_complete',
            'mod_perform'
          );
        case 'PROGRESS_NOT_APPLICABLE':
          return this.$str(
            'participant_instance_status_progress_not_applicable',
            'mod_perform'
          );
        case 'NOT_SUBMITTED':
          return this.$str(
            'participant_instance_status_not_submitted',
            'mod_perform'
          );
        default:
          return '';
      }
    },

    /**
     * Get print-friendly page URL for activity.
     *
     * @param subjectInstance
     * @deprecated since 17.0
     */
    getPrintActivityLink(subjectInstance) {
      const participantSection = getFirstSectionToParticipate(
        subjectInstance.sections,
        this.aboutRole
      );

      if (participantSection) {
        return this.$url(this.printUrl, {
          participant_section_id: participantSection.id,
        });
      }
      return '';
    },

    /**
     * Get the localized status text for a particular user activity.
     *
     * @param status {String}
     * @returns {string}
     * @deprecated since 17.0
     */
    getStatusText(status) {
      switch (status) {
        case 'NOT_STARTED':
          return this.$str('user_activities_status_not_started', 'mod_perform');
        case 'IN_PROGRESS':
          return this.$str('user_activities_status_in_progress', 'mod_perform');
        case 'COMPLETE':
          return this.$str('user_activities_status_complete', 'mod_perform');
        case 'PROGRESS_NOT_APPLICABLE':
          return this.$str(
            'user_activities_status_not_applicable',
            'mod_perform'
          );
        case 'NOT_SUBMITTED':
          return this.$str(
            'user_activities_status_not_submitted',
            'mod_perform'
          );
        default:
          return '';
      }
    },

    /**
     * Get the string value for current users progress on a particular subject instance.
     *
     * @param {Object[]} participantInstances - The participant instances from the subject instance we are getting the progress text for
     * @returns {string}
     * @deprecated since 17.0
     */
    getYourProgressText(participantInstances) {
      return this.getParticipantStatusText(
        this.getProgressStatus(participantInstances)
      );
    },

    /**
     * Checks if participant instance for current role is closed.
     *
     * @param {Array} participantInstances
     * @return {Boolean}
     * @deprecated since 17.0
     */
    isCurrentRoleInstanceClosed(participantInstances) {
      return isRoleInstanceClosed(participantInstances, this.aboutRole);
    },

    /**
     * Checks if participant instance for current role is overdue.
     *
     * @param {Array} participantInstances
     * @return {Boolean}
     * @deprecated since 17.0
     */
    isCurrentRoleInstanceOverdue(participantInstances) {
      return isRoleInstanceOverdue(participantInstances, this.aboutRole);
    },

    /**
     * Find out if an activity has only one section where current user has view-only access.
     *
     * @param activityId
     * @returns {boolean}
     * @deprecated since 17.0
     */
    isSingleSectionViewOnly(activityId) {
      return this.singleSectionViewOnlyActivities.includes(activityId);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_title_for_subject",
      "activity_title_with_subject_creation_date",
      "participant_instance_status_complete",
      "participant_instance_status_in_progress",
      "participant_instance_status_not_started",
      "participant_instance_status_not_submitted",
      "participant_instance_status_progress_not_applicable",
      "unnamed_job_assignment",
      "user_activities_header",
      "user_activities_list_none_about_self",
      "user_activities_list_none_filtered",
      "user_activities_priority_heading",
      "user_activities_status_complete",
      "user_activities_status_in_progress",
      "user_activities_status_not_applicable",
      "user_activities_status_not_started",
      "user_activities_status_not_submitted"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivityList {
  margin-top: var(--gap-2);

  & > * + * {
    margin-top: var(--gap-8);
  }

  &__priority {
    & > * + * {
      margin-top: var(--gap-4);
    }

    &-heading {
      margin: 0;
      @include tui-font-heading-small();
    }
  }

  &__content {
    min-height: 500px;
    & > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__heading {
    margin: 0;
    @include tui-font-heading-small();
  }

  &__paging {
    margin-top: var(--gap-5);
  }
}
</style>
