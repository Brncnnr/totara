<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_perform
  @deprecated Since Totara 17.0
-->

<template>
  <div class="tui-activityNotificationsTriggersTable">
    <Repeater
      class="tui-activityNotificationsTriggers"
      :rows="values"
      :min-rows="1"
      :delete-icon="true"
      :allow-deleting-first-items="true"
      :aria-label="$str('trigger_events', 'mod_perform')"
      role="region"
      @add="onAdd"
      @remove="onRemove"
    >
      <template v-slot="{ row, index }">
        <div class="tui-activityNotificationsTrigger">
          <div class="tui-activityNotificationsTrigger__field">
            <div class="tui-activityNotificationsTrigger__value">
              <InputNumber
                class="tui-activityNotificationsTrigger__input"
                :value="row"
                :min="1"
                :max="365"
                :placeholder="$str('trigger_set', 'mod_perform')"
                :aria-labelledby="$id(index)"
                :name="'trigger-' + classKey + '[' + index + ']'"
                autocomplete="off"
                @input="onInput(index, $event)"
              />
            </div>
            <span
              :id="$id(index)"
              class="tui-activityNotificationsTrigger__format"
              >{{ label }}</span
            >
          </div>
          <FieldError
            class="tui-activityNotificationsTrigger__error"
            :error="errors[index] ? $str(errors[index], 'mod_perform') : ''"
          />
        </div>
      </template>
    </Repeater>
  </div>
</template>

<script>
import InputNumber from 'tui/components/form/InputNumber';
import Repeater from 'tui/components/form/Repeater';
import FieldError from 'tui/components/form/FieldError';

/**
 * See if an array has duplicate numbers.
 * @param {number[]} array
 * @returns {array}
 */
function validate(array) {
  if (!array.length) {
    return [];
  }
  return array.map((val, index) => {
    val = 1 * val;
    if (isNaN(val) || val < 1 || val > 365) {
      return 'trigger_out_of_range';
    }
    if (array.indexOf(val) !== index) {
      return 'trigger_duplicates';
    }
    if (array.indexOf(val, index + 1) !== -1) {
      return 'trigger_duplicates';
    }
    return false;
  });
}

export default {
  components: {
    InputNumber,
    Repeater,
    FieldError,
  },
  props: {
    data: {
      type: Array,
      required: true,
    },
    classKey: {
      type: String,
      required: true,
    },
    label: String,
  },
  data() {
    let values = this.data.slice();
    return {
      values: values.map(e => 1 * e),
      errors: validate(values),
    };
  },
  watch: {
    data(values) {
      values = values.slice();
      this.values = values.map(e => 1 * e);
      this.update();
    },
  },
  methods: {
    onInput(i, val) {
      this.values[i] = 1 * val;
      this.update();
    },
    async onAdd() {
      this.values.push('');
      this.errors.push(false);
      await this.$nextTick();
      let inputs = this.$el.querySelectorAll(
        '.tui-activityNotificationsTrigger__input'
      );
      if (inputs.length) {
        inputs[inputs.length - 1].focus();
      }
    },
    onRemove(val, i) {
      if (this.values.length > 0) {
        this.values.splice(i, 1);
        this.update();
      }
    },
    update() {
      this.errors = validate(this.values);
      if (!this.errors.filter(e => e !== false).length) {
        this.$emit('input', this.values.slice());
      }
    },
  },
};
</script>

<lang-strings>
{
  "mod_perform": [
    "trigger_events",
    "trigger_duplicates",
    "trigger_out_of_range",
    "trigger_set"
  ]
}
</lang-strings>

<style lang="scss">
.tui-activityNotificationsTriggersTable {
  width: 100%;
  &__banner {
    margin-top: var(--gap-2);
  }
}
.tui-activityNotificationsTriggers {
  margin: 0;
  padding: 0;
  list-style: none;
}
.tui-activityNotificationsTrigger {
  $value-width: 6em;
  width: 100%;
  margin: 0 0 var(--gap-2) 0;
  padding: 0;
  &__field {
    display: flex;
    flex-grow: 1;
    align-items: center;
  }
  &__value {
    width: $value-width;
  }
  &__format {
    width: calc(100% - #{$value-width} - var(--gap-4));
    margin: 0 var(--gap-2);
  }
  &__error {
    margin-top: var(--gap-1);
  }
}
</style>
