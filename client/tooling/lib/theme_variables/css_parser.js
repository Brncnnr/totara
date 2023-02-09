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

const {
  isFail,
  parserState,
  match,
  discard,
  lit,
  any,
  sequence,
  many,
  map,
} = require('../parser_combinator');

const commentRegex = /(\/\*.*?\*\/)/gs;

// create parsers to eat whitespace, comments, and at-rules
// (which we can ignore)
const whitespace = discard(match(/^\s+/));
const comment = discard(match(/^(\/\*.*?\*\/)/));
const atRule = discard(match(/^@[^;]*;/));

// parse property and value
const property = map(match(/^([^:;}]+):\s+([^:;}]+);?/), match => {
  const property = match[1].replace(commentRegex, '').trim();
  const value = match[2].replace(commentRegex, '').trim();
  return { type: 'property', property, value };
});

// parse theme control comments
const controlComment = map(
  match(/^(\/\*\s*(theme:(\w+)\s*(.*?))\s*\*\/)/),
  match => {
    let args = match[4] || null;
    return { type: 'control_comment', commentType: 'theme:' + match[3], args };
  }
);

// parse content of rule { }
const ruleBody = many(any(whitespace, controlComment, comment, property));

// parse rule - `.foo { ... }`
const rule = map(
  sequence(match(/^[^{]+/), lit('{'), ruleBody, lit('}')),
  ([selector, , ruleBody]) => ({
    type: 'rule',
    selectorText: selector[0].trim(),
    children: ruleBody,
  })
);

// parse css file
const cssDocument = many(any(whitespace, atRule, comment, rule));

/**
 * Parse a CSS file to an AST.
 *
 * @param {string} css
 * @returns {array}
 */
function parseCss(css) {
  const val = parserState(css);
  const result = cssDocument(val);
  if (isFail(result)) {
    throw new Error('Invalid CSS at offset ' + result.index);
  }
  return result;
}

module.exports = { parseCss };
