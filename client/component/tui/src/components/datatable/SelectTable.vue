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
  <div class="tui-dataSelectTable">
    <Table
      :entire-selected="entireSelected"
      :border-bottom-hidden="borderBottomHidden"
      :border-separator-hidden="borderSeparatorHidden"
      :color-odd-rows="colorOddRows"
      :data="data"
      :disabled-ids="disabledIds"
      :expandable-rows="expandableRows"
      :get-id="getId"
      :get-group-id="getGroupId"
      :group-mode="groupMode"
      :hover-off="hoverOff"
      :loading-overlay-active="loadingOverlayActive"
      :header-has-loaded="headerHasLoaded"
      :loading-preview="loadingPreview"
      :loading-preview-rows="loadingPreviewRows"
      :no-items-text="noItemsText"
      :selection="value"
      :selected-highlight-off="selectedHighlightOff"
      :stacked-header-row-gap="stackedHeaderRowGap"
      :stack-at="stackAt"
    >
      <template v-if="$scopedSlots['header-row']" v-slot:header-row>
        <SelectVisibleRowsCell
          :no-label-offset="noLabelOffset"
          :large-check-box="largeCheckBox"
          :checked="selectAllChecked"
          :disabled="false"
          :hidden="!selectAllEnabled"
          @change="onSelectAllChange"
        />
        <slot name="header-row" />
      </template>

      <template v-slot:pre-rows>
        <slot
          v-if="
            (showSelectEntirePrompt || showClearEntirePrompt) && !loadingPreview
          "
          name="select-all-prompt"
          :clear-func="clearEntire"
          :count="data.length"
          :mode="showSelectEntirePrompt"
          :select-func="selectEntire"
        >
          <SelectEveryRowToggle
            :clear-func="clearEntire"
            :count="data.length"
            :select-func="selectEntire"
            :show-select="showSelectEntirePrompt"
          />
        </slot>
      </template>

      <template
        v-slot:row="{
          expand,
          expandGroup,
          expandState,
          firstInGroup,
          groupId,
          id,
          inGroup,
          row,
        }"
      >
        <SelectRowCell
          :large-check-box="largeCheckBox"
          :no-label-offset="noLabelOffset"
          :checked="isRowSelected(inGroup ? groupId : id)"
          :disabled="isRowDisabled(inGroup ? groupId : id)"
          :hidden="inGroup && !firstInGroup"
          :valign="checkboxVAlign"
          :row-label="row[rowLabelKey] ? ' ' + row[rowLabelKey] : ''"
          @change="onRowSelectChange(inGroup ? groupId : id, $event)"
        />
        <slot
          :id="id"
          :checked="isRowSelected(inGroup ? groupId : id)"
          :expand="expand"
          :expand-group="expandGroup"
          :expand-state="expandState"
          :first-in-group="firstInGroup"
          :group-id="groupId"
          :in-group="inGroup"
          name="row"
          :row="row"
        />
      </template>

      <template v-slot:expand-content="{ row }">
        <slot name="expand-content" :row="row" />
      </template>

      <template v-slot:group-expand-content="{ group }">
        <slot name="group-expand-content" :group="group" />
      </template>
    </Table>
  </div>
</template>

<script>
import { unique } from 'tui/util';
import SelectEveryRowToggle from 'tui/components/datatable/SelectEveryRowToggle';
import SelectRowCell from 'tui/components/datatable/SelectRowCell';
import SelectVisibleRowsCell from 'tui/components/datatable/SelectVisibleRowsCell';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    SelectEveryRowToggle,
    SelectRowCell,
    SelectVisibleRowsCell,
    Table,
  },

  props: {
    borderBottomHidden: Boolean,
    // Hide separator border between rows
    borderSeparatorHidden: Boolean,
    // Enable background colour on odd rows
    colorOddRows: Boolean,
    checkboxVAlign: String,
    data: {
      type: Array,
      default: () => [],
    },
    // Disabled Ids
    disabledIds: {
      type: Array,
      default: () => [],
    },
    // Entire result set selection state
    entireSelected: Boolean,
    // Enables the ability to have expandable rows
    expandableRows: Boolean,
    getId: {
      type: Function,
      default: (row, index) => ('id' in row ? row.id : index),
    },
    getGroupId: {
      type: Function,
      default: (group, index) => ('id' in group ? group.id : index),
    },
    // Enables group mode
    groupMode: Boolean,
    // No hover background for rows
    hoverOff: Boolean,
    largeCheckBox: Boolean,
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
    noLabelOffset: Boolean,
    // Enables the ability to select all visible items
    selectAllEnabled: { type: Boolean },
    // Enables the ability to select the entire result set
    selectEntireEnabled: { type: Boolean },
    // Don't add styles for selected items
    selectedHighlightOff: Boolean,
    // Add a gap above the header row when stacked,
    // useful when there is a external header before the table
    stackedHeaderRowGap: Boolean,
    // ID's of selected rows
    value: Array,
    rowLabelKey: String,
    // The text to display if the data array is empty
    noItemsText: String,
    /*
     * When the width of the table is this size (in px) or smaller the table will be stacked/collapsed to a vertical view.
     */
    stackAt: Number,
  },

  computed: {
    /**
     * Track if select all should have a checked state
     *
     * @return {Boolean}
     */
    selectAllChecked() {
      if (this.entireSelected) {
        return true;
      } else if (this.selectAllEnabled) {
        return this.isEveryRowSelected();
      }
      return false;
    },

    /**
     * Track if select entire data set prompt should be displayed
     *
     * @return {Boolean}
     */
    showSelectEntirePrompt() {
      return (
        this.selectAllEnabled &&
        this.selectEntireEnabled &&
        !this.entireSelected &&
        this.isEveryRowSelected()
      );
    },

    /**
     * Track if clear entire data set prompt should be displayed
     *
     * @return {Boolean}
     */
    showClearEntirePrompt() {
      return this.selectAllEnabled && this.entireSelected;
    },
  },

  methods: {
    /**
     * Get the IDs of every item in this.data
     *
     * @return {Array}
     */
    $_allIds() {
      return this.data.map((x, i) => this.getId(x, i));
    },

    /**
     * Check if every row is selected
     *
     * @return {Boolean}
     */
    isEveryRowSelected() {
      if (this.data.length == 0) {
        return false;
      }

      // Get all ids in data
      let allIds = this.$_allIds();
      // Remove any disabled ids
      allIds = allIds.filter(id => !this.disabledIds.includes(id));

      return allIds.every(x => this.isRowSelected(x));
    },

    /**
     * Check if row is disabled
     *
     * @param {Int} id
     * @return {Boolean}
     */
    isRowDisabled(id) {
      return this.disabledIds.indexOf(id) !== -1;
    },

    /**
     * Check if row is selected
     *
     * @param {Int} id
     * @return {Boolean}
     */
    isRowSelected(id) {
      if (this.entireSelected) {
        return true;
        // Add it here
      }
      return this.value.indexOf(id) !== -1;
    },

    /**
     * Row selection checkbox state has changed
     *
     * @param {Int} id
     * @param {Boolean} checked
     */
    onRowSelectChange(id, checked) {
      let selection = [].concat(this.value);
      if (!checked) {
        this.rowDeselected(id, selection);
      } else {
        this.rowSelected(id, selection);
      }
    },

    /**
     * Table select all checkbox state has changed
     *
     * @param {Boolean} checked
     */
    onSelectAllChange(checked) {
      if (checked) {
        this.selectAllVisibleRows();
      } else {
        this.deselectAllVisibleRows();
      }
      if (this.selectEntireEnabled) {
        this.$emit('select-entire', false);
      }
    },

    /**
     * Add row ID to selection and emit the update
     *
     * @param {Int} id
     * @param {Array} selection
     */
    rowSelected(id, selection) {
      // If not already selected
      if (this.value.indexOf(id) == -1) {
        selection = selection.concat([id]);
        this.$emit('input', selection);
      }
    },

    /**
     * Remove row ID from selection and emit the update
     *
     * @param {Int} id
     * @param {Array} selection
     */
    rowDeselected(id, selection) {
      if (this.entireSelected) {
        // Get all ids in data
        let allIds = this.$_allIds();
        // Filter to only unique IDs
        selection = unique(selection.concat(allIds));
        this.$emit('select-entire', false);
      }

      if (selection.indexOf(id) !== -1) {
        selection.splice(selection.indexOf(id), 1);
      }

      this.$emit('input', selection);
    },

    /**
     * Select all visible rows
     *
     */
    selectAllVisibleRows() {
      let selection = [].concat(this.value);
      let allIds = this.$_allIds();

      // Create array of disabled items that aren't selected
      let disabledList = this.disabledIds.filter(
        id => !this.value.includes(id)
      );

      // Remove any disabled ids that aren't selected
      allIds = allIds.filter(id => !disabledList.includes(id));
      selection = unique(selection.concat(allIds));
      this.$emit('input', selection);
    },

    /**
     * Deselect all visible rows
     *
     */
    deselectAllVisibleRows() {
      // Get all ids in data
      let allIds = this.$_allIds();

      // Create array of disabled items that aren't selected
      let disabledList = this.disabledIds.filter(id => this.value.includes(id));

      allIds = allIds.filter(id => !disabledList.includes(id));
      const selection = this.value.filter(x => allIds.indexOf(x) === -1);
      this.$emit('input', selection);
    },

    /**
     * Flag select entire data has been selected
     *
     */
    selectEntire() {
      if (this.value.length > 0) {
        this.$emit('input', []);
      }
      this.$emit('select-entire', true);
    },

    /**
     * Flag select entire data has been deselected
     *
     */
    clearEntire() {
      if (this.value.length > 0) {
        this.$emit('input', []);
      }
      this.$emit('select-entire', false);
    },
  },
};
</script>
