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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div
    ref="interactable"
    class="tui-progressTrackerNavCircleAchievement tui-progressTrackerNavCircleAchievement__outer"
    :class="{
      'tui-progressTrackerNavCircleAchievement--pending': pending,
      'tui-progressTrackerNavCircleAchievement--target': target,
      'tui-progressTrackerNavCircleAchievement--complete': complete,
      'tui-progressTrackerNavCircleAchievement--achieved': achieved,
      'tui-progressTrackerNavCircleAchievement--current': current,
    }"
    :aria-hidden="!isInteractable"
    :role="ariaRole"
    :tabindex="isInteractable ? '0' : '-1'"
    @keypress="handleKeypress($event)"
  >
    <div class="tui-progressTrackerNavCircleAchievement__middle">
      <div class="tui-progressTrackerNavCircleAchievement__inner">
        <span
          v-if="isInteractable"
          class="tui-progressTrackerNavCircleAchievement__label"
        >
          {{ $str('a11y_progresstracker_action', 'totara_tui') }}
        </span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    ariaRole: {
      default: 'button',
      type: String,
      validator: function(value) {
        const allowedOptions = ['button', 'link', 'none'];
        return allowedOptions.includes(value);
      },
    },
    isInteractable: {
      default: true,
      type: Boolean,
    },
    states: {
      default: () => ['pending'],
      type: Array,
      validator: function(values) {
        const allowedOptions = [
          'pending',
          'target',
          'complete',
          'achieved',
          'current',
        ];
        // warn on invalid state found within the supplied Array
        return !values.filter(value => {
          return allowedOptions.indexOf(value) === -1;
        }).length;
      },
    },
  },
  data() {
    return {
      open: false,
    };
  },
  computed: {
    pending() {
      return this.states.includes('pending');
    },
    target() {
      return this.states.includes('target');
    },
    complete() {
      return this.states.includes('complete');
    },
    achieved() {
      return this.states.includes('achieved');
    },
    current() {
      return this.states.includes('current');
    },
  },

  methods: {
    handleKeypress(e) {
      if (
        this.isInteractable &&
        ['enter', 'spacebar', ' '].includes(e.key.toLowerCase())
      ) {
        this.$refs.interactable.click();
      }
      return e.preventDefault();
    },
  },
};
</script>

<style lang="scss">
.tui-progressTrackerNavCircleAchievement {
  // states
  $pending: #{&}--pending;
  $complete: #{&}--complete;
  $achieved: #{&}--achieved;
  $target: #{&}--target;
  $current: #{&}--current;

  &__outer {
    z-index: 2;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: var(--progresstracker-full-marker-size);
    height: var(--progresstracker-full-marker-size);
    border: var(--border-width-normal) transparent dotted;
    border-radius: 50%;

    /**
     * states
     **/
    &#{$pending}&#{$target} {
      border-color: var(--progresstracker-color-pending);
    }

    &#{$target}&#{$achieved} {
      background: var(--progresstracker-container-bg-color);
      border-color: var(--progresstracker-color-achieved);
      border-style: solid;
    }
  }

  &__middle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: calc(
      calc(var(--progresstracker-full-marker-size) / 2) + var(--gap-1)
    );
    height: calc(
      calc(var(--progresstracker-full-marker-size) / 2) + var(--gap-1)
    );
    background: transparent;
    border: var(--border-width-thin) solid transparent;
    border-radius: 50%;
    -webkit-print-color-adjust: exact;
    color-adjust: exact;

    /**
     * states
     **/
    #{$pending} & {
      background: var(--progresstracker-color-pending);
    }

    #{$pending}#{$target} & {
      background: transparent;
    }

    #{$complete} & {
      background: var(--progresstracker-color-complete);
    }

    #{$achieved} & {
      background: var(--progresstracker-color-achieved);
    }
  }

  &__inner {
    display: flex;
    align-items: center;
    width: calc(
      calc(var(--progresstracker-full-marker-size) / 4) +
        var(--border-width-thin)
    );
    height: calc(
      calc(var(--progresstracker-full-marker-size) / 4) +
        var(--border-width-thin)
    );
    background: var(--progresstracker-container-bg-color);
    border: var(--border-width-thin) solid
      var(--progresstracker-container-bg-color);
    border-radius: 50%;

    /**
     * states
     **/
    #{$pending} & {
      border-color: var(--progresstracker-container-bg-color);
    }

    #{$pending}#{$target} & {
      border-color: var(--progresstracker-color-pending);
    }

    #{$achieved} & {
      border-color: var(--progresstracker-container-bg-color);
    }
  }
  &__label {
    @include sr-only();
  }
}
.ie,
.msedge {
  .tui-progressTrackerCircle--achieved {
    .tui-progressTrackerCircle {
      &__middle {
        border: var(--gap-1) solid var(--progresstracker-color-achieved);
      }
    }
  }

  .tui-progressTrackerCircle--complete {
    .tui-progressTrackerCircle {
      &__middle {
        border: var(--gap-1) solid var(--progresstracker-color-complete);
      }
    }
  }
}
</style>

<lang-strings>
{
"totara_tui": [
"a11y_progresstracker_action"
]
}
</lang-strings>
