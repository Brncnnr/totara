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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module tui
-->

<script>
import VirtualUtils from '../../js/lib/internal/virtual_utils';
import VirtualItemMeasurer from './internal/VirtualItemMeasurer';
import { getDocumentPosition } from 'tui/dom/position';

const directionKey = 'scrollTop';
const listKeeps = 60;

export default {
  components: {
    VirtualItemMeasurer,
  },

  props: {
    /**
     * Unique key of the data object
     **/
    dataKey: {
      type: String,
      required: true,
    },

    /**
     * If enabled then scroll is listening to the full page scroll.
     * Keep it default to get list container behavior
     **/
    pageMode: {
      type: Boolean,
      default: false,
    },

    /**
     * Data array
     **/
    dataList: {
      type: Array,
      required: true,
    },

    /**
     * Item index starting point to render
     **/
    start: {
      type: Number,
      default: 0,
    },

    /**
     * custom offset to be included in the calculation
     **/
    offset: {
      type: Number,
      default: 0,
    },

    /**
     * Add additional threshhold to customise scroll top end position callback
     **/
    topThreshold: {
      type: Number,
      default: 0,
    },

    /**
     * Add additional threshhold to customise scroll bottom end position callback
     **/
    bottomThreshold: {
      type: Number,
      default: 0,
    },

    ariaLabel: {
      type: String,
      required: true,
    },

    useRole: {
      type: Boolean,
      default: true,
    },

    /**
     * data loading state
     */
    isLoading: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      range: null,
      dataLength: 0,
    };
  },

  watch: {
    dataList(newValue, oldValue) {
      if (newValue.length !== oldValue.length) {
        this.virtualUtil.updateParam('uniqueIds', this.getIdsFromData());
        this.virtualUtil.dataListChangeEvent();
        this.getListOfElements();
        this.calculateDataLength();
        // On tall screens, there may not be enough items loaded to fill it.
        // This repeats the call to ensure it gets called again to fill it.
        this.onScroll();
      }
    },

    start(newValue) {
      this.scrollToIndex(newValue);
    },

    offset(newValue) {
      this.scrollToOffset(newValue);
    },
  },

  created() {
    this.installVirtualScroll();
    this.elementList = [];
    this.keyStroke = 0;
  },

  activated() {
    this.scrollToOffset(this.virtualUtil.offset);
  },

  mounted() {
    this.rootElPosition = getDocumentPosition(this.$refs.root).y;
    this.getListOfElements();
    this.calculateDataLength();

    if (this.start) {
      this.scrollToIndex(this.start);
    } else if (this.offset) {
      this.scrollToOffset(this.offset);
    }

    if (this.pageMode) {
      document.addEventListener('scroll', this.onScroll, { passive: true });
    }
    // Ensure enough items are loaded to fill the screen
    this.onScroll();
  },

  beforeDestroy() {
    if (this.pageMode) {
      document.removeEventListener('scroll', this.onScroll);
    }
  },

  updated() {
    this.getListOfElements();

    //Focus on element when dom elements are updated
    //Required to do this since dom elements get updated when it hits the threshhold
    if (this.keyStroke === 9 || this.keyStroke === 34) {
      this.focusElement(this.targetIndex + 1);
    } else if (this.keyStroke === 33) {
      this.focusElement(this.targetIndex - 1);
    } else if (this.keyStroke === 35) {
      this.elementList[this.elementList.length - 1].focus();
    }
  },

  methods: {
    /**
     * Get list of elements
     */
    getListOfElements() {
      this.elementList = Array.prototype.slice.apply(
        this.$refs.root.querySelectorAll('article')
      );
    },

    /**
     * Scroll to a specific position
     */
    scrollToOffset(offset) {
      if (this.pageMode) {
        document.documentElement[directionKey] = offset;
      } else {
        const { root } = this.$refs;
        if (root) {
          root[directionKey] = offset || 0;
        }
      }
    },

    /**
     * Scroll to a position by providing an index of the element
     */
    scrollToIndex(index) {
      // scroll to bottom
      if (index >= this.dataList.length - 1) {
        this.$_scrollToBottom();
      } else {
        const offset = this.virtualUtil.getOffset(index);
        this.scrollToOffset(offset);
      }
    },

    /**
     * Scroll to the bottom of the container
     */
    $_scrollToBottom() {
      const { scrollBottomRef } = this.$refs;
      if (scrollBottomRef) {
        scrollBottomRef.scrollIntoView(false);

        // check if it's really scrolled to the bottom
        // maybe list doesn't render and calculate to last range
        // so we need retry in next event loop until it really at bottom
        setTimeout(() => {
          if (
            this.$_getOffset() + this.$_getRootHeight() <
            this.$_getScrollHeight()
          ) {
            this.$_scrollToBottom();
          }
        }, 3);
      }
    },

    /**
     * Initial virtual class with initial values
     */
    installVirtualScroll() {
      this.virtualUtil = new VirtualUtils(
        {
          keeps: listKeeps,
          buffer: 2,
          uniqueIds: this.getIdsFromData(),
          pageMode: this.pageMode,
        },
        this.$_onRangeChanged
      );
      this.range = this.virtualUtil.getRange();
    },

    /**
     * Called when range values are is changed
     */
    $_onRangeChanged(range) {
      this.range = range;
    },

    getIdsFromData() {
      return this.dataList.map(dataSource => dataSource[this.dataKey]);
    },

    /**
     * Get scrolling position
     * @returns {Number} offset
     */
    $_getOffset() {
      if (this.pageMode) {
        return document.documentElement[directionKey];
      } else {
        const { root } = this.$refs;
        return root ? Math.ceil(root[directionKey]) : 0;
      }
    },

    /**
     * Get html document overall height
     *
     * @returns {Number} height
     */
    $_getDocumentHeight() {
      return document.documentElement['clientHeight'];
    },

    /**
     * Get root container height
     *
     * @returns {Number} height
     */
    $_getRootHeight() {
      const { root } = this.$refs;
      return root ? root['clientHeight'] : 0;
    },

    /**
     * Get secondary container height.
     * Used when pageMode is false
     *
     * @returns {Number} height
     */
    $_getItemsWrapperHeight() {
      const { itemsWrapper } = this.$refs;
      return itemsWrapper ? itemsWrapper['clientHeight'] : 0;
    },

    /**
     * Get scrolling overall height
     *
     * @returns {Number} height
     */
    $_getScrollHeight() {
      if (this.pageMode) {
        return document.documentElement['scrollHeight'];
      } else {
        const { root } = this.$refs;
        return root ? root['scrollHeight'] : 0;
      }
    },

    /**
     * Event called when each item mounted or size changed
     *
     * @param {String} id of the item
     * @param {Number} offsetHeight
     */
    onItemResized(id, offsetHeight) {
      this.virtualUtil.saveSize(id, offsetHeight, this.rootElPosition);
    },

    /**
     * Event called when footer size is changed
     *
     * @param {Number} size of the footer
     * @param {Boolean} hasInit - if it is initial load
     */
    onFooterResized(size, hasInit) {
      this.virtualUtil.updateParam('slotFooterSize', size);

      if (hasInit) {
        this.virtualUtil.footerSizeChangeEvent();
      }
    },

    onScroll(evt) {
      const offset = this.$_getOffset();
      const documentHeight = this.$_getDocumentHeight();
      const rootHeight = this.$_getRootHeight();
      const scrollSize = this.$_getScrollHeight();
      const calcHeight =
        this.pageMode && documentHeight !== 0 ? documentHeight : rootHeight;

      if (offset + calcHeight > scrollSize || !scrollSize) {
        return;
      }

      this.virtualUtil.handleScroll(offset);
      this.$_emitEvents(offset, documentHeight, rootHeight, scrollSize, evt);
    },

    /**
     * Emit events based on conditions met
     *
     * @param {Number} offset
     * @param {Number} documentHeight
     * @param {Number} rootHeight
     * @param {Number} scrollSize
     * @param {Object} evt
     */
    $_emitEvents(offset, documentHeight, rootHeight, scrollSize, evt) {
      const range = this.virtualUtil.getRange();
      const itemsWrapperHeight = this.$_getItemsWrapperHeight();

      if (
        this.virtualUtil.isForward() &&
        !!this.dataList.length &&
        offset - this.topThreshold <= 0
      ) {
        this.$emit('scrolltop', evt, range);
      } else if (
        this.virtualUtil.isDownward() &&
        this.pageMode &&
        offset + documentHeight > this.rootElPosition + rootHeight
      ) {
        this.$emit('scrollbottom', evt, range);
      } else if (
        this.virtualUtil.isDownward() &&
        !this.pageMode &&
        offset + rootHeight + this.bottomThreshold >= itemsWrapperHeight
      ) {
        this.$emit('scrollbottom', evt, range);
      }

      if (evt) {
        // Only fire the user if it was actually scrolled
        // (as this can be called on initialisation of this component)
        this.$emit('scroll', evt, range);
      }
    },

    getRenderingItems(h) {
      const slots = [];
      const { start, end } = this.range;
      const { dataList, dataKey } = this;
      for (let index = start; index <= end; index++) {
        const dataSource = dataList[index];
        if (dataSource) {
          if (dataSource[dataKey] != null) {
            slots.push(
              h(
                VirtualItemMeasurer,
                {
                  on: { resize: this.onItemResized },
                  key: dataSource[dataKey],
                  props: {
                    uniqueKey: dataSource[dataKey],
                  },
                },
                this.$scopedSlots.item({
                  item: dataSource,
                  index: index,
                  posInSet: index + 1,
                  setSize: this.dataList.length,
                })
              )
            );
          }
        }
      }
      return slots;
    },

    onKeyDown(event) {
      const key = event.keyCode;
      const element = event.target;
      if (!element.attributes.role) {
        return;
      }

      this.keyStroke = key;
      this.targetIndex = parseInt(element.getAttribute('aria-posinset'));
      switch (key) {
        case 9:
          // tab
          if (this.targetIndex + 1 === this.dataLength) {
            const range = this.virtualUtil.getRange();
            this.$emit('scrollbottom', range);
            event.preventDefault();
          }
          break;
        case 33:
          // Page up
          event.preventDefault();
          if (this.targetIndex > 1) {
            if (this.dataLength >= this.targetIndex) {
              // get the previous index and match it with aria postInSet and focus
              let prevIndex = this.targetIndex - 1;
              this.focusElement(prevIndex);
            }
          }
          break;
        case 34:
          //Page down
          event.preventDefault();
          if (this.targetIndex + 1 === this.dataLength) {
            const range = this.virtualUtil.getRange();
            this.$emit('scrollbottom', range);
          }
          if (this.dataLength >= this.targetIndex) {
            // get the next index and match it with aria postInSet and focus
            let nextIndex = this.targetIndex + 1;
            this.focusElement(nextIndex);
          }
          break;
        case 36:
          // home
          if (event.ctrlKey && this.dataLength > 0) {
            event.preventDefault();
            this.elementList[0].focus();
          }
          break;
        case 35:
          if (event.ctrlKey) {
            event.preventDefault();
            this.elementList[this.elementList.length - 1].focus();
          }
          break;
      }
    },

    /**
     * Set focus on element for keyboard control
     *
     * @param {Number} toMatchIndex
     */
    focusElement(toMatchIndex) {
      for (let index in this.elementList) {
        if (
          parseInt(this.elementList[index].getAttribute('aria-posinset')) ==
          toMatchIndex
        ) {
          this.elementList[index].focus();
          break;
        }
      }
    },

    /**
     * Calculate DataList length for keyboard (onKeyDown method) control checks
     */
    calculateDataLength() {
      let count = 0;
      let itemsExist = true;
      for (let el in this.dataList) {
        if (!this.dataList[el].items) {
          itemsExist = false;
          break;
        }
        count += this.dataList[el].items.length;
      }
      this.dataLength = itemsExist ? count : this.dataList.length;
    },
  },

  render(h) {
    const padding = `${this.range.padFront}px 0px ${this.range.padBehind}px`;
    return h(
      'div',
      {
        ref: 'root',
        style: {
          display: 'block',
          padding: this.pageMode && padding,
          height: !this.pageMode && `100%`,
          overflowY: !this.pageMode && 'auto',
        },
        attrs: {
          role: this.useRole && 'feed',
          'aria-label': this.ariaLabel,
          'aria-busy': this.isLoading,
        },
        on: {
          '&scroll': !this.pageMode && this.onScroll,
          keydown: this.onKeyDown,
        },
      },
      [
        this.pageMode
          ? this.getRenderingItems(h)
          : h(
              'div',
              {
                ref: 'itemsWrapper',
                style: {
                  padding,
                },
              },
              this.getRenderingItems(h)
            ),

        this.$scopedSlots.footer
          ? h(
              VirtualItemMeasurer,
              { on: { resize: this.onFooterResized } },
              this.$scopedSlots.footer()
            )
          : null,

        h('div', {
          ref: 'scrollBottomRef',
        }),
      ]
    );
  },
};
</script>
