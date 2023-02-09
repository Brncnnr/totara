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
  @module mod_perform
-->

<template>
  <div class="tui-performAdminCustomElementSummary">
    <div class="tui-performAdminCustomElementSummary__section">
      <h4 class="tui-performAdminCustomElementSummary__section-title">
        {{ settings.title_text }}
      </h4>

      <div class="tui-performAdminCustomElementSummary__section-value">
        {{ title }}
      </div>
    </div>

    <!-- generic weka content -->
    <div
      v-if="htmlContent"
      class="tui-performAdminCustomElementSummary__section"
    >
      <h4 class="tui-performAdminCustomElementSummary__section-title">
        {{ $str('html_content_title', 'mod_perform') }}
      </h4>

      <div class="tui-performAdminCustomElementSummary__section-value">
        <div ref="content" v-html="htmlContent" />
      </div>
    </div>

    <!-- Custom fields -->
    <div
      v-for="(field, index) in extraFields"
      :key="index"
      class="tui-performAdminCustomElementSummary__section"
    >
      <h4 class="tui-performAdminCustomElementSummary__section-title">
        {{ field.title }}
        <HelpIcon
          v-if="field.helpmsg"
          :helpmsg="field.helpmsg"
          :title="field.title"
        />
      </h4>

      <div class="tui-performAdminCustomElementSummary__section-value">
        <!-- Multiple option values -->
        <div
          v-if="field.options"
          class="tui-performAdminCustomElementSummary__section-options"
        >
          <div v-for="(option, i) in field.options" :key="'option' + i">
            {{ option.value }}
            <template v-if="option.descriptionEnabled">
              <div
                class="tui-performAdminCustomElementSummary__section-optionSubHeading"
              >
                {{ 'Description' }}
              </div>
              <div
                class="tui-performAdminCustomElementSummary__section-valueDescription"
                v-html="option.descriptionHtml"
              />
            </template>
          </div>
        </div>
        <div
          v-else-if="field.htmlContent"
          class="tui-performAdminCustomElementSummary__section-htmlValue"
          v-html="field.value"
        />
        <template v-else>
          {{ field.value }}
        </template>
      </div>
    </div>

    <div
      v-if="identifier"
      class="tui-performAdminCustomElementSummary__section"
    >
      <h4 class="tui-performAdminCustomElementSummary__section-title">
        {{ $str('reporting_identifier', 'mod_perform') }}
      </h4>

      <div class="tui-performAdminCustomElementSummary__section-value">
        {{ identifier }}
      </div>
    </div>

    <div
      v-if="settings.displays_responses"
      class="tui-performAdminCustomElementSummary__section"
    >
      <h4 class="tui-performAdminCustomElementSummary__section-title">
        {{ $str('section_element_response_required', 'mod_perform') }}
      </h4>

      <div class="tui-performAdminCustomElementSummary__section-value">
        {{
          $str(
            isRequired
              ? 'question_response_required_yes'
              : 'question_response_required_no',
            'mod_perform'
          )
        }}
      </div>
    </div>

    <div class="tui-performAdminCustomElementSummary__button">
      <Button
        :styleclass="{ small: true }"
        :text="$str('button_close', 'mod_perform')"
        @click="$emit('display')"
      />
    </div>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import HelpIcon from 'tui/components/form/HelpIcon';

// Utils
import tui from 'tui/tui';

export default {
  components: {
    Button,
    HelpIcon,
  },

  props: {
    extraFields: Array,
    htmlContent: String,
    identifier: String,
    isRequired: Boolean,
    isStatic: Boolean,
    settings: Object,
    title: {
      type: String,
      required: true,
    },
  },

  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    $_scan() {
      this.$nextTick().then(() => {
        let content = this.$refs.content;
        if (!content) {
          return;
        }

        tui.scan(content);
      });
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "button_close",
      "html_content_title",
      "question_response_required_yes",
      "question_response_required_no",
      "section_element_response_required",
      "reporting_identifier"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performAdminCustomElementSummary {
  & > * + * {
    margin-top: var(--gap-8);
  }

  &__button {
    text-align: right;
  }

  &__section {
    & > * + * {
      margin-top: var(--gap-2);
    }

    &-title {
      margin: 0;
      @include tui-font-heading-label();
    }

    &-options {
      & > * + * {
        margin-top: var(--gap-8);
      }
    }

    &-optionSubHeading {
      margin-top: var(--gap-2);
      @include tui-font-body-small();
      color: var(--color-neutral-6);
    }

    &-valueDescription {
      max-width: calc(50 * var(--form-input-font-size));
      margin-top: var(--gap-2);
    }

    &-htmlValue {
      margin: var(--gap-4);
    }
  }
}
</style>
