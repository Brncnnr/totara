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
  <li
    class="tui-progressTrackerItem"
    :class="[
      'tui-progressTrackerItem--' + gap,
      'tui-progressTrackerItem--' + state,
    ]"
    :aria-current="current"
  >
    <div class="tui-progressTrackerItem__content">
      <p v-if="target" class="tui-progressTrackerItem__target">
        {{ targetItemString }}
      </p>

      <PopoverTrigger
        :triggers="popoverTriggerType"
        :ui-element="$refs.popover"
        @open-changed="setOpen"
      >
        <ProgressTrackerCircle ref="trigger" :state="state" :target="target" />
      </PopoverTrigger>

      <PopoverTrigger
        :triggers="popoverTriggerType"
        :ui-element="$refs.popover"
        @open-changed="setOpen"
      >
        <div
          class="tui-progressTrackerItem__label"
          :class="{ 'tui-progressTrackerItem__label-current': current }"
        >
          <Button :text="label" :styleclass="{ transparent: true }" />
        </div>
      </PopoverTrigger>

      <p class="tui-progressTrackerItem__status">
        {{ $str('status', 'core') + ': ' + stateString }}
      </p>
    </div>
    <Popover
      ref="popover"
      :triggers="popoverTriggerType"
      :open="open"
      :reference="$refs.trigger"
      position="top"
      size="sm"
      closeable="always"
      @request-close="setOpen(false)"
    >
      <slot name="popover-content" />
    </Popover>
  </li>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Popover from 'tui/components/popover/Popover';
import ProgressTrackerCircle from 'tui/components/progresstracker/ProgressTrackerCircle';
import PopoverTrigger from 'tui/components/popover/PopoverTrigger';

export default {
  components: {
    Button,
    Popover,
    ProgressTrackerCircle,
    PopoverTrigger,
  },

  props: {
    achievedGoalString: {
      type: String,
    },
    popoverTriggerType: {
      default: () => ['click'],
      type: Array,
    },
    current: {
      type: Boolean,
      default: false,
    },
    label: {
      type: String,
    },
    gap: {
      default: 'medium',
      type: String,
      validator: function(value) {
        const allowedOptions = ['small', 'medium', 'large'];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
    state: {
      default: 'pending',
      type: String,
      validator: function(value) {
        const allowedOptions = ['pending', 'complete', 'achieved'];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
    target: {
      default: false,
      type: Boolean,
    },
    targetString: {
      type: String,
    },
  },

  data() {
    return {
      open: false,
    };
  },

  computed: {
    /**
     * Accessibility string for state
     *
     * @return {Array}
     */
    stateString() {
      if (this.state === 'pending') {
        return this.$str('completion-n', 'completion');
      } else if (this.state === 'complete') {
        return this.$str('completion-y', 'completion');
      } else {
        return (
          this.achievedGoalString ||
          this.$str('a11yachievedrequiredgoal', 'totara_core')
        );
      }
    },
    /**
     * Accessibility string for target
     *
     * @return {String}
     */
    targetItemString() {
      return (
        this.targetString || this.$str('a11yachievementtarget', 'totara_core')
      );
    },
  },

  methods: {
    setOpen(value) {
      this.open = value;
    },
  },
};
</script>

<lang-strings>
{
  "completion": [
    "completion-n",
    "completion-y"
  ],
  "core": [
    "status"
  ],
  "totara_core": [
    "a11yachievedrequiredgoal",
    "a11yachievementtarget"
  ]
}
</lang-strings>
