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
  <div class="tui-assignmentProgress">
    <PercentageDoughnut
      :percentage="progress.overall_progress"
      :label="$str('chart_label_proficient', 'totara_competency')"
      :label-font-size="13"
      label-font-weight="bold"
      :percentage-font-size="24"
      :square="true"
      :label-color="labelColor"
      :color="color"
      :background-color="backgroundColor"
      class="tui-assignmentProgress__progress-card"
    />
    <div class="tui-assignmentProgress__header">
      {{ progress.name }}
    </div>
  </div>
</template>

<script>
import PercentageDoughnut from 'tui_charts/components/PercentageDoughnut';
import theme from 'tui/theme';

export default {
  components: {
    PercentageDoughnut,
  },

  props: {
    progress: {
      required: true,
      type: Object,
    },
  },

  computed: {
    color: () => theme.getVar('--color-secondary'),
    backgroundColor: () => theme.getVar('--color-neutral-4'),
    labelColor: () => theme.getVar('--color-text'),
  },
};
</script>

<lang-strings>
{
  "totara_competency" : [
    "chart_label_proficient"
  ]
}
</lang-strings>

<style lang="scss">
.tui-assignmentProgress {
  width: 180px;
  padding: var(--gap-8) var(--gap-3) 0;

  &__header {
    margin-top: var(--gap-3);
    overflow: hidden;
    font-size: var(--font-size-16);
    white-space: nowrap;
    text-align: center;
    text-overflow: ellipsis;
  }

  &__progress-card {
    padding: 0 var(--gap-4);
  }
}
</style>
