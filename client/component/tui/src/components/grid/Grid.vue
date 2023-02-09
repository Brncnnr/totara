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
  @module tui
-->

<!-- No <template /> element, we will use this component's `render()` method to
     assemble GridItem contents, as we need to use some logic -->

<script>
import theme from 'tui/theme';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import { cloneVNode } from '../../js/internal/vnode';

export default {
  props: {
    /**
     * The tag for the Grid.
     **/
    gridTag: {
      type: String,
      validator: x => ['div', 'ul', 'ol'].includes(x),
      default: 'div',
    },
    /**
     * `horizontal` and `vertical` are the two expected values, the Grid works
     * in both situations. Vertical grids will need more attention paid to their
     * individual implementations because of limitations when an explicit CSS
     * `height` property is not applied.
     **/
    direction: {
      type: String,
      default: function() {
        return 'horizontal';
      },
    },
    /**
     * Applies, or excludes, a visual gap between grid items along the main flex
     * axis.
     **/
    useHorizontalGap: {
      type: Boolean,
      default: function() {
        return true;
      },
    },
    /**
     * Applies, or excludes, a visual gap between grid items along the cross
     * flex axis.
     **/
    useVerticalGap: {
      type: Boolean,
      default: function() {
        return true;
      },
    },
    /**
     * This property is used to calculate GridItem widths. It can be exceeded
     * both by the number of GridItem children and also by the total number of
     * units that each GridItem takes up. While not ideal, if the value is
     * exceeded, excess GridItems will wrap.
     **/
    maxUnits: {
      type: [String, Number], // theme.getVar() always returns a String
      default: function() {
        return theme.getVar('grid-maxunits');
      },
    },
    /**
     * When a value is supplied, a className is appended to the Grid which
     * provides a basic switch from a flex layout to a stack of Block elements.
     * Note that this does not use a viewport breakpoint, it uses the width of
     * the Grid itself - this is 'container query'-like behaviour.
     **/
    stackAt: {
      type: Number,
    },
  },

  data: function() {
    return {
      isStacked: false,
      resizeObserverRef: null,
    };
  },

  mounted() {
    // when mounted, create a resize observer to detect changes in dimensions,
    // this will facilitate responsiveness to a finer level than relying solely
    // on viewport width. this technique is referred to as a 'container query'
    // and is useful when you want to switch between layouts inside a narrow
    // column, for example
    if (Number.isInteger(this.stackAt) && this.$el instanceof Element) {
      this.resizeObserverRef = new ResizeObserver(this.handleResize);
      this.resizeObserverRef.observe(this.$el);
    }
  },

  unmounted() {
    // clean up ahead of garbage collection as there may be multiple Grids on a
    // page observing
    if (this.resizeObserverRef && this.$el instanceof Element) {
      this.resizeObserverRef.unobserve(this.$el);
    }
  },

  methods: {
    /**
     * Callback for ResizeObserver, toggles stack/grid modes
     *
     * @param {Array} entries
     **/
    handleResize: function(entries) {
      this.isStacked = entries[0].contentRect.width <= this.stackAt;
    },

    /**
     * Returns default Grid classNames, combined with supplied additional
     * classNames
     *
     * @param {Array} additionalClasses
     * @return {Array}
     */
    gridClasses: function(additionalClasses) {
      let classes = ['tui-grid', 'tui-grid--' + this.direction];

      if (
        this.direction === 'horizontal' &&
        this.useHorizontalGap &&
        !this.isStacked
      ) {
        classes.push('tui-grid--horizontal-gap');
      }

      if (
        this.direction === 'vertical' &&
        this.useVerticalGap &&
        !this.isStacked
      ) {
        classes.push('tui-grid--vertical-gap');
      }

      if (this.isStacked && this.useVerticalGap) {
        classes.push('tui-grid--stacked-gap');
      }

      if (this.gridTag === 'ul' || this.gridTag === 'ol') {
        classes.push('tui-grid--list');
      }

      if (additionalClasses && additionalClasses.length) {
        classes = classes.concat(additionalClasses);
      }

      return classes;
    },
  },

  render(h) {
    let totalSuppliedUnits = 0;

    // generate a clean set of intentionally coupled GridItem children, keeping
    // track of the number of supplied units as we go, it may not equal `maxUnits`
    let gridItems = this.$scopedSlots.default().filter(vnode => {
      if (
        vnode.componentOptions &&
        vnode.componentOptions.Ctor.options.name === 'GridItem'
      ) {
        if (typeof vnode.componentOptions.propsData.units !== 'undefined') {
          totalSuppliedUnits += vnode.componentOptions.propsData.units;
        } else {
          totalSuppliedUnits += 1; // default 'units' per Grid item
        }

        return vnode;
      }
    });

    // determine the gutter size for grid items, which could be zero if props
    // have been supplied to disable visual gaps
    let gutterSize = theme.getVarUsage('grid-gutter');
    if (
      (this.direction === 'horizontal' && !this.useHorizontalGap) ||
      (this.direction === 'vertical' && !this.useVerticalGap)
    ) {
      gutterSize = '0em';
    }

    // iterate our cleaned set of child nodes. we're about to mutate properties
    // on vnodes as we iterate, and though vnodes are supposed to be immutable,
    // Vue doesn't currently enforce this. for safer forwards compat we'll clone
    // vnodes and mutate those instead.
    let firstGridItem,
      currentUnwrappedUnits = 0,
      itemHasWrapped = false;

    gridItems = gridItems.map(vnode => {
      // clone and modify props data sent in, as the original vnode should be
      // considered immutable
      vnode = cloneVNode(vnode);

      // wrap potentially non-existent or wrongly typed `.class` value so we
      // can safely push more classNames onto it
      vnode.data.class = [vnode.data.class];

      // pass props to child GridItems so they can calculate their size
      vnode.componentOptions.propsData.sizeData = {
        gutterSize: gutterSize,
        maxGridUnits: this.maxUnits,
        numberOfSuppliedGridItems: gridItems.length,
      };

      // how many units does this GridItem add
      let addUnits = 0;
      if (typeof vnode.componentOptions.propsData.units !== 'undefined') {
        addUnits += vnode.componentOptions.propsData.units;
      } else {
        addUnits += 1; // default, if not specified
      }

      // we have an intentional zero unit GridItem which needs special
      // consideration, such as having its gutter removed, and not being marked
      // as a visually "first" GridItem
      let zeroUnit = false;
      if (vnode.componentOptions.propsData.units === 0) {
        vnode.data.class.push('tui-grid-item--no-units');
        zeroUnit = true;
      }

      // save a reference to the first vnode, this is so we can apply className
      // that should be the first node regardless of source flex `order` which
      // may differ. beware, because zero units are supported, the first index
      // may not be "first" anymore
      if (!firstGridItem && !zeroUnit) {
        firstGridItem = vnode;
      }

      // update to be first if flex re-ordered as first
      if (vnode.componentOptions.propsData.order === 1) {
        firstGridItem = vnode;
      }

      // handle wrapping of too-large grid items
      if (this.direction !== 'vertical' && totalSuppliedUnits > this.maxUnits) {
        // we're going to wrap, at least once, on this item
        if (currentUnwrappedUnits + addUnits > this.maxUnits) {
          vnode.data.class.push('tui-grid-item--first');
          currentUnwrappedUnits = addUnits; // restart the count
          itemHasWrapped = true;
        } else {
          currentUnwrappedUnits += addUnits; // continue the count
        }

        // add to all GridItems that wrap, when we first wrap
        if (itemHasWrapped) {
          vnode.data.class.push('tui-grid-item--wrapped');
        }
      }

      return vnode;
    });

    // give the first (visual, not DOM order) grid item a first modifier
    try {
      firstGridItem.data.class.push('tui-grid-item--first');
    } catch (e) {
      console.warn('No first GridItem has been identified!', e);
    }

    // apply default classNames to Grid node, plus any conditionally required
    // ones
    let additionalClasses = [];
    if (this.direction !== 'vertical' && totalSuppliedUnits > this.maxUnits) {
      if (this.useVerticalGap) {
        additionalClasses.push('tui-grid--wrapped', 'tui-grid--wrapped-gap');
      } else {
        additionalClasses.push('tui-grid--wrapped');
      }
    }

    // if switching from flex layout to a stack is required, conditionally apply
    // a stacking className if the Grid is supplied a numeric value to switch at,
    // instead of based on viewport
    if (this.isStacked) {
      additionalClasses.push('tui-grid--stacked');
    }

    return h(
      this.gridTag,
      {
        class: this.gridClasses(additionalClasses),
      },
      [gridItems]
    );
  },
};
</script>

<style lang="scss">
@mixin grid-item-generate-gutters(
  $_borderType: left,
  $_gutterSize: var(--grid-gutter)
) {
  > .tui-grid-item {
    // because we use transparent borders for gutters but don't want that
    // counting as visible item width
    box-sizing: content-box;
    background-clip: padding-box;
    // reset all possibly existing border widths, it is assumed that grid gaps
    // are unidirectional
    border-width: 0;
    border-#{$_borderType}: #{$_gutterSize} solid transparent;
  }
  // the "first" item should never have a gutter, but `:first-child` may not be
  // accurate if flex re-ordering has been applied, so use generated "first"
  // className instead. this works in most cases with the exception of:
  //  - when the browser ignores the order property due to siblings not having
  //    an order property also
  > .tui-grid-item--first {
    border-width: 0;
  }
}

// Grid styles
.tui-grid {
  display: flex;
  flex-grow: 1; // in case nested inside a parent grid cell
  max-width: 100%;

  // main Grid modifiers applied based on supplied prop values
  &--wrapped {
    flex-wrap: wrap;
  }

  // content-containing elements
  &-item {
    flex-grow: 0; // by default we want item size to respect unit-based calculations
    flex-shrink: 1; // by default we want to auto-adjust for gutters
    min-width: 0; // allows flex items to shrink below their minimum content size
    margin: 0;
    padding: 0;
    hyphens: auto; // default prevents text from causing grid mis-alignments

    // Grid item modifiers based on supplied prop values
    &--grow {
      flex-grow: 1;
    }
    &--no-shrink {
      flex-shrink: 0;
    }
    &--no-hyphens {
      hyphens: none;
    }
    &--overflow {
      overflow: auto;
    }
  }

  // horizontal grid
  &--horizontal {
    flex-direction: row;

    &-gap {
      @include grid-item-generate-gutters(left, var(--grid-gutter));
    }
  }

  // vertical grid
  &--vertical {
    flex-direction: column;

    &-gap {
      @include grid-item-generate-gutters(top, var(--grid-gutter));
    }

    // IE11 doesn't distribute vertical space without an explicit height set
    // on the container, so the safest thing to do is revert to block layout
    .ie & {
      display: block;
    }
  }

  &--wrapped-gap .tui-grid-item--wrapped {
    // margin better to use here instead of borders, as natural grid gap is
    // assumed to be unidirectional, whereas this additional type of grid gap
    // only applies when grid items wrap, and if the gap is confgured as
    // desirable for a given grid
    margin-top: var(--grid-gutter);
  }

  // all zero unit GridItems should not show any gutters or content
  &--vertical,
  &--horizontal {
    .tui-grid-item--no-units {
      overflow: hidden;
      border-width: 0;
    }
  }

  // switch to stacked display at an container-based pixel width breakpoint
  // value (class is conditionally applied during Grid render())
  &--stacked {
    display: block;

    > .tui-grid-item {
      flex-basis: auto;
    }

    &-gap {
      @include grid-item-generate-gutters(top, var(--grid-gutter));
    }
  }

  &--list {
    margin: 0;
    padding: 0;
    list-style-type: none;
  }
}
</style>
