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
  <Responsive
    :breakpoints="[
      { name: 'vertical', boundaries: [0, minWidth] },
      { name: 'horizontal', boundaries: [minWidth + 1, 1600] },
    ]"
    :pause="forceVertical"
    @responsive-resize="resize"
  >
    <div
      ref="tui-progressTrackerNav"
      class="tui-progressTrackerNav"
      :class="[
        isVertical
          ? 'tui-progressTrackerNav--vertical'
          : 'tui-progressTrackerNav--horizontal',
      ]"
    >
      <ol class="tui-progressTrackerNav__items">
        <ProgressTrackerNavItem
          v-for="(item, index) in items"
          :id="item.id"
          :key="index"
          :item-content-full-width="itemContentFullWidth"
          :item-content-overflow-hidden="itemContentOverflowHidden"
          :gap="gap"
          :size="size"
          :label-opens-popover="labelOpensPopover"
          :label="item.label"
          :marker-mode="markerMode"
          :connector-states="items[index + 1] ? items[index + 1].states : []"
          :popover-trigger-type="popoverTriggerType"
          :uncontained-popover="uncontainedPopover"
          :states="item.states"
          :state-string="item.stateString"
        >
          <template>
            <slot :entry="item" :index="index" />
          </template>

          <template v-if="$scopedSlots.icon" v-slot:icon>
            <slot name="icon" />
          </template>

          <template v-if="$scopedSlots.label" v-slot:label>
            <slot name="label" />
          </template>

          <template v-slot:popover-content>
            <template v-if="!$scopedSlots['custom-popover-content']">
              <div v-html="item.description" />
            </template>
            <slot
              name="custom-popover-content"
              :description="item.description"
              :label="item.label"
              :target="item.target"
            />
          </template>
        </ProgressTrackerNavItem>
      </ol>
    </div>
  </Responsive>
</template>

<script>
import theme from 'tui/theme';
import Responsive from 'tui/components/responsive/Responsive';
import ProgressTrackerNavItem from 'tui/components/progresstracker/ProgressTrackerNavItem';

export default {
  components: {
    Responsive,
    ProgressTrackerNavItem,
  },

  props: {
    /**
     * The visual space between each progress item. Passed down to child
     * component.
     **/
    gap: {
      type: String,
      default: 'medium',
    },
    /**
     * The visual size for each progress item - in horizontal mode only, where
     * we need to limit width to avoid breaking layouts. Passed down to child
     * component.
     **/
    size: {
      type: String,
      default: 'medium',
    },
    /**
     * A data structure of progress items and their current state(s)
     **/
    items: {
      type: Array,
      required: true,
    },

    /**
     * Forces the progress tracker nav item content to have 100% width
     */
    itemContentFullWidth: Boolean,

    /**
     * Adds 'overflow: hidden' to the progress tracker nav item content
     */
    itemContentOverflowHidden: Boolean,

    /**
     * Forces the component into vertical mode and disables the responsive
     * wrapper to make a minor performance savings, as it doesn't need to flick
     * between vertical and horizontal
     **/
    forceVertical: Boolean,
    /**
     * Visual markers have different meanings when indicating achievement versus
     * workflow steps, they're currently different enough that we need a way to
     * make acute style differences based on this setting. Passed down to child
     * component.
     **/
    markerMode: {
      default: 'achievement',
      type: String,
      validator: mode => ['achievement', 'workflow'].includes(mode),
    },
    /**
     * If this value is true then interacting with a rendered label will display
     * supplied label popover text. Passed down to child component.
     **/
    labelOpensPopover: Boolean,
    /**
     * Which user interactions trigger the label popover, if there is one to
     * display. Passed down to child component.
     **/
    popoverTriggerType: Array,
    uncontainedPopover: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      isVertical: this.forceVertical || false,
      parentElement: null,
      maxWidth: 1600,
    };
  },

  computed: {
    /**
     * Used to determine container query resize boundary
     **/
    minWidth() {
      // what is the supplied value for the `width` of progress items?
      let unparsedValue = theme.getVar(
        'progress-tracker-horizontal-size--' + this.size
      );
      if (unparsedValue === null) {
        throw new Error('Could not find CSS Variable via theme.getVar()!');
      }

      // regex will output two capture groups, first group for the number
      // portion in a return String from theme.getVar(), and the second group
      // for the character portion
      let getValueAndUnit = new RegExp(/^([\d]+|[\d]+\.[\d]+)(rem|px)$/);

      // split the unparsed value into a number and a unit, filter out empty
      // Array indices that come along with split() usage
      let splitValue = unparsedValue
        .split(getValueAndUnit)
        .filter(v => v.length);

      // now we're going to determine a `px` value from potentially different
      // unit types retrieved from theme.getVar()
      let width = 0;
      let rootEmValue;
      let rawWidthValue = parseFloat(splitValue[0]);
      let unit = splitValue[1];
      let allowedUnits = ['rem', 'px'];

      if (
        !rawWidthValue ||
        typeof rawWidthValue !== 'number' ||
        !allowedUnits.includes(unit)
      ) {
        throw new Error(
          'Unable to use supplied CSS Variable value via theme.getVar()!'
        );
      }

      // for supported unit types, convert to a pixel equivalent because the
      // Responsive component expects only a Number
      switch (unit) {
        case 'rem':
          rootEmValue = parseFloat(
            getComputedStyle(document.documentElement).fontSize
          );
          width = rootEmValue * rawWidthValue;
          break;
        case 'px':
        // intentional fallthrough, same as default: do nothing, hopefully `px`
        default:
          break;
      }

      // multiply the converted width value for progress item by number of
      // progress items (they're all uniform in width)
      return (width *= this.items.length);
    },
  },

  mounted() {
    this.parentElement = this.$refs['tui-progressTrackerNav'];
  },

  methods: {
    /**
     * Handles responsive switching between vertical and horizontal mode
     **/
    resize() {
      this.maxWidth = this.getNewParentWidth();
      this.isVertical = this.minWidth > this.maxWidth;
    },

    getNewParentWidth() {
      if (!this.parentElement) return;
      return this.parentElement.getBoundingClientRect().width;
    },
  },
};
</script>

<style lang="scss">
.tui-progressTrackerNav__items {
  display: flex;
  justify-content: center;
  margin: 0;
  list-style: none;

  .tui-progressTrackerNav--vertical & {
    flex-direction: column;
  }
}
</style>
