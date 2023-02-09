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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module tui_charts
-->

<template>
  <div>
    <div
      v-if="header && header.trim() !== ''"
      class="tui-chartJs__header"
      :title="header"
    >
      {{ header }}
    </div>
    <canvas
      ref="canvas"
      v-bind="canvasAttributes"
      :aria-label="label"
      role="img"
      @click="clicked"
      @mousemove="hovered"
    >
      <slot :props="{ type, data, options }"><p v-text="label"/></slot>
    </canvas>
  </div>
</template>

<script>
import Chart from 'chart.js';
import theme from 'tui/theme';
import 'chartjs-plugin-doughnutlabel';

const hasOwnProperty = Object.prototype.hasOwnProperty;

Chart.defaults.global.defaultColor = theme.getVar('color-chart-background-1');
Chart.defaults.global.defaultFontSize = parseInt(
  theme.getVar('font-size-chart-default')
);

let defaultColors = [
  {
    // 01
    baseColor: theme.getVar('color-chart-background-1'),
    transparentColor: theme.getVar('color-chart-transparent-1'),
  },
  {
    // 02
    baseColor: theme.getVar('color-chart-background-2'),
    transparentColor: theme.getVar('color-chart-transparent-2'),
  },
  {
    // 03
    baseColor: theme.getVar('color-chart-background-3'),
    transparentColor: theme.getVar('color-chart-transparent-3'),
  },
  {
    // 04
    baseColor: theme.getVar('color-chart-background-4'),
    transparentColor: theme.getVar('color-chart-transparent-4'),
  },
  {
    // 05
    baseColor: theme.getVar('color-chart-background-5'),
    transparentColor: theme.getVar('color-chart-transparent-5'),
  },
  {
    // 06
    baseColor: theme.getVar('color-chart-background-6'),
    transparentColor: theme.getVar('color-chart-transparent-6'),
  },
  {
    // 07
    baseColor: theme.getVar('color-chart-background-7'),
    transparentColor: theme.getVar('color-chart-transparent-7'),
  },
];

const pickColor = function(index) {
  return defaultColors[index % defaultColors.length];
};

const applyUndefinedProperties = function(from, to) {
  for (let def in from) {
    if (typeof to[def] === 'undefined' && hasOwnProperty.call(from, def)) {
      to[def] = from[def];
    }
  }
};

// Since ChartJs doesn't support default colors, we have to implement this functionality manually
// as advised by ChartJs devs https://github.com/chartjs/Chart.js/issues/1618
Chart.pluginService.register({
  beforeUpdate(chart) {
    switch (chart.config.type) {
      case 'bar': {
        // We need to apply colors differently depending on whether the bars are stacked...
        chart.data.datasets.forEach((dataset, index) => {
          if (
            typeof chart.options.scales.xAxes[0].stacked === 'boolean' &&
            chart.options.scales.xAxes[0].stacked &&
            typeof chart.options.scales.yAxes[0].stacked === 'boolean' &&
            chart.options.scales.yAxes[0].stacked
          ) {
            const baseColor = pickColor(index).baseColor;
            let defaults = {
              backgroundColor: baseColor,
            };

            applyUndefinedProperties(defaults, dataset);
          } else {
            if (typeof dataset.backgroundColor !== 'undefined') {
              return;
            }

            let backgroundColors = [];

            dataset.data.forEach((value, index) => {
              backgroundColors.push(pickColor(index).baseColor);
            });

            dataset.backgroundColor = backgroundColors;
          }
        });
        chart.config.options.scales.xAxes[0].ticks.autoSkip = false;
        break;
      }

      case 'radar': {
        chart.data.datasets.forEach((dataset, index) => {
          const baseColor = pickColor(index).baseColor;
          let transparentColor = pickColor(index).transparentColor;

          if (typeof transparentColor === 'undefined') {
            transparentColor = baseColor;
          }

          let defaults = {
            borderColor: baseColor,
            backgroundColor: transparentColor,
            pointBackgroundColor: baseColor,
            pointBorderColor: baseColor,
            fill: true,
          };

          applyUndefinedProperties(defaults, dataset);
        });
        break;
      }

      // Fallthrough intended
      case 'line':
      case 'scatter':
      case 'bubble': {
        chart.data.datasets.forEach((dataset, index) => {
          const baseColor = pickColor(index).baseColor;
          let transparentColor = pickColor(index).transparentColor;

          if (typeof transparentColor === 'undefined') {
            transparentColor = baseColor;
          }

          let defaults = {
            borderColor: baseColor,
            backgroundColor: transparentColor,
            pointBackgroundColor: baseColor,
            pointBorderColor: baseColor,
            fill: false,
          };

          applyUndefinedProperties(defaults, dataset);
        });

        break;
      }

      // Fallthrough intended
      case 'doughnut':
      case 'pie':
      case 'polarArea': {
        chart.data.datasets.forEach(dataset => {
          if (!dataset.backgroundColor) {
            dataset.backgroundColor = [];
            dataset.data.forEach((val, index) => {
              dataset.backgroundColor.push(pickColor(index).baseColor);
            });
          }
        });
      }
    }
  },
});

export default {
  name: 'ChartJs',

  props: {
    header: {
      type: String,
      default: '',
    },
    ariaLabel: {
      type: String,
      default: '',
    },
    type: {
      required: true,
      type: String,
      validator: value =>
        [
          'line',
          'bar',
          'radar',
          'pie',
          'doughnut',
          'polarArea',
          'bubble',
          'scatter',
        ].indexOf(value) !== -1,
    },
    data: {
      required: true,
      type: Object,
    },
    options: {
      type: Object,
      default: function() {
        return {};
      },
    },
    canvasAttributes: {
      type: Object,
      default() {
        return {};
      },
    },
  },

  computed: {
    config() {
      return {
        type: this.type,
        data: this.data,
        options: this.options,
      };
    },

    label() {
      return this.ariaLabel.trim() !== '' ? this.ariaLabel : this.header;
    },
  },

  watch: {
    config: {
      deep: true,
      handler() {
        this.renderChart();
      },
    },
    type() {
      this.renderChart();
    },
  },

  mounted() {
    this.renderChart();
  },

  beforeDestroy() {
    this.chart.destroy();
  },

  methods: {
    renderChart() {
      const root = this.$refs.canvas;

      if (this.chart) {
        this.chart.destroy();
      }

      this.chart = new Chart(root, this.config);
    },

    clicked(e) {
      this.$emit(
        'click',
        e,
        this.$refs.canvas.getContext('2d'),
        this.chart,
        Chart
      );
    },

    hovered(e) {
      this.$emit(
        'hover',
        e,
        this.$refs.canvas.getContext('2d'),
        this.chart,
        Chart
      );
    },
  },
};
</script>

<style lang="scss">
:root {
  // Chart default font size
  --font-size-chart-default: 14; // It has to be without units
  --font-size-chart-doughnut-label: 16; // It has to be without units
  --font-size-chart-doughnut-percentage-label: 30; // It has to be without units
}

.tui-chartJs__header {
  @include tui-font-heading-small();
  padding-bottom: var(--gap-7);
  overflow: hidden;
  white-space: nowrap;
  text-align: center;
  text-overflow: ellipsis;
}
</style>
