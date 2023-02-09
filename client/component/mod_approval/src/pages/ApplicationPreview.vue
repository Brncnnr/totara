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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <Loader v-if="type === 'loading'" :loading="true" />
  <PrintView v-else :schema="formSchema">
    <template v-slot:document>
      <LayoutPrintView v-if="type === 'layout'" :schema="layoutView.schema" />
      <BasicPrintView
        v-if="type === 'basic'"
        :schema="basicView.schema"
        :approvers="basicView.approvers"
      />
    </template>
  </PrintView>
</template>

<script>
import tui from 'tui/tui';
import PrintView from 'mod_approval/components/schema_form/print/PrintView';
import BasicPrintView from 'mod_approval/components/schema_form/print/BasicPrintView';
import LayoutPrintView from 'mod_approval/components/schema_form/print/LayoutPrintView';
import Loader from 'tui/components/loading/Loader';
import { getAllLayoutCells } from '../js/internal/schema_form/schema';
import {
  getLayoutViewData,
  getBasicViewData,
} from '../js/internal/schema_form/print_view';

export default {
  components: {
    PrintView,
    BasicPrintView,
    LayoutPrintView,
    Loader,
  },

  props: {
    formSchema: {
      type: Object,
      required: true,
    },
    formData: {
      type: Object,
      required: true,
    },
    approvers: {
      type: Array,
      required: true,
    },
  },

  data() {
    return {
      type: 'loading',
      layoutView: null,
      basicView: null,
    };
  },

  async created() {
    const options = {
      schema: this.formSchema,
      values: this.formData,
      extraData: {
        approvers: this.approvers,
      },
    };
    if (this.formSchema.print_layout) {
      this.layoutView = await getLayoutViewData(options);
      const components = getAllLayoutCells(this.layoutView.schema).reduce(
        (acc, cell) => {
          if (cell.resolved && cell.resolved.component) {
            acc.push(cell.resolved.component);
          }
          return acc;
        },
        []
      );
      await Promise.all(components.map(x => tui.loadRequirements(x)));
      this.type = 'layout';
    } else {
      this.basicView = await getBasicViewData(options);
      this.type = 'basic';
    }
  },
};
</script>
