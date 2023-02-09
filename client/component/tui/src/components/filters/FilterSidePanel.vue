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
  <Card class="tui-filterSidePanel" :no-border="true">
    <SkipToContent
      v-if="skipContentId"
      :content-id="skipContentId"
      :text="skipContentText"
    />

    <div class="tui-filterSidePanel__heading">
      <h3 class="tui-filterSidePanel__header">
        {{ title }}
        <span v-if="activeCount" aria-hidden="true">( {{ activeCount }} )</span>
      </h3>
      <div
        v-if="Object.entries(value).length"
        class="tui-filterSidePanel__status"
        role="status"
      >
        {{
          $str(
            activeCount == 1
              ? 'a11y_active_filter_type'
              : 'a11y_active_filter_type_plural',
            'totara_core',
            activeCount
          )
        }}
      </div>
      <div class="tui-filterSidePanel__instructions">
        {{ $str('a11y_filter_panel_desc', 'totara_core') }}
      </div>
      <Button
        v-if="Object.entries(value).length"
        class="tui-filterSidePanel__clearBtn"
        :aria-disabled="!activeCount"
        :aria-label="$str('filter_clear_active', 'totara_core')"
        :disabled="!activeCount"
        :styleclass="{
          srOnly: !activeCount,
          transparent: 'true',
          small: true,
        }"
        :text="$str('clearall', 'totara_core')"
        @click="clearAllFilters"
      />
    </div>
    <slot />
  </Card>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Card from 'tui/components/card/Card';
import SkipToContent from 'tui/components/links/SkipToContent';

export default {
  components: {
    Button,
    Card,
    SkipToContent,
  },

  props: {
    // Skip content target ID
    skipContentId: String,
    // Skip content custom text
    skipContentText: String,
    title: String,
    value: {
      default() {
        return {};
      },
      type: Object,
    },
  },

  computed: {
    /**
     * Calculate the number of active filters
     *
     * @return {Int}
     */
    activeCount() {
      let count = 0;
      Object.keys(this.value).forEach(key => {
        let val = this.value[key];

        if (val instanceof Array) {
          if (val.length !== 0) {
            count += val.length;
          }
        } else if (val !== '') {
          count++;
        }
      });
      return count;
    },
  },

  watch: {
    activeCount: {
      handler(value) {
        this.$emit('active-count-change', value);
      },
      immediate: true,
    },
  },

  methods: {
    clearAllFilters() {
      Object.keys(this.value).forEach(nestedKey => {
        let type = this.value[nestedKey] instanceof Array ? [] : '';
        this.value[nestedKey] = type;
      });
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "a11y_active_filter_type",
    "a11y_active_filter_type_plural",
    "a11y_filter_panel_desc",
    "clearall",
    "filter_clear_active"
  ]
}
</lang-strings>

<style lang="scss">
.tui-filterSidePanel {
  display: block;

  &__header {
    @include tui-font-heading-small();
    min-width: 0;
    margin: 0 var(--gap-2) 0 0;
  }

  &__heading {
    display: flex;
    padding-bottom: var(--gap-2);
    border-bottom: var(--border-width-normal) solid var(--color-neutral-7);
  }

  &__instructions,
  &__status {
    @include sr-only();
  }

  .tui-filterSidePanel__clearBtn {
    flex-shrink: 0;
    margin-left: auto;
  }
}

.tui-filterSidePanel.tui-filterSidePanel {
  & > * + * {
    margin-top: var(--gap-4);
  }

  .tui-fieldset__legend,
  .tui-formLabel {
    @include tui-font-heading-label-small();
  }
}
</style>
