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

import os
import pickle
import time
import unittest
from flask import current_app
from unittest.mock import patch, mock_open
from service.app import create_app
from service.tests.tests_api.tests_route.authentication_utils import AuthenticationUtils
from service.tests.util_objects import SyntheticObjects


class TestRequestUserItems(unittest.TestCase):
    """
    The test object to test units of the `/user-items` endpoint of the ML Service
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixture before exercising it
        """
        os.environ["FLASK_ENV"] = "testing"
        self.tenant = "1"
        synthetic = SyntheticObjects()
        self.user_features = synthetic.features
        self.positive_int_map = {}
        self.item_type_map = {}
        with patch("service.app.os.path.isfile", return_value=True):
            read_data = pickle.dumps(
                obj={
                    self.tenant: {
                        "msg": "success",
                        "model": 1,
                        "mappings": synthetic.true_test_mapping,
                        "item_features": synthetic.features,
                        "user_features": self.user_features,
                        "item_type_map": self.item_type_map,
                        "positive_interactions_map": self.positive_int_map,
                    }
                }
            )
            mock_open_test = mock_open(read_data=read_data)
            with patch("builtins.open", mock_open_test):
                app = create_app()
        self.client = app.test_client()
        self.longMessage = False
        self.totara_user_id = "2"
        self.n_items = 3
        self.item_type = "engage_article"
        self.test_recommendations = [
            [f"test_type{n}", 0.1111 * (12 - n)] for n in range(3, self.n_items + 3)
        ]
        with app.app_context():
            secret_key = current_app.config.get("TOTARA_KEY")

        headers_producer = AuthenticationUtils(
            timestamp=time.time(), secret_key=secret_key
        )
        self.headers = headers_producer.create_headers()

    @patch(
        target=(
            "service.api.route.request_user_items.PredictRecommender"
            ".get_user_recommendations"
        )
    )
    def test_endpoint_http_status(self, mock_predictor) -> None:
        """
        This method tests if the status code of the GET request to the "
        "`/user-items`endpoint is 200
        """
        mock_predictor.return_value = self.test_recommendations
        test_response = self.client.get(
            "/user-items",
            headers=self.headers,
            query_string={
                "item_type": self.item_type,
                "tenant": self.tenant,
                "totara_user_id": self.totara_user_id,
                "n_items": self.n_items,
            },
        )
        self.assertEqual(
            first=test_response.status_code,
            second=200,
            msg=(
                "The status code of the GET request at '/user-items' with "
                "valid data is not 200"
            ),
        )

    def test_user_items_response_no_tenant(self) -> None:
        """
        This method tests if the view function of the GET request to the "
        "`/user-items`endpoint is as expected with invalid tenant
        """
        test_response = self.client.get(
            "/user-items",
            headers=self.headers,
            query_string={
                "item_type": self.item_type,
                "tenant": "2",
                "totara_user_id": self.totara_user_id,
                "n_items": self.n_items,
            },
        )

        response_content = test_response.get_json()
        self.assertEqual(
            first=response_content["success"],
            second=False,
            msg=(
                "The 'success' value of the response of the GET request at"
                "'/user-items' with invalid tenant for user recommendations is "
                f"'{response_content['success']}' while it should have been 'False'"
            ),
        )

    def test_user_items_response_no_user(self) -> None:
        """
        This method tests if the view function of the GET request to the "
        "`/user-items`endpoint is as expected with invalid user
        """
        test_response = self.client.get(
            "/user-items",
            headers=self.headers,
            query_string={
                "item_type": self.item_type,
                "tenant": self.tenant,
                "totara_user_id": "1000",
                "n_items": self.n_items,
            },
        )

        response_content = test_response.get_json()
        self.assertEqual(
            first=response_content["success"],
            second=False,
            msg=(
                "The 'success' value of the response of the GET request at"
                "'/user-items' with invalid user for user recommendations is "
                f"'{response_content['success']}' while it should have been 'False'"
            ),
        )

    @patch(
        target=(
            "service.api.route.request_user_items.PredictRecommender"
            ".get_user_recommendations"
        )
    )
    def test_user_items_response(self, mock_predictor) -> None:
        """
        This method tests if the view function of the GET request to the "
        "`/user-items`endpoint is valid and calls the correct objects
        """
        mock_predictor.return_value = self.test_recommendations
        test_response = self.client.get(
            "/user-items",
            headers=self.headers,
            query_string={
                "item_type": self.item_type,
                "tenant": self.tenant,
                "totara_user_id": self.totara_user_id,
                "n_items": self.n_items,
            },
        )

        self.assertEqual(
            first=mock_predictor.call_args,
            second=unittest.mock.call(
                items_type=self.item_type,
                n_items=self.n_items,
                totara_id=self.totara_user_id,
                user_features=self.user_features,
                item_type_map=self.item_type_map,
                positive_inter_map=self.positive_int_map,
            ),
            msg=(
                "The `get_user_recommendations` method of the `PredictRecommender` "
                "class called with \n"
                f"{mock_predictor.call_args}\nwhile the following call "
                "was expected \n"
                f"call(items_type='{self.item_type}', n_items={self.n_items}, "
                f"totara_id='{self.totara_user_id}', user_features="
                f"'{self.user_features}', item_type_map='{self.item_type_map}', "
                f"positive_inter_map='{self.positive_int_map}')"
            ),
        )

        response_items = test_response.get_json()["items"]
        for j, test_item in enumerate(self.test_recommendations):
            self.assertEqual(
                first=(response_items[j][0], response_items[j][1]),
                second=(test_item[0], f"{test_item[1]: .4f}"),
                msg=(
                    "The response of the GET request at '/user-items' with valid "
                    f"data for recommended content {test_item[0]} is\n"
                    f"({response_items[j][0]}, {response_items[j][1]})\n"
                    "while the expectation is\n"
                    f"({test_item[0]}, "
                    f"{test_item[1]:.4f})"
                ),
            )
