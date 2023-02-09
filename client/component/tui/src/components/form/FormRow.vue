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
  <div
    class="tui-formRow"
    :class="{
      'tui-formRow--vertical': vertical,
      'tui-formRow--emptyDesc': hidden || (!label && !helpmsg),
      'tui-formRow--fullWidth': fullWidth,
    }"
  >
    <div class="tui-formRow__inner">
      <div class="tui-formRow__desc">
        <Label
          v-if="label"
          :id="generatedLabelId"
          :for-id="generatedId"
          :legend="labelLegend"
          :hidden="hidden"
          :accessible-label="accessibleLabel"
          :label="label"
          :required="required"
          :optional="optional"
          :subfield="subfield"
          :inline="true"
        /><HelpIcon
          v-if="helpmsg || $scopedSlots['help-message']"
          :desc-id="helpDescId"
          :helpmsg="helpmsg"
          :hidden="hidden"
          :label="label || null"
          :title="helpTitle"
        >
          <slot name="help-message">
            {{ helpmsg }}
          </slot>
        </HelpIcon>
      </div>

      <FieldContextProvider
        :id="generatedId"
        :label-id="generatedLabelId"
        :aria-describedby="ariaDescribedbyId"
      >
        <div
          :class="{
            'tui-formRow__action': true,
            'tui-formRow__action--isStacked': isStacked,
          }"
        >
          <slot
            :id="generatedId"
            :labelId="generatedLabelId"
            :label="label"
            :ariaDescribedby="ariaDescribedbyId"
            :ariaLabel="ariaLabel"
          />
        </div>
      </FieldContextProvider>
    </div>
  </div>
</template>

<script>
// Components
import HelpIcon from 'tui/components/form/HelpIcon';
import Label from 'tui/components/form/Label';
import FieldContextProvider from 'tui/components/reform/FieldContextProvider';

export default {
  components: {
    HelpIcon,
    Label,
    FieldContextProvider,
  },

  props: {
    ariaDescribedby: String,
    labelLegend: Boolean,
    helpmsg: String,
    helpTitle: String,
    hidden: Boolean,
    accessibleLabel: String,
    id: String,
    label: String,
    required: Boolean,
    optional: Boolean,
    isStacked: {
      type: Boolean,
      default: true,
    },
    subfield: Boolean,
    vertical: Boolean,
    fullWidth: Boolean,
  },

  computed: {
    ariaDescribedbyId() {
      return this.helpmsg
        ? this.helpDescId +
            (this.ariaDescribedby ? ` ${this.ariaDescribedby}` : '')
        : this.ariaDescribedby;
    },
    ariaLabel() {
      return this.hidden ? this.label : null;
    },
    generatedId() {
      return this.id || this.$id();
    },
    generatedLabelId() {
      return this.$id('label');
    },
    helpDescId() {
      return this.generatedId + '-helpDesc';
    },
  },
};
</script>

<style lang="scss">
.tui-formRow {
  display: flex;
  flex-direction: column;

  // __inner is needed as setting margin on the root element is the
  // responsibility of the containing element (outside-in spacing).
  // (see below for margin setting)
  &__inner {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
  }

  &__inner > &__desc {
    min-width: 0;
    padding-top: var(--gap-1);
    padding-right: var(--gap-2);
    text-align: left;
    overflow-wrap: break-word;

    .tui-form--vertical &,
    .tui-formRow--vertical &,
    .tui-formRow--emptyDesc & {
      padding: 0;
    }
  }

  &__inner > &__action {
    display: flex;
    max-width: 71.2rem;

    .tui-form--vertical &,
    .tui-formRow--vertical & {
      margin-top: var(--gap-2);
    }

    .tui-formRow--emptyDesc & {
      margin-top: 0;
    }

    &--isStacked {
      display: block;
      flex-direction: column;

      @include tui-stack-vertical(var(--gap-2));
    }
  }

  &--fullWidth &__inner > &__action {
    max-width: none;
  }
}

.tui-form--horizontal .tui-formRow:not(.tui-formRow--vertical) > {
  .tui-formRow {
    &__inner {
      @include tui-layout-sidebar(
        $side-width: 22rem,
        $content-min-width: 60%,
        $gutter: var(--gap-1),
        $sidebar-selector: '.tui-formRow__desc',
        $content-selector: '.tui-formRow__action'
      );
    }
  }
}
</style>
