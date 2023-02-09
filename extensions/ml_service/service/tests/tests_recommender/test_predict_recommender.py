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

import unittest
from unittest.mock import patch, Mock

from service.tests.util_objects import SyntheticObjects
from service.recommender.predict_recommender import PredictRecommender


class TestPredictRecommender(unittest.TestCase):
    """
    This is to test if the PredictRecommender class behaves as expected
    """

    def setUp(self) -> None:
        """
        Hook method to set up the fixtures before exercising it
        """
        mock_model = Mock()
        self.mock_representations = ([1, 2, 3], [2, 3, 4])
        mock_model.get_item_representations.return_value = self.mock_representations
        self.algorithm = "hybrid"
        synthetic = SyntheticObjects()
        self.mappings = synthetic.true_test_mapping
        self.item_features = synthetic.features
        self.user_features = synthetic.features
        self.num_threads = 4

        self.predictor = PredictRecommender(
            model=mock_model,
            algorithm="hybrid",
            mappings=self.mappings,
            items_features=self.item_features,
            num_threads=self.num_threads,
        )
        self.longMessage = False

    @patch(target="service.recommender.predict_recommender.SimilarItems")
    def test_get_similar_items(self, mock_similar_items) -> None:
        """
        To test if the `get_similar_items` method initiates the `SimilarItems` class
        with the correct arguments and if the `get_items` method is called with the
        correct arguments
        """
        expected_response = [("2", 0.03), ("3", 0.04)]
        mock_similar_items.return_value.get_items.return_value = expected_response
        test_n_items = 2
        test_id = "engage_microlearning1"
        test_response = self.predictor.get_similar_items(
            totara_id=test_id, n_items=test_n_items
        )
        self.assertEqual(
            first=mock_similar_items.call_args,
            second=unittest.mock.call(
                item_mapping=self.mappings[2],
                item_representations=self.mock_representations[1],
                num_items=test_n_items,
            ),
            msg=(
                "The class 'SimilarItems' is initiated with "
                f"{mock_similar_items.call_args} while it was expected to be initiated "
                f"with call(item_mapping='{self.mappings[2]}', item_representations="
                f"'{self.mock_representations[1]}', num_items='{test_n_items}')"
            ),
        )

        self.assertEqual(
            first=mock_similar_items().get_items.call_args,
            second=unittest.mock.call(item_meta=(test_id, self.mappings[2][test_id])),
            msg=(
                "The method 'get_items' is called with the arguments "
                f"{mock_similar_items().get_items.call_args} while it was expected to "
                f"be called with call(item_meta='({test_id}, "
                f"{self.mappings[2][test_id]})')"
            ),
        )

        self.assertEqual(
            first=expected_response,
            second=test_response,
            msg=(
                f"The response of the 'get_similar_items' method is {test_response} "
                f"while it was expected to be {expected_response}"
            ),
        )

    def test_get_similar_items_noid(self) -> None:
        """
        To test if the `get_similar_items` returns a response that is expected when the
        given item id is not available in the data
        """
        test_id = "engage_majorlearning5"
        test_response = self.predictor.get_similar_items(totara_id=test_id, n_items=2)
        expected_response = [("bad request: no such item id", 0.0)]
        self.assertEqual(
            first=test_response,
            second=expected_response,
            msg=(
                f"The expected response of 'get_similar_items' was {expected_response} "
                f"while it is {test_response}"
            ),
        )

    @patch(target="service.recommender.predict_recommender.UserToItems")
    def test_get_user_recommendations(self, mock_user_to_items) -> None:
        """
        To test if the `get_user_recommendations` method initiates the `UserToItems`
        class with the correct arguments and if the `get_items` method is called with
        the correct arguments as expected
        """
        test_id = "2"
        test_n_items = 2
        item_type = "test_type"
        expected_response = [("2", 0.03), ("3", 0.04)]
        test_type_map = {
            "micro_learning1": "micro_learning",
            "micro_learning2": "micro_learning",
        }
        test_positive_inter_map = {"2": ["micro_learning1"], "3": []}

        mock_user_to_items.return_value.get_items.return_value = expected_response
        test_response = self.predictor.get_user_recommendations(
            totara_id=test_id,
            n_items=test_n_items,
            items_type=item_type,
            user_features=self.user_features,
            item_type_map=test_type_map,
            positive_inter_map=test_positive_inter_map,
        )

        self.assertEqual(
            first=mock_user_to_items().get_items.call_args,
            second=unittest.mock.call(
                internal_uid=self.mappings[0][test_id], item_type=item_type
            ),
            msg=(
                "The 'get_items' method of the 'UserToItems' class is called with "
                f"{mock_user_to_items().get_items.call_args} while it was expected to "
                f"be called with call(internal_uid='{self.mappings[0][test_id]}', "
                f"item_type='{item_type}')"
            ),
        )

        self.assertEqual(
            first=test_response,
            second=expected_response,
            msg=(
                f"The response of the 'get_similar_items' method is {test_response} "
                f"while it was expected to be {expected_response}"
            ),
        )

    def test_get_user_recommendations_noid(self) -> None:
        """
        To test if the `get_user_recommendations` returns correct response if the given
        user id does not exist in the data
        """
        test_id = "not_5"
        test_response = self.predictor.get_user_recommendations(
            totara_id=test_id,
            n_items=2,
            items_type="test_type",
            user_features=self.user_features,
        )

        expected_response = [("bad request: no such user id", 0.0)]

        self.assertEqual(
            first=test_response,
            second=expected_response,
            msg=(
                f"The expected response of 'get_similar_items' was {expected_response} "
                f"while it is {test_response}"
            ),
        )
