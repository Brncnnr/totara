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

from service.recommender.predict_subroutines.similar_items import SimilarItems
from service.recommender.predict_subroutines.user_to_items import UserToItems


class PredictRecommender:
    def __init__(
        self,
        model=None,
        algorithm="hybrid",
        mappings=None,
        items_features=None,
        num_threads=2,
    ):
        """
        This is the class constructor method

        :param model: The recommendation model object
        :type model: LightFM instance, mandatory
        :param algorithm: One of 'mf' (collaborative filtering), 'partial' (content
            based filtering without text processing), or 'hybrid' (content based
            filtering with text processing). The data preparation/processing depends on
            this parameter, defaults to 'hybrid'
        :type algorithm: str, mandatory
        :param mappings: A tuple of four objects or maps; the user id map (dictionary),
            user features map (list), the item id map (dictionary), and the item
            features map (list)
        :type mappings: tuple
        :param items_features: A sparse matrix of the item features of shape
            `n_items, n_features`
        :type items_features: `scipy.sparse.csr_matrix` instance
        :param num_threads: Number of parallel computation threads to use. Should not be
            higher than the number of physical cores, defaults to 2
        :type num_threads: int, optional
        """
        self.model = model
        self.algorithm = algorithm
        self.mappings = mappings
        self.item_features = items_features
        self.num_threads = int(num_threads)

    def get_similar_items(self, totara_id="engage_microlearning1", n_items=10):
        """
        This method calls the `SimilarItems` class and uses the method `get_items` to
        compute a list containing `n_items` items in descending order of similarity
        score with the given item

        :param totara_id: The Totara id of the item for which a list of similar items is
            requested
        :type totara_id: str
        :param n_items: Number of similar items requested
        :type n_items: int
        :return: List of tuples where the first element is similar item and the second
            element is similarity score
        :rtype: list
        """
        if totara_id not in self.mappings[2]:
            return [("bad request: no such item id", 0.0)]

        item_representations = self.model.get_item_representations(
            features=self.item_features
        )[1]
        similar_items_getter = SimilarItems(
            item_mapping=self.mappings[2],
            item_representations=item_representations,
            num_items=n_items,
        )
        return similar_items_getter.get_items(
            item_meta=(totara_id, self.mappings[2][totara_id])
        )

    def get_user_recommendations(
        self,
        totara_id=2,
        n_items=10,
        items_type="engage_microlearning",
        user_features=None,
        item_type_map=None,
        positive_inter_map=None,
    ):
        """
        This method calls the `UserToItems` class and uses the method `get_items` to get
        a list containing `n_items` that are recommended for user with id `totara_id`
        in descending order of their recommendation score

        :param totara_id: The Totara id of the user for whom the recommendations are
            requested
        :type totara_id: str
        :param n_items: Number of items requested
        :type n_items: int
        :param items_type: Type of the items requested
        :type items_type: str
        :param user_features: A sparse matrix of the user features of shape
            `n_users, n_features`
        :type user_features: `scipy.sparse.csr_matrix` instance
        :param item_type_map: A map of item ids and item types
        :type item_type_map: dict
        :param positive_inter_map: A of user ids  and list of items interacted by the
            users
        :type positive_inter_map: dict
        :return: List of tuples where the first element is recommended item and the
            second element is recommendation score
        :rtype: list
        """
        if totara_id not in self.mappings[0]:
            return [("bad request: no such user id", 0.0)]

        recommendations_getter = UserToItems(
            u_mapping=self.mappings[0],
            i_mapping=self.mappings[2],
            user_features=user_features,
            item_features=self.item_features,
            item_type_map=item_type_map,
            positive_inter_map=positive_inter_map,
            model=self.model,
            num_items=n_items,
            num_threads=self.num_threads,
        )
        return recommendations_getter.get_items(
            internal_uid=self.mappings[0][totara_id], item_type=items_type
        )
