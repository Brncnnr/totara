<!--
  This file is part of Totara Learn

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module performelement_competency_rating
  -->

<template>
  <!-- Handle the different view switching (read only / print / form),
  populate form content if editable and display others responses -->
  <ElementParticipantFormContent
    v-bind="$attrs"
    :element="element"
    :is-draft="isDraft"
    :from-print="false"
  >
    <template v-slot:content>
      <FormScope :path="path" :process="process">
        <FormRadioGroup
          class="tui-competencyRatingParticipantForm"
          name="response"
          :char-length="50"
          :validations="validations"
        >
          <template
            v-for="(scaleValue, index) in extraData.content.scale_values"
          >
            <Radio :key="scaleValue.id" :value="scaleValue.id">
              {{ scaleValue.name }}
            </Radio>
            <template
              v-if="
                element.data.scaleDescriptionsEnabled &&
                  scaleValue.description_html
              "
            >
              <RenderedContent
                v-if="fromPrint"
                :key="'description' + index"
                class="tui-competencyRatingParticipantForm__descriptionPrint"
                :content-html="scaleValue.description_html"
              />
              <ElementDescription
                v-else
                :key="'description' + index"
                class="tui-competencyRatingParticipantForm__description"
                :aria-region-label="
                  $str(
                    'option_description',
                    'performelement_competency_rating',
                    scaleValue.name
                  )
                "
                :content-html="scaleValue.description_html"
                initially-closed
              />
            </template>
          </template>
        </FormRadioGroup>
      </FormScope>
    </template>
  </ElementParticipantFormContent>
</template>

<script>
import ElementDescription from 'mod_perform/components/element/participant_form/ElementDescription';
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';
import FormRadioGroup from 'tui/components/uniform/FormRadioGroup';
import FormScope from 'tui/components/reform/FormScope';
import Radio from 'tui/components/form/Radio';
import RenderedContent from 'tui/components/editor/RenderedContent';
import { v as validation } from 'tui/validation';

export default {
  components: {
    ElementDescription,
    ElementParticipantFormContent,
    FormRadioGroup,
    FormScope,
    Radio,
    RenderedContent,
  },

  props: {
    element: Object,
    isDraft: Boolean,
    fromPrint: Boolean,
    extraData: {
      type: Object,
      required: true,
      /**
       * Validates the content has valid scale values.
       */
      validator(value) {
        if (
          !value.content ||
          !value.content.scale_values ||
          value.content.scale_values.length < 1
        ) {
          return false;
        }

        return value.content.scale_values.every(scale_value => {
          return scale_value.id && scale_value.name;
        });
      },
    },
    path: {
      type: [String, Array],
      default: '',
    },
  },

  computed: {
    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @return {(function|object)[]}
     */
    validations() {
      if (!this.isDraft && this.element && this.element.is_required) {
        return [validation.required()];
      }

      return [];
    },
  },

  methods: {
    /**
     * Process the form values.
     *
     * @param value
     * @return {null|string}
     */
    process(value) {
      if (!value || !value.response) {
        return null;
      }

      return value.response;
    },
  },
};
</script>

<style lang="scss">
.tui-competencyRatingParticipantForm {
  &__description {
    margin-top: var(--gap-1);
    margin-left: var(--gap-4);
  }

  &__descriptionPrint {
    margin-top: var(--gap-4);
    margin-left: var(--gap-6);
  }
}
</style>

<lang-strings>
{
  "performelement_competency_rating": [
    "option_description"
  ]
}
</lang-strings>
