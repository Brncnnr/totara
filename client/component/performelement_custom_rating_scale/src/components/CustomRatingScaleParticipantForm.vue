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

  @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
  @module performelement_custom_rating_scale
-->
<template>
  <!-- Handle the different view switching (read only / print / form),
  populate form content if editable and display others responses -->
  <ElementParticipantFormContent
    v-bind="$attrs"
    :element="element"
    :from-print="false"
    :is-draft="isDraft"
  >
    <template v-slot:content>
      <FormScope :path="path" :process="process">
        <FormRadioGroup
          class="tui-customRatingScaleParticipantForm"
          name="response"
          :char-length="50"
          :validations="validations"
        >
          <template v-for="(item, index) in element.data.options">
            <Radio :key="index" :value="item.name">
              {{
                $str('answer_output', 'performelement_custom_rating_scale', {
                  label: item.value.text,
                  count: item.value.score,
                })
              }}
            </Radio>
            <RenderedContent
              v-if="item.descriptionEnabled && fromPrint"
              :key="'description' + index"
              class="tui-customRatingScaleParticipantForm__descriptionPrint"
              :content-html="item.descriptionHtml"
            />
            <ElementDescription
              v-else-if="item.descriptionEnabled"
              :key="'description' + index"
              class="tui-customRatingScaleParticipantForm__description"
              :aria-region-label="
                $str(
                  'option_description',
                  'performelement_custom_rating_scale',
                  index + 1
                )
              "
              :content-html="item.descriptionHtml"
            />
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
    path: {
      type: [String, Array],
      default: '',
    },
    fromPrint: Boolean,
  },

  computed: {
    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @return {(function|object)[]}
     */
    validations() {
      if (this.isDraft) {
        return [];
      }

      if (this.element && this.element.is_required) {
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
.tui-customRatingScaleParticipantForm {
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
    "performelement_custom_rating_scale": [
      "answer_output",
      "option_description"
    ]
  }
</lang-strings>
