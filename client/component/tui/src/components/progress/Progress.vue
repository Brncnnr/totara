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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module tui
-->

<template>
  <div
    ref="progress"
    class="tui-progress"
    :class="{
      'tui-progress--small': small,
      'tui-progress--hideBackground': hideBackground,
    }"
    :aria-valuetext="valueText"
    :aria-valuenow="value"
    :aria-valuemin="min"
    :aria-valuemax="max"
    role="progressbar"
  >
    <div ref="progress-bar" class="tui-progress__bar" :style="progressStyle">
      <span
        ref="progressLabel"
        class="tui-progress__label"
        :class="!hideValue && insideLabel && 'tui-progress__label--inside'"
        >{{ valueText }}</span
      >
    </div>
    <span
      v-show="!hideValue && !insideLabel"
      class="tui-progress__label tui-progress__label--outside"
      >{{ valueText }}</span
    >
  </div>
</template>

<script>
export default {
  props: {
    small: {
      type: Boolean,
      default: false,
    },
    value: {
      type: Number,
      default: 0,
    },
    min: {
      type: Number,
      default: 0,
    },
    max: {
      type: Number,
      default: 100,
    },
    hideValue: {
      type: Boolean,
      default: false,
    },
    format: {
      type: String,
      default: 'percent',
      validator: f => ['number', 'percent'].includes(f),
    },
    completedText: {
      type: [Boolean, String],
      default: false,
    },
    hideBackground: {
      type: Boolean,
      default: false,
    },
    showEmptyState: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      progressStyle: {},
      insideLabel: false,
    };
  },
  computed: {
    fraction() {
      if (this.value > this.max) {
        return 1;
      }
      if (this.value < this.min) {
        return 0;
      }
      return Number((this.value - this.min) / (this.max - this.min));
    },
    valueText() {
      if (this.completedText && this.value === this.max) {
        return typeof this.completedText === 'string'
          ? this.completedText
          : this.$str('completed', 'totara_core');
      }

      if (this.format === 'percent') {
        return `${(this.fraction * 100).toFixed(0)}%`;
      }

      return this.value;
    },
  },
  watch: {
    value: {
      immediate: true,
      handler() {
        this.$_setProgressStyle();

        if (!this.hideValue) {
          this.$nextTick(() => {
            this.$_setLabelStyle();
          });
        }
      },
    },
  },
  methods: {
    $_setLabelStyle() {
      const containerW = this.$refs.progress.offsetWidth;
      const labelW = this.$refs.progressLabel.offsetWidth;

      this.insideLabel = labelW < Math.floor(containerW * this.fraction);
    },
    $_setProgressStyle() {
      let w,
        bgColor = '';

      //handle empty state styling
      if (this.showEmptyState && this.value <= this.min) {
        w = '2%';
        bgColor = '#e6e4e4';
      } else {
        w = `${this.fraction * 100}%`;
      }

      this.progressStyle = {
        width: w,
        backgroundColor: bgColor,
      };
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "completed"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-progress {
  @include tui-font-body-xx-small();

  display: flex;
  height: 14px;
  background-color: var(--progress-container-bg-color);
  border: 1px solid var(--progress-border-color);
  border-radius: var(--gap-2);

  &--small {
    height: 12px;
    line-height: 10px;
  }

  &--hideBackground {
    background: none;
  }

  &__bar {
    display: flex;
    justify-content: flex-end;
    background-color: var(--progress-value-bg-color);
    border-radius: var(--gap-2);
    transition: width var(--transition-progress-function)
      var(--transition-progress-duration);
  }

  &__label {
    padding: 0 var(--gap-1);
    opacity: 0;

    &--inside {
      color: var(--progress-value-text-color);
      opacity: 1;
      transition: opacity var(--transition-progress-function)
        var(--transition-progress-duration);
    }
    &--outside {
      color: var(--progress-container-text-color);
      opacity: 1;
    }
  }
}
</style>
