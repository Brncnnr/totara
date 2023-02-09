<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <Responsive
    :breakpoints="[
      { name: 'xSmall', boundaries: [0, 750] },
      { name: 'small', boundaries: [750, 850] },
      { name: 'medium', boundaries: [850, 1000] },
      { name: 'large', boundaries: [1000, 1150] },
      { name: 'xLarge', boundaries: [1150, 1250] },
    ]"
    @responsive-resize="resize"
  >
    <Grid>
      <GridItem :units="gridUnitsLeft" />
      <GridItem :units="gridUnitsRight">
        <Basket
          class="tui-competencyCopyPathwayBasket"
          :items="selectedCompetencies"
          :wide-gap="true"
        >
          <template v-slot:status="{ empty }">
            <Button
              v-if="!empty"
              :styleclass="{ transparent: true }"
              :text="$str('basket_clear_selection', 'totara_competency')"
              @click="$emit('clear-selection')"
            />
          </template>

          <template v-slot:actions="{ empty }">
            <!-- Go to review selection button -->
            <template v-if="!reviewingSelection">
              <Button
                v-if="!empty"
                :styleclass="{ transparent: true }"
                :text="$str('basket_view_selected', 'totara_competency')"
                @click="$emit('reviewing-selection', true)"
              />
            </template>

            <!-- Back to all competencies button -->
            <Button
              v-else
              :styleclass="{ transparent: true }"
              :text="$str('back_to_all_competencies', 'totara_competency')"
              @click="$emit('reviewing-selection', false)"
            />

            <!-- Apply button -->
            <Button
              :disabled="empty"
              :loading="loadingConfirmation"
              :styleclass="{ primary: true }"
              :text="$str('apply_copy_pathways', 'totara_competency')"
              @click="$emit('apply')"
            />
          </template>
        </Basket>
      </GridItem>
    </Grid>
  </Responsive>
</template>

<script>
import Basket from 'tui/components/basket/Basket';
import Button from 'tui/components/buttons/Button';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Responsive from 'tui/components/responsive/Responsive';

export default {
  components: {
    Basket,
    Button,
    Grid,
    GridItem,
    Responsive,
  },

  props: {
    loadingConfirmation: {
      type: Boolean,
    },
    // Displaying the review selection view
    reviewingSelection: {
      type: Boolean,
    },
    // List of selected competencies
    selectedCompetencies: {
      type: Array,
    },
  },

  data() {
    return {
      boundaryDefaults: {
        xSmall: {
          gridUnitsLeft: 0,
          gridUnitsRight: 12,
        },
        small: {
          gridUnitsLeft: 3,
          gridUnitsRight: 9,
        },
        medium: {
          gridUnitsLeft: 4,
          gridUnitsRight: 8,
        },
        large: {
          gridUnitsLeft: 5,
          gridUnitsRight: 7,
        },
        xLarge: {
          gridUnitsLeft: 6,
          gridUnitsRight: 6,
        },
      },
      currentBoundaryName: null,
    };
  },

  computed: {
    /**
     * Return the number of grid units before the basket
     *
     * @return {Number}
     */
    gridUnitsLeft() {
      if (!this.currentBoundaryName) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundaryName].gridUnitsLeft;
    },

    /**
     * Return the number of grid units for the basket
     *
     * @return {Number}
     */
    gridUnitsRight() {
      if (!this.currentBoundaryName) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundaryName].gridUnitsRight;
    },
  },

  methods: {
    /**
     * Handles responsive resizing for basket
     *
     * @param {String} boundaryName
     */
    resize(boundaryName) {
      this.currentBoundaryName = boundaryName;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "apply_copy_pathways",
      "back_to_all_competencies",
      "basket_clear_selection",
      "basket_view_selected"
    ]
  }
</lang-strings>
