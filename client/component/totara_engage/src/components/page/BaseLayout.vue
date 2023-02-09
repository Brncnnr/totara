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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_engage
-->

<template>
  <Grid :max-units="String(maxPageUnits)">
    <GridItem :units="parseInt(contentUnits)">
      <slot name="content" />
    </GridItem>

    <GridItem :units="sidePanelUnits">
      <slot name="sidepanel" />
    </GridItem>
  </Grid>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';

export default {
  components: {
    Grid,
    GridItem,
  },

  props: {
    maxPageUnits: {
      type: [Number, String],
    },

    contentUnits: {
      type: [Number, String],
      required: true,
    },

    sidePanelUnits: {
      type: [Number, String],
      required: true,
    },
  },
};
</script>
