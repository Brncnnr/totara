<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Murali Nair <murali.nair@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <Layout
    class="tui-competencyCopyPathway"
    :title="
      $str('copy_achievement_paths_title', 'totara_competency', competencyName)
    "
  >
    <template v-slot:content-nav>
      <PageBackLink
        :link="backLinkUrl"
        :text="$str('back_to_competency', 'totara_competency', competencyName)"
      />
    </template>

    <template v-slot:feedback-banner>
      <NotificationBanner
        v-if="noPathwaysWarning"
        :inline-message="true"
        :message="
          $str('no_pathways_for_copying', 'totara_competency', {
            competency: backLinkUrl,
          })
        "
        type="error"
      />

      <NotificationBanner
        v-else-if="hasCriteriaPathway"
        :message="
          $str('criteria_based_content_wont_be_copied', 'totara_competency')
        "
        type="info"
      />
    </template>

    <template v-if="!noPathwaysWarning" v-slot:content>
      <div class="tui-competencyCopyPathway__content">
        <!-- Content heading -->
        <h3 class="tui-competencyCopyPathway__content-heading">
          {{ $str('select_target_competencies', 'totara_competency') }}
        </h3>

        <!-- Basket -->
        <CompetencyBasket
          :loading-confirmation="refetchingSelectedCompetencies"
          :reviewing-selection="reviewingSelection"
          :selected-competencies="selectedCompetencies"
          @apply="confirmCopyPaths"
          @clear-selection="clearSelectedCompetencies"
          @reviewing-selection="switchContentView"
        />

        <div class="tui-competencyCopyPathway__content-body">
          <!-- Competency selection display -->
          <CompetencySelection
            v-if="!reviewingSelection"
            :competencies="competencies"
            :current-page="selectingCurrentPage"
            :filter-values="filterValues"
            :framework-name="frameworkName"
            :loading="$apollo.queries.competencies.loading"
            :page-limit="selectingPageLimit"
            :selected-competencies="selectedCompetencies"
            :source-competency-id="competencyId"
            @change-competency-level="setCompetencyLevel"
            @items-per-page-change="setItemsPerPage"
            @page-change="setPaginationPage"
            @update="setSelectedItems"
          />

          <!-- Reviewing selected competencies display -->
          <ReviewSelection
            v-else
            :competencies="reviewingSelectedCompetencies"
            :last-page="lastReviewingCompetenciesPage"
            :loading="$apollo.queries.reviewingSelectedCompetencies.loading"
            :selected-competencies="selectedCompetencies"
            @next-page="reviewingLoadMore"
            @update="setSelectedItems"
          />
        </div>
      </div>
    </template>

    <template v-slot:modals>
      <!-- Confirm copy achievement path action  -->
      <ConfirmationModal
        :confirm-button-text="confirmationButtonMessaging"
        :loading="copyingPathways"
        :open="confirmCopyModalOpen"
        :title="
          $str('apply_copy_pathways_confirmation_title', 'totara_competency')
        "
        @confirm="copyPaths"
        @cancel="confirmCopyModalOpen = false"
      >
        <div class="tui-competencyCopyPathway__confirmModal">
          <!-- Some selected competencies have existing paths -->
          <template v-if="someSelectedCompetenciesHavePaths">
            <div>
              {{
                $str(
                  'confirm_copy_path_top',
                  'totara_competency',
                  confirmingCompetencyTotal
                )
              }}
            </div>

            <div>
              {{
                $str(
                  'confirm_copy_path_overwrite_some_middle',
                  'totara_competency',
                  confirmingCompetencyTotal - confirmingCompetencyNoPathTotal
                )
              }}
            </div>

            <div>
              {{
                $str('confirm_copy_path_overwrite_bottom', 'totara_competency')
              }}
            </div>
          </template>
          <!-- All selected competencies have existing paths -->
          <template v-else-if="allSelectedCompetenciesHavePaths">
            <div>
              {{
                $str(
                  'confirm_copy_path_top',
                  'totara_competency',
                  confirmingCompetencyTotal
                )
              }}
            </div>

            <div>
              {{
                $str(
                  'confirm_copy_path_overwrite_all_middle',
                  'totara_competency'
                )
              }}
            </div>

            <div>
              {{
                $str('confirm_copy_path_overwrite_bottom', 'totara_competency')
              }}
            </div>
          </template>
          <!-- No selected competencies have existing paths -->
          <template v-else>
            <div>
              {{
                $str(
                  'confirm_copy_path_top',
                  'totara_competency',
                  confirmingCompetencyTotal
                )
              }}
            </div>

            <div>
              {{ $str('confirm_copy_path_continue', 'totara_competency') }}
            </div>
          </template>
        </div>
      </ConfirmationModal>
    </template>
  </Layout>
</template>

<script>
import CompetencyBasket from 'totara_competency/components/competency_copy/CompetencyCopyBasket';
import CompetencySelection from 'totara_competency/components/competency_copy/CompetencySelection';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Layout from 'tui/components/layouts/LayoutOneColumn';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import PageBackLink from 'tui/components/layouts/PageBackLink';
import ReviewSelection from 'totara_competency/components/competency_copy/CompetencyCopyReviewSelection';

// Util
import { notify } from 'tui/notifications';
import { redirectWithPost } from 'tui/dom/form';

// Queries
import CompetenciesQuery from 'totara_hierarchy/graphql/competencies';
import CopyPathwayQuery from 'totara_competency/graphql/copy_pathway';

export default {
  components: {
    CompetencyBasket,
    CompetencySelection,
    ConfirmationModal,
    Layout,
    NotificationBanner,
    PageBackLink,
    ReviewSelection,
  },

  props: {
    backUrl: {
      required: true,
      type: String,
    },
    competencyId: {
      type: Number,
      required: true,
    },
    competencyName: {
      type: String,
      required: true,
    },
    frameworkId: {
      type: Number,
      required: true,
    },
    frameworkName: {
      type: String,
      required: true,
    },
    noPathwaysWarning: {
      type: Boolean,
    },
    hasCriteriaPathway: {
      type: Boolean,
    },
    copiedCount: {
      type: Number,
    },
    needReviewCount: {
      type: Number,
    },
  },

  data() {
    return {
      // Placeholder for competencies query data
      competencies: {},
      // Is the confirm copy modal displayed
      confirmCopyModalOpen: false,
      // Total number of competencies to confirm with no path
      confirmingCompetencyNoPathTotal: 0,
      // Total number of competencies to confirm
      confirmingCompetencyTotal: 0,
      // Making request to copy pathways
      copyingPathways: false,
      // Filter values from filter bar
      filterValues: {
        search: '',
        withoutPaths: false,
      },
      // Filter value to either display a flat list or a hierarchy based list
      noHierarchy: false,
      // Parent competency for changing the displayed level (child competencies)
      parentCompetencyId: null,
      // Check to see if re-requesting selected competencies
      refetchingSelectedCompetencies: false,
      // Current load more page on review display
      reviewingCurrentPage: 1,
      // Number of competencies to display per request on review UI
      reviewingPageLimit: 50,
      // Placeholder for reviewing competencies query data
      reviewingSelectedCompetencies: {
        items: [],
        next_cursor: '',
        total: 0,
      },
      // Currently displaying basket review UI
      reviewingSelection: false,
      // List of currently selected competencies
      selectedCompetencies: [],
      // List of currently selected competencies for review UI
      selectedCompetenciesReview: [],
      // Current pagination page for selecting table
      selectingCurrentPage: 1,
      // items per page limit  for selecting table
      selectingPageLimit: 10,
    };
  },

  computed: {
    /**
     * All selected competencies have existing pathways
     */
    allSelectedCompetenciesHavePaths() {
      return this.confirmingCompetencyNoPathTotal === 0;
    },

    backLinkUrl() {
      return this.$url(this.backUrl, {
        id: this.competencyId,
      });
    },

    /**
     * Button messaging based on if selected competencies have existing paths
     */
    confirmationButtonMessaging() {
      return this.confirmingCompetencyNoPathTotal ===
        this.confirmingCompetencyTotal
        ? this.$str('continue_copying_paths', 'totara_competency')
        : this.$str('replace_all_competency_paths', 'totara_competency');
    },

    /**
     * Some of the selected competencies have existing pathways
     */
    someSelectedCompetenciesHavePaths() {
      return (
        this.confirmingCompetencyNoPathTotal !== 0 &&
        this.confirmingCompetencyNoPathTotal !== this.confirmingCompetencyTotal
      );
    },

    lastReviewingCompetenciesPage() {
      return !this.reviewingSelectedCompetencies.next_cursor.length;
    },
  },

  watch: {
    filterValues: {
      deep: true,
      handler(filters) {
        if (filters.search) {
          this.disableCompetencyHierarchy();

          // If hierarchy filter is not at framework level
          if (this.parentCompetencyId) {
            this.resetCompetencyLevel();
          }
        } else {
          this.enableCompetencyHierarchy();
        }

        this.setPaginationPage(1);
      },
    },
  },

  apollo: {
    competencies: {
      query: CompetenciesQuery,
      skip() {
        return this.reviewingSelection;
      },
      variables() {
        return {
          input: {
            filters: {
              excluded_ids: [],
              framework_id: this.frameworkId,
              ids: [],
              name: this.filterValues.search,
              no_hierarchy: this.noHierarchy,
              no_path: this.filterValues.withoutPaths,
              parent_id: this.parentCompetencyId,
            },
            pagination: {
              limit: this.selectingPageLimit,
              page: this.selectingCurrentPage,
            },
          },
        };
      },
      update({ totara_hierarchy_competencies: data }) {
        return data;
      },
    },

    reviewingSelectedCompetencies: {
      query: CompetenciesQuery,
      skip() {
        return !this.reviewingSelection;
      },
      variables() {
        return {
          input: {
            filters: {
              excluded_ids: [this.competencyId],
              framework_id: this.frameworkId,
              ids: this.selectedCompetenciesReview,
              name: '',
              no_path: false,
            },
            pagination: {
              limit: this.reviewingPageLimit,
              page: 1,
            },
            order_by: 'ACHIEVEMENT_PATH',
          },
        };
      },
      update({ totara_hierarchy_competencies: data }) {
        return data;
      },
    },
  },

  methods: {
    /**
     * Clear selected competencies from the basket
     *
     */
    clearSelectedCompetencies() {
      this.selectedCompetencies = [];
    },

    /**
     * Return a flat list of competencies
     * including competencies from all levels
     *
     */
    disableCompetencyHierarchy() {
      this.noHierarchy = true;
    },

    /**
     * Return a hierarchy list of competencies
     * including competencies from only one level
     *
     */
    enableCompetencyHierarchy() {
      this.noHierarchy = false;
    },

    /**
     * Refetch the selected competencies to check how many have existing achievement pathways
     */
    async confirmCopyPaths() {
      this.refetchingSelectedCompetencies = true;
      const {
        data: { totara_hierarchy_competencies: result },
      } = await this.$apollo.query({
        query: CompetenciesQuery,
        variables: {
          input: {
            filters: {
              excluded_ids: [this.competencyId],
              framework_id: this.frameworkId,
              ids: this.selectedCompetencies,
              name: '',
              no_path: true,
            },
            pagination: {
              limit: 50,
              page: 1,
            },
          },
        },
        fetchPolicy: 'no-cache',
      });

      this.refetchingSelectedCompetencies = false;
      this.confirmingCompetencyNoPathTotal = result.total;
      this.confirmingCompetencyTotal = this.selectedCompetencies.length;
      this.confirmCopyModalOpen = true;
    },

    /**
     * Apply copy achievement path button was clicked
     *
     */
    async copyPaths() {
      try {
        this.copyingPathways = true;

        const { data: resultData } = await this.$apollo.mutate({
          mutation: CopyPathwayQuery,
          variables: {
            input: {
              allowed_competency_frameworks: [this.frameworkId],
              source_competency_id: this.competencyId,
              target_competency_ids: this.selectedCompetencies,
            },
          },
          refetchAll: false, // Don't refetch all the data again
        });

        const result = resultData.totara_competency_copy_pathway;
        this.copiedCount = result.copied_count;
        this.needReviewCount = result.need_review_count;
        if (result.success) {
          this.redirectToSummaryPage();
        } else {
          this.copyingPathways = false;
          if (result.error && result.error.message) {
            notify({
              message: result.error.message,
              type: 'error',
            });
          } else {
            this.showErrorNotification();
          }
        }
      } catch (e) {
        this.copyingPathways = false;
        this.showErrorNotification();
      }
    },

    /**
     * Redirect to the competency summary page
     *
     */
    redirectToSummaryPage() {
      redirectWithPost(this.backUrl, {
        copied_count: this.copiedCount,
        need_review_count: this.needReviewCount,
        id: this.competencyId,
      });
    },

    /**
     * Reset the competency level to framework
     *
     */
    resetCompetencyLevel() {
      this.parentCompetencyId = null;
    },

    /**
     * Reset the filter bar values
     *
     */
    resetFilters() {
      this.filterValues = {
        search: '',
        withoutPaths: false,
      };
    },

    /**
     * Reset the search filter value
     *
     */
    resetSearchFilter() {
      this.filterValues.search = '';
    },

    /**
     * Reset the reviewing selected competencies data
     *
     */
    resetReviewingSelectedCompetencies() {
      this.reviewingSelectedCompetencies = {
        items: [],
        next_cursor: '',
        total: 0,
      };
    },

    /**
     * Load more competencies on the reviewing list
     *
     */
    reviewingLoadMore() {
      // Increase page number
      this.setLoadMorePage(this.reviewingCurrentPage + 1);

      // Fetch additional data
      this.$apollo.queries.reviewingSelectedCompetencies.fetchMore({
        variables: {
          input: {
            filters: {
              excluded_ids: [this.competencyId],
              framework_id: this.frameworkId,
              ids: this.selectedCompetenciesReview,
              name: '',
              no_path: false,
            },
            pagination: {
              limit: this.reviewingPageLimit,
              page: this.reviewingCurrentPage,
            },
            order_by: 'ACHIEVEMENT_PATH',
          },
        },
        updateQuery: (previousResult, { fetchMoreResult }) => {
          fetchMoreResult.totara_hierarchy_competencies.items.unshift(
            ...previousResult.totara_hierarchy_competencies.items
          );

          return fetchMoreResult;
        },
      });
    },

    /**
     * Set the level to a particular competency based on it's ID
     *
     * @param {Number} competencyId
     */
    setCompetencyLevel(competencyId) {
      this.parentCompetencyId = competencyId;
      this.resetSearchFilter();
    },

    /**
     * Update number of items displayed in paginated selection results
     *
     * @param {Number} limit
     */
    setItemsPerPage(limit) {
      this.setPaginationPage(1);
      this.selectingPageLimit = limit;
    },

    /**
     * Set the next page for the reviewing load more button
     *
     * @param {Number} page
     */
    setLoadMorePage(page) {
      this.reviewingCurrentPage = page;
    },

    /**
     * Update current paginated page of selection results
     *
     * @param {Number} page
     */
    setPaginationPage(page) {
      this.selectingCurrentPage = page;
    },

    /**
     * Set selected competencies (chosen from the table)
     *
     * @param {Array} competencies
     */
    setSelectedItems(competencies) {
      this.selectedCompetencies = competencies;
    },

    /**
     * Show generic save/update error toast.
     */
    showErrorNotification() {
      notify({
        message: this.$str('toast_error_generic_update', 'totara_competency'),
        type: 'error',
      });
    },

    /**
     * Update the view (either selecting competencies or reviewing selection)
     *
     * @param {Boolean} reviewing
     */
    switchContentView(reviewing) {
      this.reviewingSelection = reviewing;

      if (reviewing) {
        this.selectedCompetenciesReview = this.selectedCompetencies;
        this.setLoadMorePage(1);
        this.resetFilters();
        this.setPaginationPage(1);
        this.resetCompetencyLevel();
      } else {
        this.resetReviewingSelectedCompetencies();
      }
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "apply_copy_pathways",
      "apply_copy_pathways_confirmation_title",
      "back_to_competency",
      "confirm_copy_path_continue",
      "confirm_copy_path_overwrite_all_middle",
      "confirm_copy_path_overwrite_bottom",
      "confirm_copy_path_overwrite_some_middle",
      "confirm_copy_path_top",
      "continue_copying_paths",
      "copy_achievement_paths_title",
      "criteria_based_content_wont_be_copied",
      "no_pathways_for_copying",
      "replace_all_competency_paths",
      "select_target_competencies",
      "toast_error_generic_update"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyCopyPathway {
  &__content {
    & > * + * {
      margin-top: var(--gap-4);
    }

    &-body {
      margin-top: var(--gap-8);
    }

    &-heading {
      margin: 0;
      @include tui-font-heading-small();
    }
  }

  &__confirmModal {
    & > * + * {
      margin-top: var(--gap-4);
    }
  }
}
</style>
