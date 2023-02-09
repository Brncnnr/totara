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
  <div class="tui-samplesCode">
    <Collapsible v-model="expanded" label="Code example">
      <template v-if="$slots.template">
        <h5>Template</h5>
        <pre class="tui-samplesCode__box"><slot name="template" /></pre>
      </template>
      <template v-if="$slots.script">
        <h5>Script</h5>
        <pre class="tui-samplesCode__box"><slot name="script" /></pre>
      </template>
      <template v-if="$slots.style">
        <h5>Style</h5>
        <pre class="tui-samplesCode__box"><slot name="style" /></pre>
      </template>
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
      expanded: false,
    };
  },
};
</script>

<style lang="scss">
.tui-samplesCode {
  &__box {
    margin: var(--gap-4) 0;
    padding: var(--gap-4);
    background-color: var(--color-neutral-3);
    border: 1px solid var(--color-neutral-5);
  }
}
</style>
