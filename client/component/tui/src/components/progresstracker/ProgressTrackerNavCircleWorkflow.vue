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
    class="
      tui-progressTrackerNavCircleWorkflow
      tui-progressTrackerNavCircleWorkflow__outer
    "
    :class="{
      'tui-progressTrackerNavCircleWorkflow--ready': ready,
      'tui-progressTrackerNavCircleWorkflow--done': done,
      'tui-progressTrackerNavCircleWorkflow--selected': selected,
      'tui-progressTrackerNavCircleWorkflow--locked': locked,
      'tui-progressTrackerNavCircleWorkflow--optional': optional,
      'tui-progressTrackerNavCircleWorkflow--invalid': invalid,
      'tui-progressTrackerNavCircleWorkflow--view-only': viewOnly,
    }"
    :aria-hidden="!isInteractable"
    :role="ariaRole"
    :tabindex="isInteractable ? '0' : '-1'"
    @keypress="handleKeypress($event)"
  >
    <div class="tui-progressTrackerNavCircleWorkflow__middle">
      <div class="tui-progressTrackerNavCircleWorkflow__inner">
        <span
          v-if="isInteractable"
          class="tui-progressTrackerNavCircleWorkflow__label"
        >
          {{ $str('a11y_progresstracker_action', 'totara_tui') }}
        </span>
        <template v-if="$scopedSlots.icon">
          <slot name="icon" />
        </template>
        <template v-else>
          <ViewOnlyIcon
            v-if="viewOnly"
            :alt="$str('completionstatus_view_only', 'totara_tui')"
            :size="100"
          />
          <LockIcon
            v-if="locked"
            :alt="$str('completionstatus_locked', 'totara_tui')"
            :size="100"
            class="tui-progressTrackerNavCircleWorkflow__icon--locked"
          />
          <SuccessIcon
            v-else-if="done"
            :alt="$str('completionstatus_done', 'totara_tui')"
            :size="100"
            class="tui-progressTrackerNavCircleWorkflow__icon--done"
          />
          <InvalidIcon
            v-else-if="invalid"
            :alt="$str('completionstatus_invalid', 'totara_tui')"
            :size="100"
            class="tui-progressTrackerNavCircleWorkflow__icon--invalid"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import ViewOnlyIcon from 'tui/components/icons/ViewOnly';
import LockIcon from 'tui/components/icons/Lock';
import SuccessIcon from 'tui/components/icons/Success';
import InvalidIcon from 'tui/components/icons/Invalid';
export default {
  components: {
    ViewOnlyIcon,
    LockIcon,
    SuccessIcon,
    InvalidIcon,
  },
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
      default: () => ['ready'],
      type: Array,
      validator: function(values) {
        const allowedOptions = [
          'ready',
          'done',
          'selected',
          'locked',
          'optional',
          'invalid',
          'view_only',
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
    ready() {
      return this.states.includes('ready');
    },
    done() {
      return this.states.includes('done');
    },
    selected() {
      return this.states.includes('selected');
    },
    locked() {
      return this.states.includes('locked');
    },
    optional() {
      return this.states.includes('optional');
    },
    invalid() {
      return this.states.includes('invalid');
    },
    viewOnly() {
      return this.states.includes('view_only');
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
.tui-progressTrackerNavCircleWorkflow {
  // states
  $ready: #{&}--ready;
  $locked: #{&}--locked;
  $optional: #{&}--optional;
  $selected: #{&}--selected;
  $done: #{&}--done;
  $invalid: #{&}--invalid;
  $view-only: #{&}--view-only;

  &__outer {
    z-index: 2;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: var(--progresstracker-full-marker-size);
    height: var(--progresstracker-full-marker-size);
    border: var(--border-width-normal) transparent none;
    border-radius: 50%;

    /**
     * states
     **/

    &#{$selected} {
      border-color: var(--progresstracker-color-selected);
      border-style: solid;
    }

    &#{$ready} {
      border-color: var(--progresstracker-color-ready);
    }

    &#{$locked} {
      border-color: var(--progresstracker-color-locked);
    }

    &#{$done} {
      border-color: var(--progresstracker-color-done);
    }

    &#{$optional} {
      border-color: var(--progresstracker-color-optional);
    }

    &#{$invalid} {
      border-color: var(--progresstracker-color-invalid);
    }
  }

  &__middle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: calc(var(--progresstracker-full-marker-size) - var(--gap-2));
    height: calc(var(--progresstracker-full-marker-size) - var(--gap-2));
    background-color: transparent;
    border: var(--border-width-thin) none transparent;
    border-radius: 50%;

    /**
     * states
     **/

    #{$selected} & {
      background: var(--progresstracker-color-selected);
      border-color: var(--progresstracker-color-selected);
    }

    #{$ready} & {
      background: var(--progresstracker-color-ready--inverse);
      border-color: var(--progresstracker-color-ready);
      border-style: solid;
    }

    #{$locked} & {
      background: var(--progresstracker-color-locked--inverse);
      border-color: var(--progresstracker-color-locked);
      border-style: solid;
    }

    #{$done} & {
      background: var(--progresstracker-color-done);
      border-color: var(--progresstracker-color-done);
    }

    #{$optional} & {
      background: var(--progresstracker-color-optional--inverse);
      border-color: var(--progresstracker-color-optional);
      border-style: dashed;
    }

    #{$invalid} & {
      background: var(--progresstracker-color-invalid);
      border-color: var(--progresstracker-color-invalid);
    }

    #{$view-only} & {
      border-color: var(--progresstracker-color-view-only);
      border-style: solid;
    }

    #{$ready}#{$selected} & {
      background: var(--progresstracker-color-ready);
    }

    #{$locked}#{$selected} & {
      background: var(--progresstracker-color-locked);
    }

    #{$optional}#{$selected} & {
      background: var(--progresstracker-color-optional);
      border-color: var(--progresstracker-container-bg-color);
      border-style: dashed;
    }

    #{$view-only}#{$selected} & {
      background: var(--progresstracker-color-view-only);
    }
  }

  &__inner {
    display: flex;
    align-items: center;
    justify-content: center;
    width: var(--gap-5);
    height: var(--gap-5);
    border-radius: 50%;

    /**
     * states
     **/

    #{$selected} & {
      color: var(--progresstracker-color-selected--inverse);
    }

    #{$ready} & {
      color: var(--progresstracker-color-ready);
    }

    #{$locked} & {
      color: var(--progresstracker-color-locked);
    }

    #{$done} & {
      color: var(--progresstracker-color-done--inverse);
    }

    #{$optional} & {
      color: var(--progresstracker-color-optional);
    }

    #{$invalid} & {
      color: var(--progresstracker-color-invalid--inverse);
    }

    #{$view-only} & {
      color: var(--progresstracker-color-view-only);
    }

    #{$ready}#{$selected} & {
      color: var(--progresstracker-color-ready--inverse);
    }

    #{$locked}#{$selected} & {
      color: var(--progresstracker-color-locked--inverse);
    }

    #{$optional}#{$selected} & {
      color: var(--progresstracker-color-optional--inverse);
    }

    #{$view-only}#{$selected} & {
      color: var(--progresstracker-color-view-only--inverse);
    }
  }

  &__icon--locked {
    width: 1.2rem;
    height: 1.2rem;
    margin-bottom: 2px;
  }

  &__icon--done {
    width: 1.6rem;
    height: 1.6rem;
    margin-top: 2px;
  }

  &__icon--invalid {
    width: 1.4rem;
    height: 1.4rem;
    margin-bottom: 3px;
  }

  &__label {
    @include sr-only();
  }
}
</style>

<lang-strings>
{
  "totara_tui": [
    "completionstatus_done",
    "completionstatus_locked",
    "completionstatus_invalid",
    "completionstatus_view_only",
    "a11y_progresstracker_action"
  ]
}
</lang-strings>
