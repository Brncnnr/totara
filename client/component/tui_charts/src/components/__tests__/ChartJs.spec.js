/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @module tui
 */

/* eslint-disable jest/no-standalone-expect */
/* eslint-disable jest/expect-expect */

import { shallowMount } from '@vue/test-utils';
import ChartJs from 'tui_charts/components/ChartJs';
import { axe } from 'jest-axe';

let mockDefaultColors = [
  {
    // 01
    baseColor: '#3869b1',
    transparentColor: 'rgba(#3869b1, 30)',
  },
  {
    // 02
    baseColor: '#da7e31',
    transparentColor: 'rgba(#da7e31, 30)',
  },
  {
    // 03
    baseColor: '#3f9852',
    transparentColor: 'rgba(#3f9852, 30)',
  },
  {
    // 04
    baseColor: '#cc2428',
    transparentColor: 'rgba(#cc2428, 30)',
  },
  {
    // 05
    baseColor: '#958c3d',
    transparentColor: 'rgba(#958c3d, 30)',
  },
  {
    // 06
    baseColor: '#6b4c9a',
    transparentColor: 'rgba(#6b4c9a, 30)',
  },
  {
    // 07
    baseColor: '#8c8c8c',
    transparentColor: 'rgba(#8c8c8c, 30)',
  },
];

// We are mocking theme to override css variables
jest.mock('tui/theme', () => {
  return {
    getVar(name) {
      let vars = {
        'font-size-chart-default': '14',
      };

      // This must be a local variable
      let mockDefaultColors = [
        {
          // 01
          baseColor: '#3869b1',
          transparentColor: 'rgba(#3869b1, 30)',
        },
        {
          // 02
          baseColor: '#da7e31',
          transparentColor: 'rgba(#da7e31, 30)',
        },
        {
          // 03
          baseColor: '#3f9852',
          transparentColor: 'rgba(#3f9852, 30)',
        },
        {
          // 04
          baseColor: '#cc2428',
          transparentColor: 'rgba(#cc2428, 30)',
        },
        {
          // 05
          baseColor: '#958c3d',
          transparentColor: 'rgba(#958c3d, 30)',
        },
        {
          // 06
          baseColor: '#6b4c9a',
          transparentColor: 'rgba(#6b4c9a, 30)',
        },
        {
          // 07
          baseColor: '#8c8c8c',
          transparentColor: 'rgba(#8c8c8c, 30)',
        },
      ];

      mockDefaultColors.forEach((value, i) => {
        vars[`color-chart-background-${i + 1}`] = value.baseColor;
        vars[`color-chart-transparent-${i + 1}`] = value.transparentColor
          ? value.transparentColor
          : value.baseColor;
      });

      return vars[name];
    },
  };
});

let wrapper;

describe('ChartJs', () => {
  beforeAll(() => {
    wrapper = shallowMount(ChartJs, {
      propsData: {
        type: 'bar',
        options: {},
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 9, 12, 56, 21, 23, 12, 19, 23, 12],
            },
          ],
        },
        header: 'This is an example header',
      },
    });
  });

  it('Renders correctly', () => {
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Initializes ChartJs', () => {
    // When ChartJs initializes it sets the following values...
    expect(wrapper.find('canvas').attributes('width')).toEqual('0');
    expect(wrapper.find('canvas').attributes('height')).toEqual('0');
    expect(wrapper.find('canvas').classes('chartjs-render-monitor')).toBeTrue();
  });

  [
    {
      propsData: {
        type: 'bar',
        options: {},
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 9, 12, 56, 21, 23, 12, 19, 23, 12],
            },
          ],
        },
        header: 'Bar chart',
      },
      expectation(chart) {
        let expectedColors = mockDefaultColors.map(color => color.baseColor);

        expectedColors.push(
          expectedColors[0],
          expectedColors[1],
          expectedColors[2]
        );

        expect(chart.data.datasets[0].backgroundColor).toEqual(expectedColors);
      },
    },
    {
      propsData: {
        type: 'bar',
        header: 'Stacked bar chart',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 15],
            },
            {
              label: 'Dataset 2',
              data: [11, 23, 53, 15, 24, 34, 12, 3, 16, 39],
            },
            {
              label: 'Dataset 3',
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 15],
            },
            {
              label: 'Dataset 4',
              data: [11, 23, 53, 15, 24, 34, 12, 3, 16, 39],
            },
            {
              label: 'Dataset 5',
              data: [2, 5, 12, 12, 14, 18, 11, 9, 0, 7, 14],
            },
          ],
        },
        options: {
          scales: {
            xAxes: [
              {
                stacked: true,
              },
            ],
            yAxes: [
              {
                stacked: true,
              },
            ],
          },
        },
      },
      expectation(chart) {
        let expectedColors = mockDefaultColors.map(color => color.baseColor);
        expectedColors = expectedColors.splice(0, 5);

        let actualColors = chart.data.datasets.map(
          ({ backgroundColor }) => backgroundColor
        );
        expect(expectedColors).toEqual(actualColors);
      },
    },
    {
      propsData: {
        type: 'polarArea',
        header: 'Polar Area',
        data: {
          labels: ['1', '2', '3'],
          datasets: [
            {
              data: [15, 11, 12],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedColors = mockDefaultColors
          .map(({ baseColor }) => baseColor)
          .splice(0, 3);

        chart.data.datasets.forEach(({ backgroundColor }) => {
          expect(backgroundColor).toEqual(expectedColors);
        });
      },
    },
    {
      propsData: {
        type: 'line',
        header: 'Line graph',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, -5],
            },
            {
              label: 'Dataset 2',
              data: [1, 23, 4, 5, 7, -5, 12, 34, 12, -2, 13],
            },
            {
              label: 'Dataset 3',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
            },
            {
              label: 'Dataset 4',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
            },
            {
              label: 'Dataset 5',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedBaseColors = mockDefaultColors
          .map(({ baseColor }) => baseColor)
          .splice(0, 5);
        let expectedAlphaColors = mockDefaultColors
          .map(({ transparentColor }) => transparentColor)
          .splice(0, 5);

        let actualBackgroundColor = chart.data.datasets.map(
          ({ backgroundColor }) => backgroundColor
        );
        let actualBorderColor = chart.data.datasets.map(
          ({ borderColor }) => borderColor
        );
        let actualPointBackgroundColor = chart.data.datasets.map(
          ({ pointBackgroundColor }) => pointBackgroundColor
        );
        let actualPointBorderColor = chart.data.datasets.map(
          ({ pointBorderColor }) => pointBorderColor
        );
        let actualFill = chart.data.datasets.map(({ fill }) => fill);

        expect(actualBackgroundColor).toEqual(expectedAlphaColors);
        expect(actualBorderColor).toEqual(expectedBaseColors);
        expect(actualPointBackgroundColor).toEqual(expectedBaseColors);
        expect(actualPointBorderColor).toEqual(expectedBaseColors);
        expect(actualFill).toEqual([false, false, false, false, false]);
      },
    },
    {
      propsData: {
        type: 'scatter',
        header: 'Scatter graph',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, -5],
            },
            {
              label: 'Dataset 2',
              data: [1, 23, 4, 5, 7, -5, 12, 34, 12, -2, 13],
            },
            {
              label: 'Dataset 3',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
            },
            {
              label: 'Dataset 4',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
            },
            {
              label: 'Dataset 5',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedBaseColors = mockDefaultColors
          .map(({ baseColor }) => baseColor)
          .splice(0, 5);
        let expectedAlphaColors = mockDefaultColors
          .map(({ transparentColor }) => transparentColor)
          .splice(0, 5);

        let actualBackgroundColor = chart.data.datasets.map(
          ({ backgroundColor }) => backgroundColor
        );
        let actualBorderColor = chart.data.datasets.map(
          ({ borderColor }) => borderColor
        );
        let actualPointBackgroundColor = chart.data.datasets.map(
          ({ pointBackgroundColor }) => pointBackgroundColor
        );
        let actualPointBorderColor = chart.data.datasets.map(
          ({ pointBorderColor }) => pointBorderColor
        );
        let actualFill = chart.data.datasets.map(({ fill }) => fill);

        expect(actualBackgroundColor).toEqual(expectedAlphaColors);
        expect(actualBorderColor).toEqual(expectedBaseColors);
        expect(actualPointBackgroundColor).toEqual(expectedBaseColors);
        expect(actualPointBorderColor).toEqual(expectedBaseColors);
        expect(actualFill).toEqual([false, false, false, false, false]);
      },
    },
    {
      propsData: {
        type: 'bubble',
        header: 'Bubble graph',
        data: {
          labels: ['1', '2', '3', '4', '5'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 75, 12, 56, 78],
            },
            {
              label: 'Dataset 2',
              data: [1, 23, 4, 5, 7],
            },
            {
              label: 'Dataset 3',
              data: [12, 42, 10, -5, 6],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedBaseColors = mockDefaultColors
          .map(({ baseColor }) => baseColor)
          .splice(0, 3);
        let expectedAlphaColors = mockDefaultColors
          .map(({ transparentColor }) => transparentColor)
          .splice(0, 3);

        let actualBackgroundColor = chart.data.datasets.map(
          ({ backgroundColor }) => backgroundColor
        );
        let actualBorderColor = chart.data.datasets.map(
          ({ borderColor }) => borderColor
        );
        let actualPointBackgroundColor = chart.data.datasets.map(
          ({ pointBackgroundColor }) => pointBackgroundColor
        );
        let actualPointBorderColor = chart.data.datasets.map(
          ({ pointBorderColor }) => pointBorderColor
        );
        let actualFill = chart.data.datasets.map(({ fill }) => fill);

        expect(actualBackgroundColor).toEqual(expectedAlphaColors);
        expect(actualBorderColor).toEqual(expectedBaseColors);
        expect(actualPointBackgroundColor).toEqual(expectedBaseColors);
        expect(actualPointBorderColor).toEqual(expectedBaseColors);
        expect(actualFill).toEqual([false, false, false]);
      },
    },
    {
      propsData: {
        type: 'radar',
        header: 'Radar chart',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, -5],
            },
            {
              label: 'Dataset 2',
              data: [1, 23, 4, 5, 7, -5, 12, 34, 12, -2, 13],
            },
            {
              label: 'Dataset 3',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedBaseColors = mockDefaultColors
          .map(({ baseColor }) => baseColor)
          .splice(0, 3);
        let expectedAlphaColors = mockDefaultColors
          .map(({ transparentColor }) => transparentColor)
          .splice(0, 3);

        let actualBackgroundColor = chart.data.datasets.map(
          ({ backgroundColor }) => backgroundColor
        );
        let actualBorderColor = chart.data.datasets.map(
          ({ borderColor }) => borderColor
        );
        let actualPointBackgroundColor = chart.data.datasets.map(
          ({ pointBackgroundColor }) => pointBackgroundColor
        );
        let actualPointBorderColor = chart.data.datasets.map(
          ({ pointBorderColor }) => pointBorderColor
        );
        let actualFill = chart.data.datasets.map(({ fill }) => fill);

        expect(actualBackgroundColor).toEqual(expectedAlphaColors);
        expect(actualBorderColor).toEqual(expectedBaseColors);
        expect(actualPointBackgroundColor).toEqual(expectedBaseColors);
        expect(actualPointBorderColor).toEqual(expectedBaseColors);
        expect(actualFill).toEqual([true, true, true]);
      },
    },
    {
      propsData: {
        type: 'doughnut',
        header: 'Doughnut',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 11],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedColors = mockDefaultColors.map(
          ({ baseColor }) => baseColor
        );

        expectedColors.push(
          expectedColors[0],
          expectedColors[1],
          expectedColors[2]
        );

        chart.data.datasets.forEach(({ backgroundColor }) => {
          expect(backgroundColor).toEqual(expectedColors);
        });
      },
    },
    {
      propsData: {
        type: 'pie',
        header: 'Pie',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 11],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedColors = mockDefaultColors.map(
          ({ baseColor }) => baseColor
        );

        expectedColors.push(
          expectedColors[0],
          expectedColors[1],
          expectedColors[2]
        );

        chart.data.datasets.forEach(({ backgroundColor }) => {
          expect(backgroundColor).toEqual(expectedColors);
        });
      },
    },
    {
      propsData: {
        type: 'polarArea',
        header: 'Polar area',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 11],
            },
          ],
        },
        options: {},
      },
      expectation(chart) {
        let expectedColors = mockDefaultColors.map(
          ({ baseColor }) => baseColor
        );

        expectedColors.push(
          expectedColors[0],
          expectedColors[1],
          expectedColors[2]
        );

        chart.data.datasets.forEach(({ backgroundColor }) => {
          expect(backgroundColor).toEqual(expectedColors);
        });
      },
    },
  ].forEach(({ propsData, expectation }) => {
    it('Sets default colors for a ' + propsData.header, () => {
      // Let's create a Chart component instance
      expectation(
        shallowMount(ChartJs, {
          propsData,
        }).vm.chart
      );
    });
  });

  it("Default colors won't be applied if provided by user...", () => {
    // When ChartJs initializes it sets the following values...
    let chart = shallowMount(ChartJs, {
      propsData: {
        type: 'radar',
        header: 'Radar chart',
        data: {
          labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
          datasets: [
            {
              label: 'Dataset 1',
              data: [15, 75, 12, 56, 78, 23, 12, 19, 23, -5],

              borderColor: '#0f0f0f',
              pointBorderColor: '#f0f0f0',
              fill: false,
            },
            {
              label: 'Dataset 2',
              data: [1, 23, 4, 5, 7, -5, 12, 34, 12, -2, 13],

              borderColor: '#0f0f0f',
              pointBackgroundColor: '#c0c0c0',
            },
            {
              label: 'Dataset 3',
              data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],

              borderColor: '#fff',
              backgroundColor: '#fff',
              pointBackgroundColor: '#fff',
              pointBorderColor: '#fff',
              fill: false,
            },
          ],
        },
        options: {},
      },
    }).vm.chart;

    // First dataset overridden:
    expect(chart.data.datasets[0].borderColor).toEqual('#0f0f0f');
    expect(chart.data.datasets[0].pointBorderColor).toEqual('#f0f0f0');
    expect(chart.data.datasets[0].fill).toEqual(false);

    // First dataset default:
    expect(chart.data.datasets[0].backgroundColor).toEqual(
      mockDefaultColors[0].transparentColor
    );
    expect(chart.data.datasets[0].pointBackgroundColor).toEqual(
      mockDefaultColors[0].baseColor
    );

    // Second dataset overridden:
    expect(chart.data.datasets[1].borderColor).toEqual('#0f0f0f');
    expect(chart.data.datasets[1].pointBackgroundColor).toEqual('#c0c0c0');

    // Second dataset default:
    expect(chart.data.datasets[1].backgroundColor).toEqual(
      mockDefaultColors[1].transparentColor
    );
    expect(chart.data.datasets[1].pointBorderColor).toEqual(
      mockDefaultColors[1].baseColor
    );
    expect(chart.data.datasets[1].fill).toEqual(true);

    // Third dataset overridden:
    expect(chart.data.datasets[2].borderColor).toEqual('#fff');
    expect(chart.data.datasets[2].backgroundColor).toEqual('#fff');
    expect(chart.data.datasets[2].pointBorderColor).toEqual('#fff');
    expect(chart.data.datasets[2].pointBackgroundColor).toEqual('#fff');
    expect(chart.data.datasets[2].fill).toEqual(false);
  });

  it('It passes header as an aria label if header is not set', () => {
    let anotherWrapper = shallowMount(ChartJs, {
      propsData: {
        type: 'bar',
        options: {},
        data: {},
        header: 'This is an example header',
        ariaLabel: 'This is the canvas label',
      },
    });

    expect(anotherWrapper.find('p').text()).toEqual('This is the canvas label');
    expect(anotherWrapper.find('canvas').attributes('aria-label')).toEqual(
      'This is the canvas label'
    );
  });

  it('should not have any accessibility violations', async () => {
    const results = await axe(wrapper.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });
});
