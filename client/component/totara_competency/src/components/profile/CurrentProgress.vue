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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencyProfileCurrentProgress">
    <ul
      v-if="data.length"
      class="tui-competencyProfileCurrentProgress__progress"
    >
      <li v-for="(item, key) in data" :key="key">
        <AssignmentProgress :progress="item" />
      </li>
    </ul>
    <div v-else class="tui-competencyProfileCurrentProgress__empty">
      {{
        $str(
          isCurrentUser
            ? 'no_current_assignments_self'
            : 'no_current_assignments_other',
          'totara_competency'
        )
      }}
    </div>
  </div>
</template>

<script>
import AssignmentProgress from 'totara_competency/components/AssignmentProgress';

export default {
  components: {
    AssignmentProgress,
  },

  props: {
    data: {
      required: true,
      type: Array,
    },
    isCurrentUser: {
      type: Boolean,
      required: true,
    },
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "no_current_assignments_other",
    "no_current_assignments_self"
  ]
}
</lang-strings>

<style lang="scss">
.tui-competencyProfileCurrentProgress {
  &__empty {
    margin-top: var(--gap-8);
  }

  &__progress {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    margin: 0;
    padding: 0;
    list-style: none;

    @media (min-width: $tui-screen-xs) {
      justify-content: flex-start;
    }
  }
}
</style>
