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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui

-->

<template>
  <li
    class="tui-progressTrackerNav__item"
    :class="[
      {
        'tui-progressTrackerNav__item--pending': pending,
        'tui-progressTrackerNav__item--target': target,
        'tui-progressTrackerNav__item--complete': complete,
        'tui-progressTrackerNav__item--achieved': achieved,
        'tui-progressTrackerNav__item--current': current,
        'tui-progressTrackerNav__item--ready': ready,
        'tui-progressTrackerNav__item--done': done,
        'tui-progressTrackerNav__item--selected': selected,
        'tui-progressTrackerNav__item--locked': locked,
        'tui-progressTrackerNav__item--optional': optional,
        'tui-progressTrackerNav__item--invalid': invalid,
        'tui-progressTrackerNav__item--view-only': viewOnly,
      },
      connectorStateClasses,
    ]"
  >
    <div class="tui-progressTrackerNav__itemContentWrapper">
      <PopoverTrigger
        :triggers="popoverTriggerType"
        :ui-element="$refs.popover"
        @open-changed="setOpen"
      >
        <ProgressTrackerNavCircleAchievement
          v-if="markerMode === 'achievement'"
          ref="trigger"
          :states="states"
          :is-interactable="hasPopoverTrigger"
          :aria-role="hasPopoverTrigger ? undefined : 'none'"
        />
        <ProgressTrackerNavCircleWorkflow
          v-else-if="markerMode === 'workflow'"
          ref="trigger"
          :states="states"
          :is-interactable="hasPopoverTrigger"
          :aria-role="hasPopoverTrigger ? undefined : 'none'"
        >
          <template v-if="$scopedSlots.icon" v-slot:icon>
            <slot name="icon" />
          </template>
        </ProgressTrackerNavCircleWorkflow>
      </PopoverTrigger>

      <div
        class="tui-progressTrackerNav__itemContent"
        :class="[
          {
            'tui-progressTrackerNav__itemContent--full-width': itemContentFullWidth,
            'tui-progressTrackerNav__itemContent--overflow-hidden': itemContentOverflowHidden,
          },
          'tui-progressTrackerNav__itemContent--gap-' + gap,
          'tui-progressTrackerNav__itemContent--size-' + size,
        ]"
      >
        <!-- optional label -->
        <div
          v-if="label"
          class="tui-progressTrackerNav__itemLabel"
          :class="{ 'tui-progressTrackerNavItem__itemLabel--current': current }"
        >
          <PopoverTrigger
            v-if="labelOpensPopover"
            :triggers="popoverTriggerType"
            :ui-element="$refs.popover"
            @open-changed="setOpen"
          >
            <Button :text="label" :styleclass="{ transparent: true }" />
          </PopoverTrigger>
          <div v-else class="tui-progressTrackerNav__itemLabelText">
            {{ label }}
          </div>
        </div>

        <p class="tui-progressTrackerNav__itemStatus">
          {{ stateString || defaultStateString }}
        </p>

        <!-- optional generic content -->
        <slot />
      </div>

      <Popover
        ref="popover"
        :triggers="popoverTriggerType"
        :open="open"
        :reference="$refs.trigger"
        :context-mode="uncontainedPopover ? 'uncontained' : 'contained'"
        position="top"
        size="sm"
        closeable="always"
        @request-close="setOpen(false)"
      >
        <slot name="popover-content" />
      </Popover>
    </div>
  </li>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Popover from 'tui/components/popover/Popover';
import ProgressTrackerNavCircleAchievement from 'tui/components/progresstracker/ProgressTrackerNavCircleAchievement';
import ProgressTrackerNavCircleWorkflow from 'tui/components/progresstracker/ProgressTrackerNavCircleWorkflow';
import PopoverTrigger from 'tui/components/popover/PopoverTrigger';

export default {
  components: {
    Button,
    Popover,
    ProgressTrackerNavCircleAchievement,
    ProgressTrackerNavCircleWorkflow,
    PopoverTrigger,
  },

  props: {
    uncontainedPopover: {
      type: Boolean,
      default: false,
    },
    id: [String, Number],
    /**
     * If this prop is supplied, pre-defined label output is rendered using the
     * supplied text
     **/

    /**
     * Forces the item content to have 100% width
     */
    itemContentFullWidth: Boolean,

    /**
     * Adds 'overflow: hidden' to the item content
     */
    itemContentOverflowHidden: Boolean,

    label: String,
    /**
     * If this value is true then interacting with a rendered label will display
     * supplied label popover text
     **/
    labelOpensPopover: Boolean,
    /**
     * Which user interactions trigger the label popover, if there is one to
     * display
     **/
    popoverTriggerType: {
      default: () => ['click'],
      type: Array,
    },
    /**
     * Accessible state information for non-sighted users
     **/
    stateString: String,
    /**
     * Two paths to choose from, renders a different visual marker that each
     * handle their own possible states
     **/
    markerMode: {
      default: 'achievement',
      type: String,
      validator: function(value) {
        return ['achievement', 'workflow'].includes(value);
      },
    },
    /**
     * Whitespace gap between items
     **/
    gap: {
      default: 'medium',
      type: String,
      validator: function(value) {
        const allowedOptions = ['small', 'medium', 'large'];
        return allowedOptions.includes(value);
      },
    },
    /**
     * The visual size for each progress item - in horizontal mode only, where
     * we need to limit width to avoid breaking layouts
     **/
    size: {
      type: String,
      default: 'medium',
    },
    /**
     * The current state of a given item, these states are applied to items as
     * CSS modifiers to apply specific progress style meaning for sighted users
     **/
    states: {
      default: () => ['pending'],
      type: Array,
      validator: function(values) {
        const allowedOptions = [
          // related to achievement
          'pending',
          'target',
          'complete',
          'achieved',
          'current',
          // related to workflows
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
    /**
     * Similar to `states` but the CSS modifiers applied to a connector (which
     * is a pseudo element that visually touches two progress markers) uses the
     * state values from the item ahead of the current one.
     **/
    connectorStates: {
      default: () => [],
      type: Array,
    },
  },

  data() {
    return {
      open: false,
    };
  },

  computed: {
    // related to achievement
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
    // related to workflows
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
    /**
     * Determines the connector line visual states based on the *next* item
     * after this current one
     *
     * @return {Array}
     **/
    connectorStateClasses() {
      return this.connectorStates.map(state => {
        return 'tui-progressTrackerNav__connector--' + state;
      });
    },

    /**
     * Returns a default a11y state string for progress items, based on their
     * state Array entries
     *
     * @return {String}
     **/
    defaultStateString() {
      let strVar = this.states
        .map(state => {
          return this.$str(`completionstatus_${state}`, 'totara_tui');
        })
        .join(', ');

      return this.$str('ally_status_with_value', 'totara_core', strVar);
    },

    /**
     * @return {boolean}
     **/
    hasPopoverTrigger() {
      return this.popoverTriggerType.length > 0;
    },
  },

  methods: {
    setOpen(value) {
      this.open = value;
    },
  },
};
</script>

<style lang="scss">
$progressTracker-half-marker-size: calc(
  var(--progresstracker-full-marker-size) / 2
) !default;

/**
  * Connector lines
  **/
.tui-progressTrackerNav__itemContentWrapper:before {
  position: absolute;
  display: block;
  border-color: var(--progresstracker-color-pending);
  border-style: dotted;
  content: '';

  .tui-progressTrackerNav--vertical & {
    top: calc(
      var(--progresstracker-full-marker-size) + var(--border-width-normal)
    );
    /* nested calc() will get botched by RTLCSS processing if it is broken down onto multiple lines */
    /* prettier-ignore */
    left: calc( #{$progressTracker-half-marker-size} - calc(var(--border-width-normal) / 2) );
    width: var(--border-width-normal);
    /* prettier-ignore */
    height: calc( 100% - calc( var(--progresstracker-full-marker-size) + calc(var(--border-width-normal) * 2) ) );
    border-width: 0 0 0 var(--border-width-normal);
  }
  .tui-progressTrackerNav--horizontal & {
    /* nested calc() will get botched by RTLCSS processing if it is broken down onto multiple lines */
    /* prettier-ignore */
    top: calc( #{$progressTracker-half-marker-size} - calc(var(--border-width-normal) / 2) );
    /* prettier-ignore */
    left: calc(50% + #{$progressTracker-half-marker-size} + var(--progresstracker-connector-gap) - var(--border-width-normal));
    /* prettier-ignore */
    width: calc( 100% - calc( var(--progresstracker-full-marker-size) + calc(var(--progresstracker-connector-gap)) + var(--border-width-normal)));
    height: var(--border-width-normal);
    border-width: var(--border-width-normal) 0 0 0;
  }
}

/**
  * Connector line modifiers
  **/
.tui-progressTrackerNav__connector {
  // item states related to Achievement
  &--pending {
    // this is the default state if no state Array is passed into the item
    .tui-progressTrackerNav__itemContentWrapper:before {
      border-color: var(--progresstracker-color-pending);
    }
  }

  &--complete {
    .tui-progressTrackerNav__itemContentWrapper:before {
      border-color: var(--progresstracker-color-complete);
      border-style: solid;
    }
  }

  &--achieved {
    .tui-progressTrackerNav__itemContentWrapper:before {
      border-color: var(--progresstracker-color-achieved);
      border-style: solid;
    }
  }
}

/**
 * Items and their content
 **/
.tui-progressTrackerNav {
  &__item {
    position: relative;
    flex-shrink: 0;

    &:last-child {
      .tui-progressTrackerNav__itemContentWrapper:before {
        display: none;
      }
    }

    /**
    * Connector gap adjustment for large item markers
    **/
    &--selected,
    &--target {
      .tui-progressTrackerNav__itemContentWrapper:after {
        position: absolute;
        display: block;
        /* nested calc() will get botched by RTLCSS processing if it is broken down onto multiple lines */
        /* prettier-ignore */
        width: calc( var(--progresstracker-full-marker-size) + calc(var(--progresstracker-connector-gap) * 2) );
        /* prettier-ignore */
        height: calc( var(--progresstracker-full-marker-size) + calc(var(--progresstracker-connector-gap) * 2) );
        border-color: var(--progresstracker-container-bg-color);
        border-style: solid;
        border-width: var(--progresstracker-connector-gap);
        border-radius: 50%;
        content: '';

        .tui-progressTrackerNav--horizontal & {
          top: calc(var(--progresstracker-connector-gap) * -1);
          left: calc(
            50% - #{$progressTracker-half-marker-size} - var(--progresstracker-connector-gap)
          );
        }
        .tui-progressTrackerNav--vertical & {
          top: calc(var(--progresstracker-connector-gap) * -1);
          left: calc(var(--progresstracker-connector-gap) * -1);
        }
      }
    }
  }

  &__itemContentWrapper {
    position: relative;
    display: flex;

    .tui-progressTrackerNav--vertical & {
      flex-direction: row;
      align-items: stretch;
      height: 100%;
    }
    .tui-progressTrackerNav--horizontal & {
      flex-direction: column;
      align-items: center;
    }
  }

  &__itemContent {
    display: flex;
    flex-direction: column;

    &--full-width {
      width: 100%;
    }

    &--overflow-hidden {
      overflow: hidden;
    }

    .tui-progressTrackerNav--vertical & {
      padding-left: var(--gap-2);

      /**
      * variable width and gap sizes based on supplied props
      **/
      &--gap-small {
        padding-bottom: var(--gap-2);
      }

      &--gap-medium {
        padding-bottom: var(--gap-5);
      }

      &--gap-large {
        padding-bottom: var(--gap-7);
      }
    }

    .tui-progressTrackerNav--horizontal & {
      align-items: center;

      /**
        * variable width and gap sizes based on supplied props
        **/
      &--gap-small {
        padding-right: var(--progress-tracker-horizontal-gap--small);
        padding-left: var(--progress-tracker-horizontal-gap--small);
      }
      &--size-small {
        width: var(--progress-tracker-horizontal-size--small);
      }

      &--gap-medium {
        padding-right: var(--progress-tracker-horizontal-gap--medium);
        padding-left: var(--progress-tracker-horizontal-gap--medium);
      }
      &--size-medium {
        width: var(--progress-tracker-horizontal-size--medium);
      }

      &--gap-large {
        padding-right: var(--progress-tracker-horizontal-gap--large);
        padding-left: var(--progress-tracker-horizontal-gap--large);
      }
      &--size-large {
        width: var(--progress-tracker-horizontal-size--large);
      }
    }
  }

  &__itemLabel {
    position: relative;
    width: 100%;
    margin: 0;
    padding-top: var(--gap-1);
    padding-bottom: var(--gap-1);

    .tui-progressTrackerNav--horizontal & {
      text-align: center;
    }

    &--current {
      .tui-formBtn {
        color: var(--color-state-active);
        font-weight: bold;
      }
    }
  }

  &__itemStatus {
    @include sr-only();
  }
}
</style>

<lang-strings>
{
  "totara_core": [
    "ally_status_with_value"
  ],
  "totara_tui": [
    "completionstatus_achieved",
    "completionstatus_complete",
    "completionstatus_current",
    "completionstatus_done",
    "completionstatus_invalid",
    "completionstatus_locked",
    "completionstatus_optional",
    "completionstatus_pending",
    "completionstatus_ready",
    "completionstatus_selected",
    "completionstatus_target",
    "completionstatus_view_only"
  ]
}
</lang-strings>
