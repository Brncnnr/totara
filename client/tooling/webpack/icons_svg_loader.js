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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

const { optimize } = require('svgo');
const {
  parserState,
  match,
  lit,
  sequence,
  many,
  map,
  optional,
  isFail,
} = require('../lib/parser_combinator');

const ident = map(match(/^[A-Za-z][\w-]+/), r => r[0]);
const quotedAttrValue = map(match(/^"([^"]+)"/), r => r[1]);
const attr = map(sequence(ident, lit('='), quotedAttrValue), r => ({
  name: r[0],
  value: r[2],
}));
const attrs = map(
  optional(sequence(attr, many(sequence(match(/^\s+/), attr)))),
  result => {
    if (!result) {
      return {};
    }
    const [firstAttr, restAttrs] = result;
    return [firstAttr, ...restAttrs.map(x => x[1])].reduce((acc, cur) => {
      acc[cur.name] = cur.value;
      return acc;
    }, {});
  }
);

function parseAttributes(str) {
  const val = parserState(str.trim());
  const result = attrs(val);
  if (isFail(result)) {
    throw new Error('SVG attributes: ' + str);
  }
  return result;
}

const options = {
  plugins: [
    {
      name: 'preset-default',
      params: {
        removeViewBox: false,
      },
    },
    'removeXMLNS',
    'sortAttrs',
    'removeDimensions',
  ],
};

module.exports = async function(src) {
  const callback = this.async();
  const result = optimize(src, options);
  const match = /^<svg(.*?)>(.*)<\/svg>$/.exec(result.data);
  if (!match) throw new Error('Invalid SVG');
  let paths = match[2];
  const attrs = parseAttributes(match[1]);
  const content = `export default [
  'svgc',
  ${JSON.stringify(attrs)},
  ${JSON.stringify(paths)},
];
`;
  callback(null, content);
};
