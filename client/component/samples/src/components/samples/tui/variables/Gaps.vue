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
  @module samples
-->

<template>
  <div>
    Available gap variables

    <SamplesExample>
      <Table :data="gaps">
        <template v-slot:header-row>
          <HeaderCell size="7">Variables</HeaderCell>
          <HeaderCell size="9">Example</HeaderCell>
        </template>
        <template v-slot:row="{ row }">
          <Cell size="7" column-header="Variables">
            {{ row.name }}
          </Cell>

          <Cell size="9" column-header="Example">
            <div class="tui-gapSamplesBox" :class="row.class" />
          </Cell>
        </template>
      </Table>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Cell,
    HeaderCell,
    SamplesCode,
    SamplesExample,
    Table,
  },

  data() {
    return {
      gaps: [
        {
          class: 'tui-gapSamplesBox--one',
          name: 'var(--gap-1);',
        },
        {
          class: 'tui-gapSamplesBox--two',
          name: 'var(--gap-2);',
        },
        {
          class: 'tui-gapSamplesBox--three',
          name: 'var(--gap-3);',
        },
        {
          class: 'tui-gapSamplesBox--four',
          name: 'var(--gap-4);',
        },
        {
          class: 'tui-gapSamplesBox--five',
          name: 'var(--gap-5);',
        },
        {
          class: 'tui-gapSamplesBox--six',
          name: 'var(--gap-6);',
        },
        {
          class: 'tui-gapSamplesBox--seven',
          name: 'var(--gap-7);',
        },
        {
          class: 'tui-gapSamplesBox--eight',
          name: 'var(--gap-8);',
        },
        {
          class: 'tui-gapSamplesBox--nine',
          name: 'var(--gap-9);',
        },
        {
          class: 'tui-gapSamplesBox--ten',
          name: 'var(--gap-10);',
        },
        {
          class: 'tui-gapSamplesBox--eleven',
          name: 'var(--gap-11);',
        },
        {
          class: 'tui-gapSamplesBox--twelve',
          name: 'var(--gap-12);',
        },
      ],
      codeTemplate: `<div class="tui-example__box">...</div>`,
      codeScript: `<style lang="scss">
  .tui-example__box {
    margin-left: var(--gap-4);
  }
</style>`,
    };
  },
};
</script>

<style lang="scss">
.tui-gapSamplesBox {
  height: 30px;
  background: var(--color-state);

  &--one {
    width: var(--gap-1);
  }

  &--two {
    width: var(--gap-2);
  }

  &--three {
    width: var(--gap-3);
  }

  &--four {
    width: var(--gap-4);
  }

  &--five {
    width: var(--gap-5);
  }

  &--six {
    width: var(--gap-6);
  }

  &--seven {
    width: var(--gap-7);
  }

  &--eight {
    width: var(--gap-8);
  }

  &--nine {
    width: var(--gap-9);
  }

  &--ten {
    width: var(--gap-10);
  }

  &--eleven {
    width: var(--gap-11);
  }

  &--twelve {
    width: var(--gap-12);
  }
}
</style>
