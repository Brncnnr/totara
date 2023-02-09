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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module engage_survey
-->

<template>
  <FormRow
    v-slot="{ labelId }"
    :label="label"
    class="tui-engageSurveySquareBox"
    hidden
  >
    <CheckboxGroup :aria-labelledby="labelId">
      <FormRow v-for="option in options" :key="option.id">
        <Checkbox
          :key="option.id"
          :name="'engagesurvey-checkbox'"
          :value="option.id"
          class="tui-engageSurveySquareBox__checkbox"
          @change="$_handleChange(option.id, $event)"
        >
          {{ option.value }}
        </Checkbox>
      </FormRow>
    </CheckboxGroup>
  </FormRow>
</template>

<script>
import Checkbox from 'tui/components/form/Checkbox';
import CheckboxGroup from 'tui/components/form/CheckboxGroup';
import { FormRow } from 'tui/components/uniform';

const has = Object.prototype.hasOwnProperty;

export default {
  components: {
    Checkbox,
    CheckboxGroup,
    FormRow,
  },

  model: {
    prop: 'value',
    event: 'update-value',
  },

  props: {
    value: {
      // A property that is being used for v-model
      type: Array,
      default() {
        return [];
      },
    },

    options: {
      type: [Array, Object],
      validator(prop) {
        for (let i in prop) {
          if (!has.call(prop, i)) {
            continue;
          }

          let item = prop[i];
          if (!has.call(item, 'id') || !has.call(item, 'value')) {
            return false;
          }
        }

        return true;
      },
    },

    label: String,
  },

  data() {
    return {
      picked: [],
    };
  },

  methods: {
    $_handleChange(id, checked) {
      if (!checked) {
        this.picked = this.picked.filter(function(item) {
          return item !== id;
        });
      } else if (checked && !this.picked.includes(id)) {
        // Adding.
        this.picked.push(id);
      }

      // We are making sure that the whole button vote will be blocked form clicked.
      let picked = null;

      if (0 < this.picked.length) {
        picked = this.picked;
      }

      this.$emit('update-value', picked);
    },
  },
};
</script>

<style lang="scss">
.tui-engageSurveySquareBox {
  &__checkbox {
    font-size: var(--font-size-15);
  }
  .tui-checkbox {
    min-height: var(--gap-4);
    @include tui-wordbreak--hyphens;
  }
  .tui-checkboxGroup > * + * {
    margin-top: 0;
  }
}
</style>
