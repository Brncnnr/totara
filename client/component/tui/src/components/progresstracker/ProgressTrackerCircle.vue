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
  @deprecated Since Totara 15.0
-->

<template>
  <div
    class="tui-progressTrackerCircle tui-progressTrackerCircle__outer"
    :class="[
      state ? 'tui-progressTrackerCircle--' + state : '',
      target ? 'tui-progressTrackerCircle--target' : '',
    ]"
    aria-hidden="true"
  >
    <div class="tui-progressTrackerCircle__middle">
      <div class="tui-progressTrackerCircle__inner" />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    state: {
      default: 'pending',
      type: String,
    },
    target: Boolean,
  },
};
</script>

<style lang="scss">
.tui-progressTrackerCircle {
  $pending: #{&}--pending;
  $complete: #{&}--complete;
  $achieved: #{&}--achieved;
  $target: #{&}--target;

  &__outer {
    z-index: 2;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: calc(var(--gap-7) + 1px);
    height: calc(var(--gap-7) + 1px);
    border: var(--border-width-normal) transparent dotted;
    border-radius: 50%;

    // Pending target
    &#{$pending}&#{$target} {
      border-color: var(--progresstracker-color-pending);
    }

    // Achieved target
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
    width: calc(var(--gap-4) + 1px);
    height: calc(var(--gap-4) + 1px);
    background: transparent;
    border: var(--border-width-thin) solid transparent;
    border-radius: 50%;
    -webkit-print-color-adjust: exact;
    color-adjust: exact;

    // Pending
    #{$pending} & {
      background: var(--progresstracker-color-pending);
    }

    // Pending target
    #{$pending}#{$target} & {
      background: transparent;
    }

    // Complete
    #{$complete} & {
      background: var(--progresstracker-color-complete);
    }

    // Achieved
    #{$achieved} & {
      background: var(--progresstracker-color-achieved);
    }
  }

  &__inner {
    width: calc(var(--gap-2) + 1px);
    height: calc(var(--gap-2) + 1px);
    background: var(--progresstracker-container-bg-color);
    border: var(--border-width-thin) solid
      var(--progresstracker-container-bg-color);
    border-radius: 50%;

    // Pending
    #{$pending} & {
      border-color: var(--progresstracker-container-bg-color);
    }

    // Pending target
    #{$pending}#{$target} & {
      border-color: var(--progresstracker-color-pending);
    }

    // Achieved
    #{$achieved} & {
      border-color: var(--progresstracker-container-bg-color);
    }
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
