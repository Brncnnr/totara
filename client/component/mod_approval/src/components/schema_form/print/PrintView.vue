<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div :id="uid" class="tui-mod_approval-printView">
    <div class="tui-mod_approval-printView__document">
      <slot name="document" />
    </div>
    <div class="tui-mod_approval-printView__actionButtons">
      <Button :text="$str('print', 'mod_approval')" @click="print" />
    </div>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Button,
  },

  props: {
    schema: {
      type: Object,
      required: true,
    },
  },

  computed: {
    layout() {
      return this.schema.print_layout;
    },

    layoutOptions() {
      return (this.layout && this.layout.options && this.layout.options) || {};
    },

    css() {
      const width =
        this.layoutOptions.paper_size && this.layoutOptions.paper_size.width;
      return width
        ? `@media screen { #${this.uid} .tui-mod_approval-printView__document { width: ${width}mm; } }`
        : '';
    },
  },

  watch: {
    css() {
      this.$_updateCSS();
    },
  },

  mounted() {
    this.$_updateCSS();
  },

  beforeDestroy() {
    if (this.styleEl) {
      this.styleEl.remove();
    }
  },

  methods: {
    print() {
      window.print();
    },

    $_updateCSS() {
      if (!this.styleEl && !this.css) {
        return;
      }
      if (!this.styleEl) {
        this.styleEl = document.createElement('style');
        document.head.appendChild(this.styleEl);
      }
      this.styleEl.innerHTML = this.css;
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "print"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-printView {
  &__document {
    @media screen {
      width: 210mm;
      margin: var(--gap-8) auto calc(var(--gap-10) * 2) auto;
      padding: 10mm;
      border: var(--border-width-thin) solid var(--color-border);
      box-shadow: var(--shadow-4);
    }

    @media print {
      &__actionButtons {
        display: none;
      }
    }
  }

  &__actionButtons {
    position: fixed;
    bottom: 0;
    left: 0;
    display: flex;
    justify-content: center;
    width: 100%;
    padding: var(--gap-2) 0;
    background: rgba(247, 247, 247, 0.8);

    @media print {
      display: none;
    }
  }
}
</style>
