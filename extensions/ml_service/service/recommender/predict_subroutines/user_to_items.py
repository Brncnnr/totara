"""
This file is part of Totara Enterprise Extensions.

Copyright (C) 2021 onward Totara Learning Solutions LTD

Totara Enterprise Extensions is provided only to Totara
Learning Solutions LTD's customers and partners, pursuant to
the terms and conditions of a separate agreement with Totara
Learning Solutions LTD or its affiliate.

If you do not have an agreement with Totara Learning Solutions
LTD, you may not access, use, modify, or distribute this software.
Please contact [licensing@totaralearning.com] for more information.

@author Amjad Ali <amjad.ali@totaralearning.com>
@package ml_service
"""

import numpy as np
import re

from service.recommender.config import Config


class UserToItems:
    """
    This is a conceptual representation for generating the item recommendations for
    users
    """

    def __init__(
        self,
        u_mapping=None,
        i_mapping=None,
        item_type_map=None,
        user_features=None,
        item_features=None,
        positive_inter_map=None,
        model=None,
        num_items=10,
        num_threads=2,
    ):
        """
        Constructor method

        :param u_mapping: A dictionary where keys are Totara user ids and values are
            internal user ids
        :type u_mapping: dict
        :param i_mapping: A dictionary where keys are Totara item ids and values are
            internal item ids
        :type i_mapping: dict
        :param item_type_map: A dictionary where keys are Totara item ids and values are
            item types, e.g., one of `container_course`, `container_workspace`,
            `engage_article`, `engage_microlearning`, and `totara_playlist`
        :type item_type_map: dict
        :param user_features: A sparse matrix of shape `[n_users, n_user_features]`
            - Each row contains that user's weight over features
        :type user_features: csr_matrix
        :param item_features: A sparse matrix of shape `[n_items, n_item_features]`
            - Each row contains that item's weight over features
        :type item_features: csr_matrix
        :param positive_inter_map: A dictionary where keys are the Totara user ids and
            values are lists of the Totara item ids that user has interacted with
        :type positive_inter_map: dict
        :param model: The model to be evaluated
        :type model: LightFM model instance
        :param num_items: Number of top ranked items user wants to be recommended,
            defaults to 10
        :type num_items: int, optional
        :param num_threads: Number of parallel computation threads to use, defaults to 2
        :type num_items: int, optional

        """
        self.u_mapping = u_mapping
        self.i_mapping = i_mapping
        self.u_mapping_rev = {v: k for k, v in u_mapping.items()}
        self.i_mapping_rev = {v: k for k, v in i_mapping.items()}
        self.item_type_map = item_type_map
        self.user_features = user_features
        self.item_features = item_features
        self.positive_inter_map = positive_inter_map
        self.model = model
        self.num_items = num_items
        self.num_threads = num_threads

    def top_x_by_cat(self, sorted_items, item_type="container_course"):
        """
        Returns top `num_items` recommended items where `num_items` is the instance
        variable of the class

        :param sorted_items: A list consisting of tuples where each tuple is composed of
            three items; Totara item id, ranking, and the item type
        :type sorted_items: list
        :param item_type: Type of the items requested
        :type item_type: str
        :return: A list of tuples where the first elements are the Totara ids of the
            items and the second ones the ranking.
        :rtype: list
        """
        cfg = Config()
        item_types_allowed = cfg.get_property(property_name="item_types")

        type_recommended = [(x[0], x[1]) for x in sorted_items if x[2] == item_type][
            : self.num_items
        ]
        removal_pattern = re.compile(pattern="|".join(item_types_allowed))
        recommended = [
            (removal_pattern.sub(repl="", string=x[0]), x[1]) for x in type_recommended
        ]
        return recommended

    def get_items(
        self, internal_uid=2, item_type="container_course", reduction_percentage=0.5
    ):
        """
        Returns top `num_items` recommended items where `num_items` is the instance
        variable of the class

        :param internal_uid: The internal id of the user for whom the recommendations
            are sought
        :type internal_uid: int
        :param item_type: Type of the items requested
        :type item_type: str
        :param reduction_percentage: The percentage of the range of unseen item's
            recommendation score by which the seen item's recommendation score will be
            reduced, defaults to 0.5
        :type reduction_percentage: float, optional
        :return: A list of tuples where the first elements are the Totara ids of the
            items and the second ones the ranking.
        :rtype: list
        """
        item_ids = np.fromiter(self.i_mapping.values(), dtype=np.int32)
        predictions = self.model.predict(
            user_ids=internal_uid,
            item_ids=item_ids,
            user_features=self.user_features,
            item_features=self.item_features,
            num_threads=self.num_threads,
        )
        seen_totara_id = []
        if self.u_mapping_rev[internal_uid] in self.positive_inter_map:
            seen_totara_id = self.positive_inter_map[self.u_mapping_rev[internal_uid]]
        seen_internal_id = [self.i_mapping[x] for x in seen_totara_id]
        unseen_internal_id = [
            x for x in range(predictions.shape[0]) if x not in seen_internal_id
        ]
        unseen_range = (
            predictions[unseen_internal_id].max()
            - predictions[unseen_internal_id].min()
        )
        for j in range(predictions.shape[0]):
            if j in seen_internal_id:
                predictions[j] = predictions[j] - unseen_range * reduction_percentage
        sorted_ids = predictions.argsort()[::-1]
        sorted_items = [
            (
                self.i_mapping_rev[x],
                predictions[x],
                self.item_type_map[self.i_mapping_rev[x]],
            )
            for x in sorted_ids
        ]
        best_with_score = self.top_x_by_cat(sorted_items, item_type=item_type)
        return best_with_score
