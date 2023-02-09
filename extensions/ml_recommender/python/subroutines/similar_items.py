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
import pandas as pd


class SimilarItems:
    """
    This is a conceptual representation for generating the list of similar items for the
    items
    """

    def __init__(self, item_mapping=None, item_representations=None, num_items=10):
        """
        Constructor method
        :param item_mapping: A dictionary where keys are Totara item ids and values are
            internal item ids
        :type item_mapping: dict
        :param item_representations: The latent representations of the items in the
            shape `[n_items, num_components]`
        :type item_representations: np.float32 array
        :param num_items: The number of similar item recommendations for each item,
            defaults to 10
        :type num_items: int, optional
        """
        self.item_mapping = item_mapping
        self.mapping_rev = {v: k for k, v in item_mapping.items()}
        self.item_representations = item_representations
        self.num_items = num_items

    def __get_items(self, item_meta):
        """
        Returns `num_items` number of items (as defined in the class constructor method)
        similar to the item with internal id `internal_idx`
        :param item_meta: Totara id, and Internal id of the item whose similar items are
            being sought
        :type item_meta: tuple (`totara_id`, `internal_id`)
        :return: A list of (`totara_id`, `similarity_score`) of `num_items` that are
            similar to the given item
        :rtype: list
        """
        # Cosine similarity
        dot_prods_with_item = self.item_representations.dot(
            self.item_representations[item_meta[1], :]
        )
        item_norms = np.linalg.norm(self.item_representations, axis=1)
        cosine_denominators = item_norms * item_norms[item_meta[1]]
        cosine_scores = list(enumerate(dot_prods_with_item / cosine_denominators))

        scores = [(self.mapping_rev[x[0]], x[1]) for x in cosine_scores]
        scores.sort(key=lambda tup: tup[1], reverse=True)
        scores = [x for x in scores if x[0] != item_meta[0]]
        similar_items = scores[: self.num_items]

        return similar_items

    def all_items(self):
        """
        Loops through each `target_iid` (target internal id) of items and creates a list
        of `num_items` (number of similar items defined in the class constructor
        method).
        :return: A pandas DataFrame with three columns `target_iid`, `similar_iid`, and
            `ranking`
        :rtype: DataFrame
        """
        similar_items = []
        for item in self.item_mapping.items():
            i_similar = self.__get_items(item_meta=item)
            for s_item in i_similar:
                similar_items.append(
                    {
                        "target_iid": item[0],
                        "similar_iid": s_item[0],
                        "ranking": s_item[1],
                    }
                )

        return pd.DataFrame(similar_items)
