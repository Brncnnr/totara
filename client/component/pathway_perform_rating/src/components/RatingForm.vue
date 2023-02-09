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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module pathway_perform_rating
-->

<template>
  <div>
    <Rating
      v-if="isRatingEnabled && data && data.can_view_rating"
      class="tui-competencyLinkedReviewRatingForm"
      :from-print="fromPrint"
      :rating="data.rating"
      :required="required"
    >
      <!-- Display rating  -->
      <template v-slot:display>
        <Grid
          class="tui-competencyLinkedReviewRatingForm__summary"
          :stack-at="700"
        >
          <GridItem :units="4">
            <template v-if="data.rating.rater_user">
              <span>
                {{ $str('rating_by_colon', 'pathway_perform_rating') }}
              </span>
              {{
                $str('rater_and_relationship', 'pathway_perform_rating', {
                  rater: data.rating.rater_user.fullname,
                  relationship: ratingRelationship,
                })
              }}
            </template>
            <template v-else>
              {{
                $str(
                  'rating_by_relationship',
                  'pathway_perform_rating',
                  ratingRelationship
                )
              }}
            </template>
          </GridItem>
          <GridItem :units="3">
            <span class="sr-only">
              {{ $str('rating_on', 'pathway_perform_rating') }}
            </span>
            {{ data.rating.created_at }}
          </GridItem>
          <GridItem
            class="tui-competencyLinkedReviewRatingForm__summary-rating"
            :units="5"
          >
            <span>
              {{ $str('rating_final', 'pathway_perform_rating') }}
            </span>
            <template v-if="data.rating.scale_value">
              {{ data.rating.scale_value.name }}
            </template>
            <template v-else>
              {{ $str('rating_no_rating', 'pathway_perform_rating') }}
            </template>
          </GridItem>
        </Grid>
      </template>

      <!-- Display the form for collecting rating -->
      <template v-slot:form>
        <Uniform
          v-if="data.can_rate && participantInstanceId"
          :initial-values="initialValues"
          :validate="validations"
          @submit="confirmRating"
        >
          <FormRow v-if="data.can_rate && !fromPrint" :label="ratingLabel">
            <InputSet char-length="full">
              <FormSelect
                :id="$id('scaleValue')"
                name="scaleValue"
                :aria-label="$str('rating_select', 'pathway_perform_rating')"
                :options="scaleValueOptions"
                char-length="15"
              />
              <Field v-slot="{ value }" name="scaleValue">
                <Button
                  :styleclass="{ primary: true, small: true }"
                  :text="$str('rating_submit', 'pathway_perform_rating')"
                  type="submit"
                  :disabled="value === 0"
                />
              </Field>
            </InputSet>
          </FormRow>

          <FormRow v-else :label="ratingLabel">
            <FormRadioGroup name="scaleValue">
              <Radio
                v-for="item in scaleValueOptions"
                :key="item.id"
                :value="item.id"
              >
                {{ item.label }}
              </Radio>
            </FormRadioGroup>
          </FormRow>
        </Uniform>

        <div v-else>
          {{ $str('rating_who', 'pathway_perform_rating', ratingRelationship) }}
        </div>
      </template>
    </Rating>

    <ConfirmationModal
      :open="modalOpen"
      :title="$str('rating_confirmation_title', 'pathway_perform_rating')"
      :confirm-button-text="$str('rating_submit', 'pathway_perform_rating')"
      :loading="isSaving"
      @confirm="saveRating"
      @cancel="cancelRating"
    >
      <p
        v-html="
          $str('rating_confirmation_body_1', 'pathway_perform_rating', {
            user: subjectUser.fullname,
            scale_value: selectedScaleValueName,
          })
        "
      />
      <p>{{ $str('rating_confirmation_body_2', 'pathway_perform_rating') }}</p>
    </ConfirmationModal>
  </div>
</template>

<script>
// Components
import Button from 'tui/components/buttons/Button';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Field from 'tui/components/reform/Field';
import {
  FormRadioGroup,
  FormRow,
  FormSelect,
  Uniform,
} from 'tui/components/uniform';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import InputSet from 'tui/components/form/InputSet';
import Radio from 'tui/components/form/Radio';
import Rating from 'pathway_perform_rating/components/Rating';
import { notify } from 'tui/notifications';
import { v as validation } from 'tui/validation';

// Query
import rateCompetencyMutation from 'pathway_perform_rating/graphql/linked_competencies_rate';

export default {
  components: {
    Button,
    ConfirmationModal,
    Field,
    FormRadioGroup,
    FormRow,
    FormSelect,
    Grid,
    GridItem,
    InputSet,
    Radio,
    Rating,
    Uniform,
  },

  props: {
    content: {
      type: Object,
      required: true,
    },
    elementData: Object,
    fromPrint: Boolean,
    isDraft: Boolean,
    participantInstanceId: {
      type: [String, Number],
      // For view-only this is not passed
      required: false,
    },
    required: Boolean,
    sectionElementId: {
      type: [String, Number],
      required: true,
    },
    subjectUser: {
      required: true,
      type: Object,
    },
  },

  data() {
    return {
      data: this.content,
      initialValues: {
        scaleValue: 0,
      },
      isSaving: false,
      modalOpen: false,
      selectedScaleValueId: 0,
    };
  },

  computed: {
    /**
     * Are ratings enabled for this linked review element?
     */
    isRatingEnabled() {
      return this.elementData.content_type_settings.enable_rating;
    },

    /**
     * The relationship that is to rate competencies for this linked review element.
     */
    ratingRelationship() {
      return this.elementData.content_type_settings.rating_relationship_name;
    },

    /**
     * The scale value that has been selected by the user.
     */
    selectedScaleValueName() {
      if (this.selectedScaleValueId === 0) {
        return null;
      }

      if (this.selectedScaleValueId === null) {
        return this.$str('rating_no_rating', 'pathway_perform_rating');
      }

      return this.data.scale_values.find(
        v => v.id === this.selectedScaleValueId
      ).name;
    },

    /**
     * Create rater string for label
     *
     * @return {string}
     */
    ratingLabel() {
      return this.$str(
        'rating_relationship_label',
        'pathway_perform_rating',
        this.ratingRelationship || ''
      );
    },

    /**
     * Generate select list options with placeholder value
     *
     * @return {array}
     */
    scaleValueOptions() {
      const scaleValues = this.data.scale_values ? this.data.scale_values : [];

      let values = this.fromPrint
        ? []
        : [
            {
              id: 0,
              label: this.$str('rating_select', 'pathway_perform_rating'),
            },
          ];

      return values
        .concat(
          scaleValues.map(value => {
            return {
              id: value.id,
              label: value.name,
            };
          })
        )
        .concat([
          {
            id: null,
            label: this.$str('rating_set_to_none', 'pathway_perform_rating'),
          },
        ]);
    },
  },

  methods: {
    /**
     * An array of validation rules for the element.
     *
     * @return {(function|object)[]}
     */
    validations() {
      if (!this.required || this.isDraft) {
        return [];
      }
      return [validation.required()];
    },

    /**
     * Open confirmation modal to confirm selection before saving.
     */
    confirmRating(values) {
      this.selectedScaleValueId = values.scaleValue;
      this.modalOpen = true;
    },

    /**
     * Close confirmation modal and clear what was selected.
     */
    cancelRating() {
      this.selectedScaleValueId = 0;
      this.modalOpen = false;
    },

    /**
     * Save the linked review manual rating
     *
     * @return {array}
     */
    async saveRating() {
      this.isSaving = true;

      try {
        const { data: resultData } = await this.$apollo.mutate({
          mutation: rateCompetencyMutation,
          variables: {
            input: {
              competency_id: this.data.competency.id,
              participant_instance_id: this.participantInstanceId,
              scale_value_id: this.selectedScaleValueId,
              section_element_id: this.sectionElementId,
            },
          },
        });

        const result =
          resultData.pathway_perform_rating_linked_competencies_rate;
        this.data = Object.assign({}, this.data, {
          rating: result.rating,
        });

        // A user has already provided a rating so prevent this value from being
        // stored and update the UI to show other users rating.
        if (result.already_exists === true) {
          this.$emit(
            'show-banner',
            this.$str('rating_already_made_message', 'pathway_perform_rating')
          );
        } else {
          notify({
            message: this.$str('rating_saved', 'pathway_perform_rating'),
            type: 'success',
          });
        }
      } finally {
        this.isSaving = false;
        this.cancelRating();
      }
    },
  },
};
</script>

<lang-strings>
{
  "pathway_perform_rating": [
    "rater_and_relationship",
    "rating_already_made_message",
    "rating_by_colon",
    "rating_by_relationship",
    "rating_confirmation_body_1",
    "rating_confirmation_body_2",
    "rating_confirmation_title",
    "rating_final",
    "rating_no_rating",
    "rating_on",
    "rating_relationship_label",
    "rating_saved",
    "rating_select",
    "rating_set_to_none",
    "rating_who",
    "rating_submit"
  ]
}
</lang-strings>

<style lang="scss">
.tui-competencyLinkedReviewRatingForm {
  max-width: 1200px;

  &__summary {
    &-rating {
      @include tui-font-heavy();
    }
  }
}
</style>
