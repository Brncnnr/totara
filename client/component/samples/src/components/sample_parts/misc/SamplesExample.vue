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
  @module samples
-->

<template>
  <div class="tui-samplesExample">
    <Collapsible v-model="expanded" label="Example">
      <div class="tui-samplesExample__box">
        <slot />
      </div>
    </Collapsible>
  </div>
</template>

<script>
import Collapsible from 'tui/components/collapsible/Collapsible';

export default {
  components: {
    Collapsible,
  },

  data() {
    return {
      expanded: true,
    };
  },
};
</script>

<style lang="scss">
.tui-samplesExample {
  margin-top: var(--gap-4);

  &__box {
    padding: var(--gap-4);
  }
}
</style>
