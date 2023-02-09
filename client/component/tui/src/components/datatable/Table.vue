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
-->

<template>
  <div v-if="!data.length && !loadingPreview">
    {{ noItemsText }}
  </div>
  <div
    v-else
    :aria-hidden="loadingPreview"
    class="tui-dataTable"
    :class="{
      'tui-dataTable--archived': archived,
    }"
    role="table"
  >
    <HeaderRow
      :empty="!$slots['header-row']"
      :indented="indentContents"
      :is-stacked="isStacked"
      :stacked-gap="stackedHeaderRowGap"
    >
      <HeaderCell
        v-if="$slots['header-row'] && draggableRows"
        class="tui-dataTable__row-move-cell"
      />
      <PropsProvider :provide="headerProvide">
        <slot name="header-row" />
      </PropsProvider>
    </HeaderRow>

    <PreRows v-if="this.$slots['pre-rows']">
      <PropsProvider :provide="provide">
        <slot name="pre-rows" />
      </PropsProvider>
    </PreRows>

    <render :vnode="draggableDropTarget" />

    <RowGroup
      v-for="{ group, groupId, rows, expandGroup } in rowGroupData"
      :key="groupId"
      :selected="isSelected(groupId)"
      :wrap="groupMode"
      :is-stacked="isStacked"
    >
      <template v-for="({ row, id, expand, index }, groupIndex) in rows">
        <!--
          Workarounds for Vue limitations:
            * It is difficult to conditionally wrap a component when using
              template syntax (it is easier with JSX), so we need to do a trick
              using <component :is> and a special "passthrough" component that
              just renders its children.
            * Vue can't bind native listeners with v-on="object" syntax, so we
              need to use PropsProvider to enable that - which results in another
              <component :is> passthrough. This is fixed in Vue 3.
        -->
        <component
          :is="draggableRows ? 'Draggable' : 'passthrough'"
          :key="id"
          v-slot="{
            dragging,
            attrs,
            nativeEvents,
            moveMenu,
          }"
          :type="getDraggableType(row)"
          :value="getDraggableValue(row)"
          :index="index + indexOffset"
          :renderless="true"
        >
          <component
            :is="draggableRows ? 'PropsProvider' : 'passthrough'"
            :key="id"
            :provide="{ nativeListeners: nativeEvents }"
          >
            <Row
              :key="id"
              :border-bottom-hidden="borderBottomHidden"
              :border-separator-hidden="borderSeparatorHidden"
              :border-top-hidden="borderTopHidden"
              :disabled="isDisabled(id)"
              :hover-off="loadingPreview || hoverOff"
              :in-group="groupMode"
              :selected="isSelected(id)"
              :selected-highlight-off="selectedHighlightOff"
              :color-odd="colorOddRows && !draggableRows"
              :draggable="draggableRows"
              :dragging="dragging"
              :expanded="id == expanded && !loadingPreview"
              :indented="indentContents"
              :stealth="indentExpandedContents"
              :stealth-expanded="stealthExpanded"
              :is-stacked="isStacked"
              v-bind="attrs"
            >
              <Cell
                v-if="draggableRows"
                class="tui-dataTable__row-move-cell"
                valign="center"
              >
                <DragHandleIcon />
                <div v-if="draggableRows" class="tui-dataTable__row-move-menu">
                  <render :vnode="moveMenu" />
                </div>
              </Cell>
              <PropsProvider :provide="provide">
                <slot
                  :id="id"
                  name="row"
                  :expand="expand"
                  :expandState="isExpanded(id)"
                  :expand-group="expandGroup"
                  :first-in-group="groupIndex === 0"
                  :group-id="groupId"
                  :in-group="groupMode"
                  :row="row"
                  :dragging="dragging"
                />
              </PropsProvider>
            </Row>
          </component>
        </component>

        <ExpandedRow
          v-if="!loadingPreview && isExpanded(id)"
          :key="id + ' expand'"
          :stealth="stealthExpanded"
          :indent-contents="indentExpandedContents"
          :is-stacked="isStacked"
          @close="updateExpandedRow()"
        >
          <PropsProvider :provide="provide">
            <slot name="expand-content" :row="row" />
          </PropsProvider>
        </ExpandedRow>
      </template>

      <ExpandedRow
        v-if="expandableRows && groupMode && groupId == expandedGroup"
        :key="groupId + ' expand'"
        :stealth="stealthExpanded"
        :indent-contents="indentExpandedContents"
        :is-stacked="isStacked"
        @close="updateExpandedGroup()"
      >
        <PropsProvider :provide="provide">
          <slot name="group-expand-content" :group="group" />
        </PropsProvider>
      </ExpandedRow>
    </RowGroup>

    <render :vnode="draggablePlaceholder" />
  </div>
</template>

<script>
import ExpandedRow from 'tui/components/datatable/ExpandedRow';
import HeaderRow from 'tui/components/datatable/HeaderRow';
import PreRows from 'tui/components/datatable/PreRows';
import Row from 'tui/components/datatable/Row';
import RowGroup from 'tui/components/datatable/RowGroup';
import Draggable from 'tui/components/drag_drop/Draggable';
import PropsProvider from 'tui/components/util/PropsProvider';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import DragHandleIcon from 'tui/components/icons/DragHandle';
import ResizeObserver from 'tui/polyfills/ResizeObserver';

export default {
  components: {
    ResizeObserver,
    ExpandedRow,
    HeaderRow,
    PreRows,
    Row,
    RowGroup,
    Draggable,
    PropsProvider,
    Cell,
    HeaderCell,
    DragHandleIcon,
  },

  props: {
    // Table is displaying archived content
    archived: Boolean,
    // Rows are displayed indented
    indentContents: Boolean,
    // Expanded Rows are displayed indented
    indentExpandedContents: Boolean,
    // Table has stealth expanded rows
    stealthExpanded: Boolean,
    // Hide last border bottom
    borderBottomHidden: Boolean,
    // Hide separator border between rows
    borderSeparatorHidden: Boolean,
    // Hide first border top
    borderTopHidden: Boolean,
    // Enable background colour on odd rows
    colorOddRows: Boolean,
    data: Array,
    // List of disabled IDs
    disabledIds: Array,
    // No hover background for rows
    hoverOff: Boolean,
    // The text to display if the data array is empty
    noItemsText: {
      type: String,
      default() {
        return this.$str('noitems', 'totara_core');
      },
    },
    // Entire result set selection state
    entireSelected: Boolean,
    // Enables the ability to have expandable rows
    expandableRows: Boolean,
    expandMultipleRows: Boolean,
    getGroupId: {
      type: Function,
      default: (group, index) => ('id' in group ? group.id : index),
    },
    getId: {
      type: Function,
      default: (row, index) => ('id' in row ? row.id : index),
    },
    // Enables group mode
    groupMode: Boolean,
    // Loading preview is behind an overlay
    loadingOverlayActive: Boolean,

    // loadingPreview can be overridden by headerHasLoaded to render the table header
    // while the table rows are loading.
    headerHasLoaded: Boolean,

    // Show placeholder skeleton content while loading
    loadingPreview: Boolean,
    // Number of placeholder rows to display while loading
    loadingPreviewRows: {
      type: Number,
      default: 5,
    },
    // ID's of selected rows
    selection: Array,
    // Don't add styles for selected items
    selectedHighlightOff: Boolean,
    // Add a gap above the header row when stacked,
    // useful when there is a external header before the table
    stackedHeaderRowGap: Boolean,
    // draggable:
    draggableRows: Boolean,
    draggablePlaceholder: Object,
    draggableDropTarget: Object,
    indexOffset: {
      type: Number,
      default: 0,
    },
    draggableValue: {
      type: Function,
    },
    draggableType: {
      type: [String, Function],
    },
    /*
     * When the width of the table is this size (in px) or smaller the table will be stacked/collapsed to a vertical view.
     */
    stackAt: {
      type: Number,
      default: 570, // Equivalent to the $tui-screen-xs breakpoint if the table is full width.
    },
  },

  data() {
    return {
      expanded: null,
      expandedRows: [],
      expandedGroup: null,
      width: null,
      isStacked: false,
    };
  },

  computed: {
    /**
     * Return row data
     *
     * @return {Array}
     */
    rowData() {
      if (!Array.isArray(this.data)) {
        return [];
      }

      let data = this.loadingPreview
        ? this.getPlaceholderLoadingRows()
        : this.data;

      // Close any expanded rows when loading content
      if (this.loadingPreview) {
        this.updateExpandedRow();
      }

      return data.map((row, index) => {
        const id = this.getId(row, index);
        return {
          row,
          id,
          index,
          expand: () => {
            this.updateExpandedRow(id);
          },
        };
      });
    },

    /**
     * Return row data based on grouping
     *
     * @return {Array}
     */
    rowGroupData() {
      if (!this.groupMode || this.draggableRows) {
        return [{ id: null, rows: this.rowData }];
      }

      return this.data.map((group, groupIndex) => {
        const groupId = this.getGroupId(group, groupIndex);
        return {
          groupId,
          group,
          expandGroup: () => this.updateExpandedGroup(groupId),
          rows: group.rows.map((row, rowIndex) => {
            const id = this.getId(row, groupId + ':' + rowIndex);
            return {
              row,
              id,
              expand: () => this.updateExpandedRow(id),
            };
          }),
        };
      });
    },

    enableResizeObserver() {
      return this.loadingPreview || this.data.length > 0;
    },
  },

  watch: {
    stackAt() {
      this.isStacked = this.width <= this.stackAt;
    },

    enableResizeObserver: {
      handler(enable) {
        if (enable) {
          this.$nextTick(this.registerResizeObserver);
        } else {
          this.$nextTick(this.unregisterResizeObserver);
        }
      },
      immediate: true,
    },
  },

  beforeDestroy() {
    this.unregisterResizeObserver();
  },

  methods: {
    /**
     * Number of placeholder rows for loading display
     *
     * @return {Array}
     */
    getPlaceholderLoadingRows() {
      return Array.from({ length: this.loadingPreviewRows }, () => ({}));
    },

    /**
     * Check if row has been disabled
     *
     * @param {Int} id
     */
    isDisabled(id) {
      return this.disabledIds && this.disabledIds.includes(id);
    },

    /**
     * Check if row has been selected
     *
     * @param {Int} id
     */
    isSelected(id) {
      return (
        this.selection && (this.entireSelected || this.selection.includes(id))
      );
    },

    /**
     * Calculate isExpanded for current row
     *
     * @param {Int} id
     */
    isExpanded(id) {
      return this.expandMultipleRows
        ? this.expandedRows.indexOf(id) !== -1
        : this.expandableRows && id == this.expanded;
    },

    /**
     * Provider for passing props down to the header cells.
     *
     * @return {Object}
     */
    headerProvide() {
      return {
        props: {
          isStacked: this.isStacked,
          loadingOverlayActive: this.loadingOverlayActive,
          loadingPreview: this.headerHasLoaded ? false : this.loadingPreview,
        },
      };
    },

    /**
     * Provider for passing props down multiple levels of slots
     *
     * @return {Object}
     */
    provide() {
      return {
        props: {
          isStacked: this.isStacked,
          loadingOverlayActive: this.loadingOverlayActive,
          loadingPreview: this.loadingPreview,
        },
      };
    },

    /**
     * set expanded to ID of expanded row
     *
     */
    updateExpandedRow(rowId) {
      if (rowId === undefined) {
        this.expanded = null;
        this.expandedRows = [];
        return;
      }

      if (this.expandMultipleRows) {
        const indexOfRowId = this.expandedRows.indexOf(rowId);

        if (indexOfRowId === -1) {
          this.expandedRows.push(rowId);
        } else {
          this.expandedRows.splice(indexOfRowId, 1);
        }
        return;
      }

      this.expandedGroup = null;
      this.expanded = this.expanded === rowId ? null : rowId;
    },

    /**
     * set expanded to ID of expanded row group
     *
     */
    updateExpandedGroup(groupId) {
      this.expanded = null;
      const expand = this.expandedGroup !== groupId;
      this.expandedGroup = groupId !== undefined && expand ? groupId : null;
    },

    /**
     * Get the value to use for a draggable row.
     */
    getDraggableValue(row) {
      return typeof this.draggableValue === 'function'
        ? this.draggableValue(row)
        : row;
    },

    /**
     * Get the type to use for a draggable row.
     */
    getDraggableType(row) {
      if (!this.draggableRows) {
        return;
      }

      if (typeof this.draggableType === 'string') {
        return this.draggableType;
      } else if (typeof this.draggableType === 'function') {
        return this.draggableType(row);
      }
      console.error(
        'draggable-type prop must be supplied to Table when draggable-rows is true.'
      );
    },

    /**
     * Register the resize observer used to control stacking (stackedAt).
     */
    registerResizeObserver() {
      if (Number.isInteger(this.stackAt) && this.$el instanceof Element) {
        this.resizeObserverRef = new ResizeObserver(this.handleResize);
        this.resizeObserverRef.observe(this.$el);
      }
    },

    /**
     * Unregister the resize observer used to control stacking (stackedAt).
     */
    unregisterResizeObserver() {
      if (this.resizeObserverRef && this.$el instanceof Element) {
        this.resizeObserverRef.unobserve(this.$el);
      }
    },

    /**
     * Resize observer callback to determine if the table should be in stacked mode or not.
     *
     * @param entries {Object[]}
     */
    handleResize: function(entries) {
      this.width = entries[0].contentRect.width;

      this.isStacked = this.width <= this.stackAt;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": ["noitems"]
  }
</lang-strings>

<style lang="scss">
.tui-dataTable {
  &--archived {
    background: var(--datatable-bg-archived);
  }

  &__row-move-cell {
    flex-basis: var(--gap-5);
    flex-grow: 0;
  }

  &__row-move-menu {
    position: absolute;
    top: 0;
    left: var(--gap-8);
    background: var(--color-background);
  }
}
</style>
