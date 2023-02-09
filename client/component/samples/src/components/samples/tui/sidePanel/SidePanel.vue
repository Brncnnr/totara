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

<template>
  <div class="tui-sidePanelExample">
    <Grid>
      <GridItem :units="6">
        <SidePanel
          :initially-open="true"
          :overflows="false"
          :flush="false"
          :animated="false"
        >
          <template>
            <Grid
              class="tui-sidePanelExample__content"
              direction="vertical"
              :use-vertical-gap="false"
            >
              <GridItem :units="3">
                <h3>Title (original left)</h3>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                <h4>Comments</h4>
              </GridItem>
              <GridItem :units="8">
                <p>
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                  Suspendisse sit amet erat ex. Sed ac felis auctor, molestie
                  orci eget, semper tortor. Curabitur non elementum nisi.
                </p>

                <p>
                  Donec sit amet venenatis ligula, at congue massa. In ac dolor
                  ante. Mauris faucibus, nulla consectetur scelerisque
                  efficitur, risus leo pharetra mi, non vehicula tellus elit
                  eget massa. Quisque feugiat eros et aliquam tempus.
                </p>

                <p>
                  Maecenas ut ornare sapien. Nulla sed rutrum ante. Pellentesque
                  habitant morbi tristique senectus et netus et malesuada fames
                  ac turpis egestas.
                </p>

                <p>
                  In turpis purus, feugiat sed commodo vitae, hendrerit in
                  tortor. Ut sed risus dolor. Vestibulum sed sapien ultrices
                  ipsum interdum facilisis nec non augue. Nulla et tellus id
                  ipsum congue aliquet in a quam.
                </p>

                <p>
                  Aenean cursus dolor vitae arcu egestas, vel interdum justo
                  bibendum. Cras convallis nulla sit amet eros interdum, a
                  aliquet metus fermentum. Fusce dictum est libero, vitae
                  rhoncus lectus lacinia sed.
                </p>

                <p>
                  Nunc ullamcorper eros a arcu hendrerit laoreet. Phasellus
                  elementum feugiat orci, sed consequat mauris luctus vel.
                  Praesent id dolor id lorem ultrices feugiat.
                </p>

                <p>
                  Praesent posuere nunc ipsum, egestas vestibulum odio ultrices
                  in. Duis suscipit lorem nisi, sed viverra odio blandit sit
                  amet. Fusce ultrices nibh odio, nec eleifend nibh efficitur
                  in.
                </p>

                <p>
                  Quisque eget lectus pulvinar, ornare enim sit amet, interdum
                  ligula. Donec lobortis magna ut malesuada tincidunt. Aenean
                  consectetur augue sit amet nulla blandit, eget feugiat eros
                  gravida.
                </p>

                <p>
                  Nulla pulvinar commodo congue. Nam sed sem at enim gravida
                  feugiat. Nunc eget lorem feugiat sem iaculis suscipit id ut
                  nisl. Nunc in ullamcorper nulla. Praesent eu sem feugiat est
                  faucibus dapibus. Fusce fermentum ipsum vitae nulla viverra
                  imperdiet.
                </p>
              </GridItem>
              <GridItem :units="1">
                <p>Some tags....</p>
              </GridItem>
            </Grid>
          </template>
        </SidePanel>
      </GridItem>

      <GridItem :units="6">
        <SidePanel direction="rtl" :overflows="false" :flush="false">
          <template>
            <aside>
              <Grid
                direction="vertical"
                :use-vertical-gap="false"
                :style="{
                  height: '100%',
                }"
              >
                <GridItem :units="3">
                  <h3>Title (original right)</h3>
                  <p>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                  </p>
                  <h4>Comments</h4>
                </GridItem>
                <GridItem :units="8">
                  <p>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                    Suspendisse sit amet erat ex. Sed ac felis auctor, molestie
                    orci eget, semper tortor. Curabitur non elementum nisi.
                  </p>

                  <p>
                    Donec sit amet venenatis ligula, at congue massa. In ac
                    dolor ante. Mauris faucibus, nulla consectetur scelerisque
                    efficitur, risus leo pharetra mi, non vehicula tellus elit
                    eget massa. Quisque feugiat eros et aliquam tempus.
                  </p>

                  <p>
                    Maecenas ut ornare sapien. Nulla sed rutrum ante.
                    Pellentesque habitant morbi tristique senectus et netus et
                    malesuada fames ac turpis egestas.
                  </p>

                  <p>
                    In turpis purus, feugiat sed commodo vitae, hendrerit in
                    tortor. Ut sed risus dolor. Vestibulum sed sapien ultrices
                    ipsum interdum facilisis nec non augue. Nulla et tellus id
                    ipsum congue aliquet in a quam.
                  </p>

                  <p>
                    Aenean cursus dolor vitae arcu egestas, vel interdum justo
                    bibendum. Cras convallis nulla sit amet eros interdum, a
                    aliquet metus fermentum. Fusce dictum est libero, vitae
                    rhoncus lectus lacinia sed.
                  </p>

                  <p>
                    Nunc ullamcorper eros a arcu hendrerit laoreet. Phasellus
                    elementum feugiat orci, sed consequat mauris luctus vel.
                    Praesent id dolor id lorem ultrices feugiat.
                  </p>

                  <p>
                    Praesent posuere nunc ipsum, egestas vestibulum odio
                    ultrices in. Duis suscipit lorem nisi, sed viverra odio
                    blandit sit amet. Fusce ultrices nibh odio, nec eleifend
                    nibh efficitur in.
                  </p>

                  <p>
                    Quisque eget lectus pulvinar, ornare enim sit amet, interdum
                    ligula. Donec lobortis magna ut malesuada tincidunt. Aenean
                    consectetur augue sit amet nulla blandit, eget feugiat eros
                    gravida.
                  </p>

                  <p>
                    Nulla pulvinar commodo congue. Nam sed sem at enim gravida
                    feugiat. Nunc eget lorem feugiat sem iaculis suscipit id ut
                    nisl. Nunc in ullamcorper nulla. Praesent eu sem feugiat est
                    faucibus dapibus. Fusce fermentum ipsum vitae nulla viverra
                    imperdiet.
                  </p>
                </GridItem>
                <GridItem :units="1">
                  <p>Some tags....</p>
                </GridItem>
              </Grid>
            </aside>
          </template>
        </SidePanel>
      </GridItem>
    </Grid>
  </div>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import SidePanel from 'tui/components/sidepanel/SidePanel';

export default {
  components: {
    Grid,
    GridItem,
    SidePanel,
  },
};
</script>

<style lang="scss">
.tui-sidePanelExample {
  height: 100%;

  .tui-sidePanel__content {
    padding: var(--gap-8);
  }
}
</style>
