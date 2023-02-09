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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-libraryView">
    <Layout
      :breakpoints="libraryBreakpoints"
      :boundaries="libraryBoundaries"
      default-boundary="l"
      :stack-at="585"
      :show-right-side-panel="showRightSidePanel"
      :right-side-panel-animated="true"
      :right-side-panel-sticky="true"
      :right-side-panel-overflows="true"
      :show-left-side-panel="true"
      :show-left-side-panel-control="false"
      :left-side-panel-initially-open="true"
      :left-side-panel-sticky="false"
      :left-side-panel-overflows="true"
    >
      <!-- sidePanelLeft -->
      <template v-slot:sidePanelLeft="{ units, gridDirection }">
        <NavigationPanel
          :selected-id="id"
          :values="pageProps"
          :title="itemTitle"
          :units="units"
          :grid-direction="gridDirection"
          :redirection="pageProps.redirection"
        />
      </template>
      <!-- /sidePanelLeft -->

      <!-- column -->
      <template v-slot:column="{ units, gridDirection }">
        <component
          :is="content.component"
          :page-id="id"
          :page-props="pageProps"
          :units="units"
          :grid-direction="gridDirection"
          @updateTitle="updateTitle"
        />
      </template>
      <!-- /column -->

      <!-- sidePanelRight -->
      <template
        v-if="showRightSidePanel"
        v-slot:sidePanelRight="{ units, gridDirection }"
      >
        <component
          :is="sidePanel.component"
          :page-id="id"
          :page-props="pageProps"
          :units="units"
          :grid-direction="gridDirection"
        />
      </template>
      <!-- /sidePanelRight -->
    </Layout>
  </div>
</template>

<script>
import Layout from 'totara_engage/components/page/LayoutOneColumnWithMultiSidePanel';
import NavigationPanel from 'totara_engage/components/sidepanel/NavigationPanel';

// Mixins
import { validatePageComponent } from 'totara_engage/mixins/library_mixin';
import tui from 'tui/tui';

const has = Object.prototype.hasOwnProperty;

export default {
  components: {
    Layout,
    NavigationPanel,
  },

  props: {
    id: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    content: {
      type: Object,
      required: true,
      validator: content => validatePageComponent(content),
    },
    sidePanel: {
      type: Object,
      required: false,
      validator: sidePanel => validatePageComponent(sidePanel),
    },
    pageProps: {
      type: Object,
      default: () => ({}),
    },
  },

  data() {
    return {
      itemTitle: this.title,
      libraryBreakpoints: [
        { name: 'xs', boundaries: [0, 364] },
        { name: 's', boundaries: [365, 768] },
        { name: 'm', boundaries: [769, 1192] },
        { name: 'l', boundaries: [1193, 1672] },
      ],
      libraryBoundaries: {
        xs: {
          gridDirection: 'vertical',
          gridUnitsOuterLeftExpanded: 6,
          gridUnitsOuterLeftCollapsed: 11,
          gridUnitsOuterRightExpanded: 6,
          gridUnitsOuterRightCollapsed: 1,
          gridUnitsInnerLeftExpanded: 10,
          gridUnitsInnerLeftCollapsed: 10,
          gridUnitsInnerRightExpanded: 12,
          gridUnitsInnerRightCollapsed: 2,
        },
        s: {
          gridDirection: 'vertical',
          gridUnitsOuterLeftExpanded: 6,
          gridUnitsOuterLeftCollapsed: 11,
          gridUnitsOuterRightExpanded: 6,
          gridUnitsOuterRightCollapsed: 1,
          gridUnitsInnerLeftExpanded: 10,
          gridUnitsInnerLeftCollapsed: 10,
          gridUnitsInnerRightExpanded: 12,
          gridUnitsInnerRightCollapsed: 2,
        },
        m: {
          gridDirection: 'horizontal',
          gridUnitsOuterLeftExpanded: 3,
          gridUnitsOuterLeftCollapsed: 1,
          gridUnitsOuterRightExpanded: 9,
          gridUnitsOuterRightCollapsed: 1,
          gridUnitsInnerLeftExpanded: 6,
          gridUnitsInnerLeftCollapsed: 11,
          gridUnitsInnerRightExpanded: 6,
          gridUnitsInnerRightCollapsed: 1,
        },
        l: {
          gridDirection: 'horizontal',
          gridUnitsOuterLeftExpanded: 2,
          gridUnitsOuterLeftCollapsed: 1,
          gridUnitsOuterRightExpanded: 10,
          gridUnitsOuterRightCollapsed: 1,
          gridUnitsInnerLeftExpanded: 6,
          gridUnitsInnerLeftCollapsed: 11,
          gridUnitsInnerRightExpanded: 6,
          gridUnitsInnerRightCollapsed: 1,
        },
      },
    };
  },

  computed: {
    showRightSidePanel() {
      return !!this.sidePanel;
    },
  },
  created() {
    this.$_loadTuiComponents(this.content);
    if (this.sidePanel) {
      this.$_loadTuiComponents(this.sidePanel);
    }
  },

  methods: {
    /**
     *
     * @param {Array} items
     */
    $_loadTuiComponents({ component, tuicomponent }) {
      if (!has.call(this.$options.components, component)) {
        this.$options.components[component] = tui.asyncComponent(tuicomponent);
      }
    },

    /**
     * In case the title gets updated by a sub-component we allow for the change to be propagated up
     * updated and propagated to other components in here.
     *
     * @param {String} title
     */
    updateTitle(title) {
      this.itemTitle = title;
    },
  },
};
</script>
