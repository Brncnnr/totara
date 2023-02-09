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

/**
 * Simple parser combinator library.
 */

/**
 * @typedef {(input: ParserState) => any|Failure} Parser
 */

/**
 * @typedef {object} Failure
 * @property {true} fail
 * @property {ParserState} input
 * @property {number} index
 */

const failSymbol = Symbol('fail');

/**
 * Check if a value is a failure.
 *
 * @param {any|Failure} fail
 * @returns {boolean}
 */
const isFail = fail => fail && fail[failSymbol];

/**
 * Create a failure result.
 *
 * @param {ParserState} input
 */
const fail = input => {
  return {
    fail: true,
    [failSymbol]: true,
    input,
    index: input.index,
  };
};

/**
 * Create parser state object.
 *
 * @param {string} text
 * @returns {ParserState}
 */
const parserState = text => {
  return new ParserState(text);
};

class ParserState {
  constructor(text) {
    this.value = text;
    this.index = 0;
  }

  /**
   * Get value at current index.
   *
   * @param {number} length
   * @returns {string}
   */
  peek(length) {
    return this.value.substr(this.index, length);
  }

  /**
   * Increase index by.
   *
   * @param {number} count
   */
  advance(count) {
    this.index += count;
  }

  /**
   * Has source been read to end?
   *
   * @returns {boolean}
   */
  get finished() {
    return this.index >= this.value.length;
  }
}

/**
 * Generate parser that matches a regex.
 *
 * @param {RegExp} regex
 * @returns {Parser}
 */
const match = regex => input => {
  const result = regex.exec(input.peek());
  if (result) {
    input.advance(result[0].length);
    return result;
  }
  return fail(input);
};

/**
 * Generate parser that discards the provided parser's result.
 *
 * @param {RegExp} regex
 * @returns {Parser}
 */
const discard = parser => input => {
  const result = parser(input);
  return isFail(result) ? result : null;
};

/**
 * Create a parser that matches a literal string.
 *
 * @param {string} str
 * @returns {Parser}
 */
const lit = str => input => {
  if (input.peek(str.length) == str) {
    input.advance(str.length);
    return str;
  }
  return fail(input);
};

/**
 * Create a parser that matches any of the provided parsers (in order).
 *
 * @param {...Parser} parsers
 * @returns {Parser}
 */
const any = (...parsers) => input => {
  const pos = input.index;
  for (const parser of parsers) {
    const result = parser(input);
    if (!isFail(result)) {
      return result;
    }
    input.index = pos;
  }
  return fail(input);
};

/**
 * Create a parser that matches all of the provided parsers (in order) and
 * returns their results as an array.
 *
 * @param {...Parser} parsers
 * @returns {Parser}
 */
const sequence = (...parsers) => input => {
  const pos = input.index;
  const allResults = [];
  for (const parser of parsers) {
    const result = parser(input);
    if (isFail(result)) {
      input.index = pos;
      return result;
    }
    allResults.push(result);
  }
  return allResults;
};

/**
 * Create a parser that repeatedly matches the provided parser 0 to unlimited
 * times and returns their results as an array.
 *
 * @param {Parser} parser
 * @returns {Parser}
 */
const many = parser => input => {
  const results = [];
  while (!input.finished) {
    const pos = input.index;
    const result = parser(input);
    if (isFail(result)) {
      input.index = pos;
      break;
    }
    if (result !== null) {
      results.push(result);
    }
  }
  return results;
};

/**
 * Create a parser that eats failures of the provided parser.
 *
 * @param {Parser} parser
 * @returns {Parser}
 */
const optional = parser => input => {
  const pos = input.index;
  const result = parser(input);
  if (isFail(result)) {
    input.index = pos;
    return null;
  }
  return result;
};

/**
 * Create a parser that maps the output of the provided parser through a function.
 *
 * @param {Parser} parser
 * @param {(result: any) => any} mapper
 * @returns {Parser}
 */
const map = (parser, mapper) => input => {
  const result = parser(input);
  return isFail(result) ? result : mapper(result);
};

/**
 * Lazily define a parser. Used to create recursive parsers.
 *
 * @param {() => Parser} fn
 * @returns {Parser}
 */
const def = fn => {
  let parser = null;
  return input => {
    if (parser == null) {
      parser = fn();
    }
    return parser(input);
  };
}

module.exports = {
  parserState,
  match,
  discard,
  lit,
  any,
  sequence,
  many,
  optional,
  map,
  isFail,
  fail,
  def,
};
