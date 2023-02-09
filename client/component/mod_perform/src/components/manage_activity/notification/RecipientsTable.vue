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

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_perform
  @deprecated Since Totara 17.0
-->

<template>
  <ul
    class="tui-activityNotificationsRecipientsTable"
    :aria-label="$str('recipients', 'mod_perform')"
  >
    <li
      v-for="e in data"
      :key="e.relationship_id"
      class="tui-activityNotificationsRecipientsTable__row"
    >
      <ToggleSwitch
        :text="e.name"
        :value="e.active"
        :toggle-first="true"
        @input="onToggle(e, $event)"
      />
    </li>
  </ul>
</template>

<script>
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';

export default {
  components: {
    ToggleSwitch,
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
  },

  methods: {
    onToggle(row, val) {
      this.$emit('toggle', {
        id: row.relationship_id,
        active: val,
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_perform": [
    "recipients"
  ]
}
</lang-strings>

<style lang="scss">
.tui-activityNotificationsRecipientsTable {
  width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;

  &__row {
    margin: 0 0 var(--gap-4);
    padding: 0;
  }
}
</style>
