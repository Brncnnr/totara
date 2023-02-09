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
  <div class="tui-sidePanelNavExample">
    <Grid>
      <GridItem :units="3">
        <SidePanel
          :initially-open="true"
          :show-button-control="false"
          :sticky="false"
        >
          <SidePanelNav
            v-model="selectedItem"
            :aria-label="ariaLabel"
            @change="navChange"
          >
            <SidePanelNavGroup
              v-for="(group, id) in itemGroups"
              :key="id"
              :title="group.title || null"
            >
              <template v-if="id == 0" v-slot:heading-side>
                <ButtonIcon
                  :aria-label="'More'"
                  :styleclass="{ circle: true, xsmall: true }"
                >
                  <AddIcon />
                </ButtonIcon>
              </template>

              <template v-if="group.type == 'links'">
                <SidePanelNavLinkItem
                  v-for="item in group.items"
                  :id="item.id"
                  :key="item.id"
                  :text="item.text"
                  :url="item.action"
                >
                  <ButtonIcon
                    v-if="item.id == 11"
                    :aria-label="'More'"
                    :styleclass="{ circle: true, xsmall: true }"
                  >
                    <AddIcon />
                  </ButtonIcon>
                </SidePanelNavLinkItem>
              </template>

              <template v-else>
                <SidePanelNavButtonItem
                  v-for="item in group.items"
                  :id="item.id"
                  :key="item.id"
                  :text="item.text"
                  :action="item.action"
                >
                  <ButtonIcon
                    v-if="item.id == 8"
                    :aria-label="'More'"
                    :styleclass="{ circle: true, xsmall: true }"
                  >
                    <AddIcon />
                  </ButtonIcon>
                </SidePanelNavButtonItem>
              </template>
            </SidePanelNavGroup>
          </SidePanelNav>
        </SidePanel>
      </GridItem>

      <GridItem :units="9" :shrinks="true">
        <p>
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse
          sit amet erat ex. Sed ac felis auctor, molestie orci eget, semper
          tortor. Curabitur non elementum nisi. Donec sit amet venenatis ligula,
          at congue massa. In ac dolor ante. Mauris faucibus, nulla consectetur
          scelerisque efficitur, risus leo pharetra mi, non vehicula tellus elit
          eget massa. Quisque feugiat eros et aliquam tempus.
        </p>

        <p>
          Maecenas ut ornare sapien. Nulla sed rutrum ante. Pellentesque
          habitant morbi tristique senectus et netus et malesuada fames ac
          turpis egestas. In turpis purus, feugiat sed commodo vitae, hendrerit
          in tortor. Ut sed risus dolor. Vestibulum sed sapien ultrices ipsum
          interdum facilisis nec non augue. Nulla et tellus id ipsum congue
          aliquet in a quam. Aenean cursus dolor vitae arcu egestas, vel
          interdum justo bibendum. Cras convallis nulla sit amet eros interdum,
          a aliquet metus fermentum. Fusce dictum est libero, vitae rhoncus
          lectus lacinia sed. Nunc ullamcorper eros a arcu hendrerit laoreet.
          Phasellus elementum feugiat orci, sed consequat mauris luctus vel.
          Praesent id dolor id lorem ultrices feugiat.
        </p>
      </GridItem>
    </Grid>
  </div>
</template>

<script>
import AddIcon from 'tui/components/icons/Add';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import SidePanel from 'tui/components/sidepanel/SidePanel';
import SidePanelNav from 'tui/components/sidepanel/SidePanelNav';
import SidePanelNavButtonItem from 'tui/components/sidepanel/SidePanelNavButtonItem';
import SidePanelNavGroup from 'tui/components/sidepanel/SidePanelNavGroup';
import SidePanelNavLinkItem from 'tui/components/sidepanel/SidePanelNavLinkItem';

export default {
  components: {
    AddIcon,
    ButtonIcon,
    Grid,
    GridItem,
    SidePanel,
    SidePanelNav,
    SidePanelNavButtonItem,
    SidePanelNavGroup,
    SidePanelNavLinkItem,
  },

  data() {
    return {
      ariaLabel: 'SidePanelNav label',
      itemGroups: [
        {
          items: [
            { id: 1, text: 'Item 1', action: '#item1' },
            { id: 2, text: 'Item 2', action: '#item2' },
            { id: 3, text: 'Item 3', action: '#item3' },
            { id: 4, text: 'Item 4', action: '#item4' },
            { id: 5, text: 'Item 5', action: '#item5' },
          ],
          title: 'Group 1a',
          type: 'links',
        },
        {
          items: [
            { id: 6, text: 'Item 6', action: '#item6' },
            { id: 7, text: 'Item 7', action: '#item7' },
            { id: 8, text: 'Item 8', action: '#item8' },
            { id: 9, text: 'Item 9', action: '#item9' },
            { id: 10, text: 'Item 10', action: '#item10' },
          ],
          title: 'Group 2b',
          type: 'buttons',
        },
        {
          items: [
            { id: 11, text: 'Item 11', action: '#item11' },
            { id: 12, text: 'Item 12', action: '#item12' },
            { id: 13, text: 'Item 13', action: '#item13' },
            { id: 14, text: 'Item 14', action: '#item14' },
          ],
          type: 'links',
        },
      ],

      selectedItem: 3,
    };
  },

  methods: {
    navChange(selection) {
      console.log(selection);
    },
  },
};
</script>

<style lang="scss">
.tui-sidePanelNavExample {
  @include tui-font-body();
}
</style>
