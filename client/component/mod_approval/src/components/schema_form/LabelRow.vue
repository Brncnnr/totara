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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <h4 class="tui-mod_approval-labelRow">{{ heading }}</h4>
</template>

<script>
export default {
  components: {},

  props: {
    heading: String,
  },
};
</script>

<style lang="scss">
.tui-mod_approval-labelRow {
  @include tui-font-heading-x-small();
  font-weight: bold;
}
</style>
