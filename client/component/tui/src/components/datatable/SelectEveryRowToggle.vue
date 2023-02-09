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
  @module tui
-->

<template>
  <div class="tui-dataTableSelectEveryRowToggle">
    <div v-if="showSelect" name="selectEntirePrompt">
      {{ $str('selectallpageselected', 'totara_core', { count: count }) }}
      <a href="#" @click.prevent="selectFunc">
        {{ $str('selectentireresult', 'totara_core') }}
      </a>
    </div>

    <div v-else name="clearEntirePrompt">
      {{ $str('selectentireresultselected', 'totara_core') }}
      <a href="#" @click.prevent="clearFunc">
        {{ $str('clearselection', 'totara_core') }}
      </a>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    clearFunc: Function,
    count: Number,
    selectFunc: Function,
    showSelect: Boolean,
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "clearselection",
    "selectallpageselected",
    "selectentireresult",
    "selectentireresultselected"
  ]
}
</lang-strings>

<style lang="scss">
.tui-dataTableSelectEveryRowToggle {
  padding: var(--gap-2);
  background-color: var(--datatable-select-everything-prompt-bg);
}
</style>
