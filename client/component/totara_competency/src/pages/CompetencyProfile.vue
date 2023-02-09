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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencyProfile">
    <Loader :loading="$apollo.loading">
      <NotificationBanner
        v-if="hasPendingAggregation"
        type="warning"
        :message="
          $str('warning_pending_aggregation_profile', 'totara_competency')
        "
      />

      <div class="tui-competencyProfile__header">
        <MiniProfileCard v-if="user && !isMine" :display="user.card_display" />
        <PageHeading :title="$str('competency_profile', 'totara_competency')" />
      </div>

      <NoCompetencyAssignments
        v-if="noAssignments"
        :is-mine="isMine"
        :is-assign-enabled="canAssign"
        :self-assignment-url="selfAssignmentUrl"
      />
      <div v-else>
        <div class="tui-competencyProfile__currentHeading">
          <h3 class="tui-competencyProfile__currentHeading-title">
            {{ $str('current_assignment_progress', 'totara_competency') }}
          </h3>
          <div
            v-if="!noAssignments"
            class="tui-competencyProfile__currentHeading-buttons"
          >
            <ActionLink
              v-if="rateCompetenciesUrl"
              :href="rateCompetenciesUrl"
              :text="$str('rate_competencies', 'pathway_manual')"
            />
            <ActionLink
              v-if="canAssign"
              :href="selfAssignmentUrl"
              :text="
                $str(
                  isMine ? 'self_assign_competencies' : 'assign_competencies',
                  'totara_competency'
                )
              "
            />
          </div>
        </div>

        <CurrentProgress
          :data="currentProgressData"
          :is-current-user="isMine"
        />

        <Responsive
          v-slot="{ currentBoundaryName }"
          class="tui-competencyProfile__competencies"
          :breakpoints="[
            { name: 'small', boundaries: [0, 700] },
            { name: null, boundaries: [701, 701] },
          ]"
        >
          <div class="tui-competencyProfile__competencies-content">
            <h3 class="tui-competencyProfile__sectionTitle">
              {{ $str('header_competencies', 'totara_competency') }}
            </h3>
            <div class="tui-competencyProfile__filtersBar">
              <ProgressAssignmentFilters
                v-model="selectedFilters"
                :filters="filterOptions"
              />
              <div
                v-if="currentBoundaryName != 'small'"
                class="tui-competencyProfile__tabs"
              >
                <ToggleSet
                  v-model="activeTab"
                  :aria-label="
                    $str('toggle_competency_view_format', 'totara_competency')
                  "
                >
                  <ToggleButton
                    value="charts"
                    :aria-label="$str('toggle_charts', 'totara_competency')"
                  >
                    <BarChartIcon />
                  </ToggleButton>
                  <ToggleButton
                    value="table"
                    :aria-label="$str('toggle_table', 'totara_competency')"
                  >
                    <ListIcon />
                  </ToggleButton>
                </ToggleSet>
              </div>
            </div>
            <div
              v-if="currentBoundaryName != 'small' && activeTab === 'charts'"
            >
              <CompetencyCharts
                :data="data"
                :user-id="userId"
                :is-current-user="isMine"
              />
            </div>
            <div v-if="currentBoundaryName == 'small' || activeTab === 'table'">
              <CompetencyList
                :filters="selectedFilters"
                :user-id="userId"
                :is-mine="isMine"
                :base-url="baseUrl"
              />
            </div>
          </div>
        </Responsive>
      </div>
    </Loader>
  </div>
</template>
<script>
import ActionLink from 'tui/components/links/ActionLink';
import BarChartIcon from 'tui/components/icons/BarChart';
import CompetencyCharts from 'totara_competency/components/profile/CompetencyCharts';
import CompetencyList from 'totara_competency/components/profile/competency_list/List';
import CurrentProgress from 'totara_competency/components/profile/CurrentProgress';
import ListIcon from 'tui/components/icons/List';
import Loader from 'tui/components/loading/Loader';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import NoCompetencyAssignments from 'totara_competency/components/profile/NoCompetencyAssignments';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import PageHeading from 'tui/components/layouts/PageHeading';
import ProgressAssignmentFilters from 'totara_competency/components/ProgressAssignmentFilters';
import Responsive from 'tui/components/responsive/Responsive';
import ToggleButton from 'tui/components/toggle/ToggleButton';
import ToggleSet from 'tui/components/toggle/ToggleSet';
import { notify } from 'tui/notifications';
import { pick, groupBy } from 'tui/util';
// Query
import ProgressQuery from 'totara_competency/graphql/progress_for_user';
import UserQuery from 'totara_competency/graphql/user';

const ACTIVE_ASSIGNMENT = 1;

export default {
  components: {
    ActionLink,
    BarChartIcon,
    CompetencyCharts,
    CompetencyList,
    CurrentProgress,
    ListIcon,
    Loader,
    MiniProfileCard,
    NoCompetencyAssignments,
    NotificationBanner,
    PageHeading,
    ProgressAssignmentFilters,
    Responsive,
    ToggleButton,
    ToggleSet,
  },

  props: {
    selfAssignmentUrl: {
      required: true,
      type: String,
    },
    userId: {
      required: true,
      type: Number,
    },
    baseUrl: {
      required: true,
      type: String,
    },
    isMine: {
      required: true,
      type: Boolean,
    },
    canRateCompetencies: {
      required: true,
      type: Boolean,
    },
    toastMessage: {
      type: String,
    },
    hasPendingAggregation: {
      type: Boolean,
    },
    canAssign: Boolean,
  },

  data() {
    return {
      data: {},
      user: null,
      activeTab: 'charts',
      selectedFilters: this.$_addFilterKey({
        status: ACTIVE_ASSIGNMENT,
        type: null,
        user_group_id: null,
        user_group_type: null,
      }),
      currentProgressData: [],
    };
  },

  apollo: {
    data: {
      query: ProgressQuery,
      variables() {
        return {
          user_id: this.userId,
          filters: pick(this.selectedFilters, [
            'status',
            'type',
            'user_group_id',
            'user_group_type',
          ]),
        };
      },
      update: data => data.totara_competency_profile_progress,
      result({ data: { totara_competency_profile_progress: data } }) {
        // show current progress only when there is active assignment
        if (
          this.selectedFilters.status == ACTIVE_ASSIGNMENT &&
          !this.currentProgressData.length
        ) {
          this.currentProgressData = data.items.slice(0);
        }

        // ensure the filter we requested with is actually allowed
        if (data.filters && data.filters.length) {
          const filter = this.selectedFilters;
          const isStatusFilter =
            filter.type === null &&
            filter.user_group_id === null &&
            filter.user_group_type === null;

          const filterPresent = isStatusFilter
            ? data.filters.some(x => x.status == this.selectedFilters.status)
            : data.filters.some(
                x => this.$_filterKey(x) == this.selectedFilters.key
              );

          if (!filterPresent) {
            // we requested with an invalid filter, try again with a valid one
            this.selectedFilters = this.$_addFilterKey(
              isStatusFilter
                ? {
                    status: data.filters[0].status,
                    type: null,
                    user_group_id: null,
                    user_group_type: null,
                  }
                : data.filters[0]
            );
          }
        }
      },
    },
    user: {
      query: UserQuery,
      variables() {
        return { user_id: this.userId };
      },
      update: data => data.totara_competency_user,
      skip() {
        return this.isMine;
      },
    },
  },

  computed: {
    /**
     * Get options for the filter.
     *
     * @returns {Array<{id: String, name: String, value: Object, filters: Array<{id: String, name: String, value: Object}>}>}
     */
    filterOptions() {
      if (!this.data || !this.data.filters) {
        return [];
      }

      return Object.entries(
        groupBy(this.data.filters, filter => filter.status)
        // eslint-disable-next-line no-unused-vars
      ).map(([key, filters]) => {
        const groupValue = this.$_addFilterKey({
          status: filters[0].status,
          type: null,
          user_group_id: null,
          user_group_type: null,
        });
        return {
          id: groupValue.key,
          name: filters[0].status_name,
          value: groupValue,
          filters: filters.map(filter => {
            const value = this.$_addFilterKey(this.$_filterValue(filter));
            return {
              id: value.key,
              name: filter.name,
              value,
            };
          }),
        };
      });
    },

    /**
     * True if we're not loading and there are no filter options.
     */
    noAssignments() {
      return !this.$apollo.loading && !this.filterOptions.length;
    },

    rateCompetenciesUrl() {
      if (!this.canRateCompetencies) {
        return null;
      }

      return this.$url('/totara/competency/rate_competencies.php', {
        user_id: this.userId,
      });
    },
  },

  mounted() {
    if (this.toastMessage) {
      notify({ message: this.toastMessage });
    }
  },

  methods: {
    /**
     * Filter a filter value down to the properties that the query needs
     *
     * @param {object} obj
     * @returns {object}
     */
    $_filterValue(obj) {
      return pick(obj, ['status', 'type', 'user_group_id', 'user_group_type']);
    },

    /**
     * Add the filter key to a copy of the specified object and return it
     *
     * @param {object} obj
     * @returns {object}
     */
    $_addFilterKey(obj) {
      return Object.assign({ key: this.$_filterKey(obj) }, obj);
    },

    /**
     * Generate a key for a particular filter
     *
     * @param {object} obj
     * @returns {object}
     */
    $_filterKey(obj) {
      return JSON.stringify([
        obj.status,
        obj.type,
        obj.user_group_id,
        obj.user_group_type,
      ]);
    },
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "assign_competencies",
    "competency_profile",
    "current_assignment_progress",
    "header_competencies",
    "self_assign_competencies",
    "toggle_charts",
    "toggle_competency_view_format",
    "toggle_table",
    "warning_pending_aggregation_profile"
  ],
  "pathway_manual": [
    "rate_competencies"
  ]
}
</lang-strings>

<style lang="scss">
.tui-competencyProfile {
  // disable scroll anchoring as it is problematic when switching between chart/list views
  overflow-anchor: none;

  & > * + * {
    margin-top: var(--gap-8);
  }

  &__header {
    & > * + * {
      margin-top: var(--gap-2);
    }
  }

  &__currentHeading {
    display: flex;
    flex-direction: column;

    &-title {
      margin: 0;
      @include tui-font-heading-small();
    }

    &-buttons {
      & > * {
        margin-top: var(--gap-4);
        margin-left: var(--gap-2);
      }
    }
  }

  &__competencies {
    margin-top: var(--gap-6);
    padding-top: var(--gap-6);
    border-top: var(--border-width-thin) solid var(--color-neutral-5);

    &-content {
      & > * + * {
        margin-top: var(--gap-8);
      }
    }
  }

  &__sectionTitle {
    margin: 0;
    @include tui-font-heading-small();
  }

  &__filtersBar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-competencyProfile {
    &__currentHeading {
      flex-direction: row;
      &-buttons {
        margin-left: auto;

        & > * {
          margin-top: 0;
        }
      }
    }
  }
}
</style>
