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
  @module pathway_manual
-->

<template>
  <div class="tui-bulkManualRatingRateUserCompetencies">
    <div class="tui-bulkManualRatingRateUserCompetencies__filters">
      <UserCompetenciesFilters
        :filter-options="filterOptions"
        :has-ratings="hasSelectedRatings"
        :is-rating-single-competency="isRatingSingleCompetency"
        @update-filters="applyFilters"
        @go-back="$emit('go-back')"
      />
    </div>
    <Loader :loading="$apollo.loading">
      <div
        v-if="!$apollo.loading && competencies.framework_groups.length > 0"
        class="tui-bulkManualRatingRateUserCompetencies__frameworks"
      >
        <FrameworkGroup
          v-for="group in competencies.framework_groups"
          :key="group.framework.id"
          class="tui-bulkManualRatingRateUserCompetencies__frameworkGroup"
          :group="group"
          :role="role"
          :current-user-id="currentUserId"
          :selected-ratings="selectedRatings"
          :is-expanded="expandFrameworkGroups"
          @update-rating="updateRating"
          @delete-rating="deleteRating"
        />
        <div class="tui-bulkManualRatingRateUserCompetencies__submitButtons">
          <ButtonGroup>
            <Button
              :styleclass="{ primary: 'true' }"
              :text="$str('submit', 'core')"
              :disabled="!hasSelectedRatings"
              type="submit"
              @click="showSubmitRatingsModal = true"
            />
            <Button :text="$str('cancel', 'core')" @click="$emit('go-back')" />
          </ButtonGroup>
          <ConfirmationModal
            :open="showSubmitRatingsModal"
            :title="
              $str('modal_submit_ratings_confirmation_title', 'pathway_manual')
            "
            :loading="isSaving"
            @confirm="submitRatings"
            @cancel="showSubmitRatingsModal = false"
          >
            <p>{{ submitRatingsModalMessage }}</p>
            <p>
              {{
                $str(
                  'modal_submit_ratings_confirmation_question',
                  'pathway_manual'
                )
              }}
            </p>
          </ConfirmationModal>
        </div>
      </div>
      <div
        v-else-if="!$apollo.loading"
        class="tui-bulkManualRatingRateUserCompetencies__noCompetencies"
      >
        {{
          hasSelectedFilters
            ? $str('filter_no_competencies', 'pathway_manual')
            : $str('no_rateable_competencies', 'pathway_manual')
        }}
      </div>
    </Loader>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import CreateManualRatingsMutation from 'pathway_manual/graphql/create_manual_ratings';
import FrameworkGroup from 'pathway_manual/components/FrameworkGroup';
import Loader from 'tui/components/loading/Loader';
import RateableCompetenciesQuery from 'pathway_manual/graphql/user_rateable_competencies';
import UserCompetenciesFilters from 'pathway_manual/components/UserCompetenciesFilters';
import { NONE_OPTION_VALUE, ROLE_SELF } from 'pathway_manual/constants';
import { notify } from 'tui/notifications';

/**
 * If there are more than this amount of competencies available to rate,
 * then the framework groups should be collapsed by default for performance reasons.
 *
 * @type {number}
 */
const MAX_COMPETENCIES_TO_DISPLAY = 200;

export default {
  components: {
    Button,
    ButtonGroup,
    ConfirmationModal,
    FrameworkGroup,
    Loader,
    UserCompetenciesFilters,
  },

  props: {
    user: {
      required: true,
      type: Object,
    },
    role: {
      required: true,
      type: String,
    },
    currentUserId: {
      required: true,
      type: Number,
    },
    assignmentId: {
      type: Number,
    },
  },

  data() {
    let data = {
      competencies: {},
      selectedFilters: {},
      filterOptions: {},
      showSubmitRatingsModal: false,
      selectedRatings: [],
      isRatingSingleCompetency: false,
      isSaving: false,
    };

    if (this.assignmentId != null) {
      data.selectedFilters = {
        assignment_reason: [this.assignmentId],
      };
      data.isRatingSingleCompetency = true;
    }

    return data;
  },

  computed: {
    /**
     * Should the framework groups of competencies be expanded?
     * We don't expand them if there are too many competencies.
     * @returns {boolean}
     */
    expandFrameworkGroups() {
      return this.competencies.count < MAX_COMPETENCIES_TO_DISPLAY;
    },

    /**
     * Are there any draft ratings made that haven't been saved?
     * @returns {boolean}
     */
    hasSelectedRatings() {
      return this.selectedRatings.length > 0;
    },

    /**
     * Are there any filters that have been selected but not applied yet?
     * @returns {boolean}
     */
    hasSelectedFilters() {
      return Object.keys(this.selectedFilters).length > 0;
    },

    /**
     * Message to show to confirm saving ratings.
     * @returns {String}
     */
    submitRatingsModalMessage() {
      let ratingSummary = 'modal_submit_ratings_summary';
      ratingSummary +=
        this.selectedRatings.length === 1 ? '_singular' : '_plural';
      ratingSummary += this.role === ROLE_SELF ? '_self' : '_other';

      let confirmMsgParams = {
        amount: this.selectedRatings.length,
        subject_user: this.user.fullname,
      };

      return this.$str(ratingSummary, 'pathway_manual', confirmMsgParams);
    },
  },

  methods: {
    /**
     * Apply the filters that have been selected.
     * This will reload the competencies listed.
     * @param filters
     */
    applyFilters(filters) {
      this.selectedRatings = [];
      this.selectedFilters = filters;
      this.isRatingSingleCompetency = false;
    },

    /**
     * Add/modify an unsaved draft rating.
     * @param ratingData
     */
    updateRating(ratingData) {
      let previousRating = this.selectedRatings.find(
        ratingObj => ratingObj.competency_id === ratingData.competency_id
      );

      if (previousRating) {
        // Was already rated: update value.
        previousRating.scale_value_id = ratingData.scale_value_id;
        previousRating.comment = ratingData.comment;
      } else {
        // Wasn't in list yet: add to array.
        this.selectedRatings.push(ratingData);
      }

      this.updateUnloadHandler();
    },

    /**
     * Remove an unsaved draft rating.
     * @param compId
     */
    deleteRating(compId) {
      this.selectedRatings = this.selectedRatings.filter(
        previousRating => previousRating.competency_id !== compId
      );

      this.updateUnloadHandler();
    },

    /**
     * Notify the browser and parent component that a rating has been changed.
     */
    updateUnloadHandler() {
      // Warn user about leaving the page when having unsaved selections.
      if (this.hasSelectedRatings) {
        window.addEventListener('beforeunload', this.unloadHandler);
      } else {
        window.removeEventListener('beforeunload', this.unloadHandler);
      }

      this.$emit('has-unsaved-ratings', this.hasSelectedRatings);
    },

    /**
     * Update a comment
     * @param newComment
     */
    updateComment(newComment) {
      let rating = this.selectedRatings.find(
        ratingData => ratingData.competency_id === newComment.competency_id
      );
      if (rating) {
        rating.comment = newComment.comment;
      }
    },

    /**
     * Displays a warning message if the user tries to navigate away without saving.
     * @param {Event} e
     * @returns {string}
     */
    unloadHandler(e) {
      // For older browsers that still show custom message.
      let discardUnsavedChanges = this.$str(
        'unsaved_ratings_warning',
        'pathway_manual'
      );
      e.preventDefault();
      e.returnValue = discardUnsavedChanges;
      return discardUnsavedChanges;
    },

    /**
     * Get rating data for sending to GQL mutation.
     * @returns {{scale_value_id: (null|*), comment: (string|*), competency_id: *}[]}
     */
    getRatingsForSaving() {
      return this.selectedRatings.map(rating => ({
        competency_id: rating.competency_id,
        // Map "None" to null.
        scale_value_id:
          parseInt(rating.scale_value_id) === NONE_OPTION_VALUE
            ? null
            : rating.scale_value_id,
        comment: typeof rating.comment === 'undefined' ? '' : rating.comment,
      }));
    },

    /**
     * Save the draft ratings the user has made.
     */
    async submitRatings() {
      this.isSaving = true;
      try {
        const { data: result } = await this.$apollo.mutate({
          // Query
          mutation: CreateManualRatingsMutation,
          // Parameters
          variables: {
            user_id: this.user.id,
            role: this.role,
            ratings: this.getRatingsForSaving(),
          },
          refetchAll: false,
        });

        if (result && result.pathway_manual_create_manual_ratings) {
          window.removeEventListener('beforeunload', this.unloadHandler);
          this.$emit('saved', this.selectedRatings.length);
        } else {
          this.showErrorNotification();
        }
      } catch (e) {
        this.showErrorNotification();
      } finally {
        this.isSaving = false;
        this.showSubmitRatingsModal = false;
      }
    },

    /**
     * Show generic save/update error toast.
     */
    showErrorNotification() {
      notify({
        message: this.$str('error_ratings_not_saved', 'pathway_manual'),
        type: 'error',
      });
    },
  },

  apollo: {
    competencies: {
      query: RateableCompetenciesQuery,
      variables() {
        return {
          user_id: this.user.id,
          role: this.role,
          filters: this.selectedFilters,
        };
      },
      update({ pathway_manual_user_rateable_competencies: competencies }) {
        if (competencies.filters != null) {
          this.filterOptions = competencies.filters;
        }
        return competencies;
      },
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "cancel",
      "submit"
    ],
    "pathway_manual": [
      "error_ratings_not_saved",
      "filter_no_competencies",
      "modal_submit_ratings_confirmation_title",
      "modal_submit_ratings_confirmation_question",
      "modal_submit_ratings_summary_singular_other",
      "modal_submit_ratings_summary_singular_self",
      "modal_submit_ratings_summary_plural_other",
      "modal_submit_ratings_summary_plural_self",
      "no_rateable_competencies",
      "unsaved_ratings_warning"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-bulkManualRatingRateUserCompetencies {
  & > * + * {
    margin-top: var(--gap-5);
  }

  &__frameworks {
    & > * + * {
      margin-top: var(--gap-5);
    }
  }

  &__noCompetencies {
    @include tui-font-hint;
  }

  &__submitButtons {
    display: flex;
    flex-direction: row-reverse;
  }
}
</style>
