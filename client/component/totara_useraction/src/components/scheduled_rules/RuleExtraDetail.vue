<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totara.com] for more information.

  @author Simon Chester <simon.chester@totara.com>
  @module tui
-->

<template>
  <div class="tui-totara_useraction-ruleExtraDetail">
    <div class="tui-totara_useraction-ruleExtraDetail__header">
      {{ $str('criteria', 'totara_useraction') }}
    </div>
    <div
      class="tui-totara_useraction-ruleExtraDetail__description"
      v-text="rule.description"
    />
    <Form input-width="full">
      <!--
        Add internal div to prevent spacing from being added -- we
        want something more compact.
      -->
      <div>
        <FormRow :label="$str('applies_to', 'totara_useraction')">
          <InputSizedText>
            <template v-if="audiences">
              <template v-for="(aud, i) in audiences">
                <!-- This is a bit ugly, but necessary due to Vue's handling of whitespace. -->
                <a
                  :key="aud.id"
                  :href="$url('/cohort/view.php', { id: aud.id })"
                  >{{ aud.name }}</a
                >{{ audiences.length - 1 === i ? '' : ', ' }}
              </template>
              <template v-if="audiences.length === 0">
                {{ $str('no_audiences_selected', 'totara_useraction') }}
              </template>
            </template>
            <template v-else>
              {{ $str('filter_applies_to_all_users', 'totara_useraction') }}
            </template>
          </InputSizedText>
        </FormRow>

        <FormRow :label="$str('user_status', 'totara_useraction')">
          <InputSizedText>
            {{
              userStatuses[rule.filters.user_status] || rule.filters.user_status
            }}
          </InputSizedText>
        </FormRow>

        <FormRow :label="$str('data_source', 'totara_useraction')">
          <InputSizedText>
            {{
              durationSources[rule.filters.duration.source] ||
                rule.filters.duration.source
            }}
          </InputSizedText>
        </FormRow>

        <FormRow :label="$str('duration', 'totara_useraction')">
          <InputSizedText>
            {{ formatDuration(rule) }}
          </InputSizedText>
        </FormRow>
      </div>
    </Form>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InputSizedText from 'tui/components/form/InputSizedText';

export default {
  components: {
    Form,
    FormRow,
    InputSizedText,
  },

  props: {
    rule: Object,
  },

  data() {
    return {
      userStatuses: {
        SUSPENDED: this.$str('suspended', 'core'),
      },
      durationSources: {
        DATE_SUSPENDED: this.$str('date_suspended', 'totara_useraction'),
      },
    };
  },

  computed: {
    audiences() {
      return this.rule.filters.applies_to.audiences;
    },
  },

  methods: {
    formatDuration(row) {
      const { value, unit } = row.filters.duration;
      const one = value === 1;

      switch (unit) {
        case 'DAY':
          return one
            ? this.$str('numday', 'core', value)
            : this.$str('numdays', 'core', value);
        case 'MONTH':
          return one
            ? this.$str('nummonth', 'core', value)
            : this.$str('nummonths', 'core', value);
        case 'YEAR':
          return one
            ? this.$str('numyear', 'core', value)
            : this.$str('numyears', 'core', value);
      }
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "numday",
    "numdays",
    "nummonth",
    "nummonths",
    "suspended",
    "numyear",
    "numyears"
  ],
  "totara_useraction": [
    "applies_to",
    "criteria",
    "data_source",
    "date_suspended",
    "duration",
    "filter_applies_to_all_users",
    "no_audiences_selected",
    "user_status"
  ]
}
</lang-strings>

<style lang="scss">
.tui-totara_useraction-ruleExtraDetail {
  &__header {
    @include tui-font-heading-label-small();
    padding: var(--gap-2) 0;
    border-bottom: var(--border-width-thin) solid var(--color-neutral-5);
  }

  &__description {
    margin: var(--gap-4) 0;
    white-space: pre-wrap;
    word-break: break-word;
  }
}
</style>
