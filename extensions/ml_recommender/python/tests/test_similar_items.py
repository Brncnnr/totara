"""
This file is part of Totara Enterprise Extensions.

Copyright (C) 2020 onwards Totara Learning Solutions LTD

Totara Enterprise Extensions is provided only to Totara
Learning Solutions LTD's customers and partners, pursuant to
the terms and conditions of a separate agreement with Totara
Learning Solutions LTD or its affiliate.

If you do not have an agreement with Totara Learning Solutions
LTD, you may not access, use, modify, or distribute this software.
Please contact [licensing@totaralearning.com] for more information.

@author Amjad Ali <amjad.ali@totaralearning.com>
@package ml_recommender
@deprecated since Totara 17.0 ml_recommender has been deprecated.
"""

import numpy as np
import random
import unittest
from pandas import DataFrame

from subroutines.similar_items import SimilarItems


class TestSimilarItems(unittest.TestCase):
    """
    This class is set up to test units of the `SimilarItems` class
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        self.test_items_n = 20
        features_dimension = 100
        self.mock_mapping = dict(
            zip(
                ["item" + str(i) for i in range(1, (self.test_items_n + 1))],
                range(self.test_items_n),
            )
        )
        self.mock_item_representations = np.random.rand(
            self.test_items_n, features_dimension
        )
        self.similar_items = SimilarItems(
            item_mapping=self.mock_mapping,
            item_representations=self.mock_item_representations,
        )

    def test_get_items(self):
        """
        This method tests if the `__get_items` method of the `SimilarItems` class
        returns results as expected
        """
        test_item = random.choice(list(self.mock_mapping.items()))
        rev_mapping = {v: k for k, v in self.similar_items.item_mapping.items()}
        dot_prods_with_item = self.mock_item_representations.dot(
            self.mock_item_representations[test_item[1], :]
        )
        item_norms = np.linalg.norm(self.mock_item_representations, axis=1)
        cosine_denominators = item_norms * item_norms[test_item[1]]
        cosine_scores = list(enumerate(dot_prods_with_item / cosine_denominators))

        scores = [(rev_mapping[x[0]], x[1]) for x in cosine_scores]
        scores.sort(key=lambda tup: tup[1], reverse=True)
        scores = [x for x in scores if x[0] != test_item[0]]
        similar_items = scores[: self.similar_items.num_items]
        computed_items = self.similar_items._SimilarItems__get_items(
            item_meta=test_item
        )
        self.assertEqual(computed_items, similar_items)

    def test_all_items(self):
        """
        This method tests if the `all_items` method of `SimilarItems` class returns a
        pandas' DataFrame object
        and it has shape as expected
        """
        computed_similar_items = self.similar_items.all_items()
        self.assertIsInstance(computed_similar_items, DataFrame)
        self.assertEqual(
            computed_similar_items.shape,
            (self.test_items_n * self.similar_items.num_items, 3),
        )
