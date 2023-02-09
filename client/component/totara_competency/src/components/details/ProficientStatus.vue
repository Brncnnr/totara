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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module totara_competency
-->

<template>
  <span
    class="tui-competencyDetailProficientStatus"
    :class="{
      'tui-competencyDetailProficientStatus-srOnly': !proficientStatus,
    }"
  >
    <ProficientIcon v-if="proficientStatus" size="200" />
    <span>
      {{
        $str(
          proficientStatus ? 'proficient' : 'not_proficient',
          'totara_competency'
        )
      }}
    </span>
  </span>
</template>

<script>
import ProficientIcon from 'tui/components/icons/CheckSuccess';

export default {
  components: {
    ProficientIcon,
  },
  props: {
    proficientStatus: {
      type: Boolean,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "not_proficient",
      "proficient"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyDetailProficientStatus {
  @media screen and (min-width: $tui-screen-xs) {
    font-weight: bold;
    &-srOnly {
      @include sr-only();
    }
  }
}
</style>
