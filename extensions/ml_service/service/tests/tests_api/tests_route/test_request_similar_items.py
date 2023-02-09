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


class TestRequestSimilarItems(unittest.TestCase):
    """
    The test object to test units of the `/similar-items` endpoint of the ML Service
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixture before exercising it
        """
        os.environ["FLASK_ENV"] = "testing"
        self.tenant = "1"
        synthetic = SyntheticObjects()
        with patch("service.app.os.path.isfile", return_value=True):
            read_data = pickle.dumps(
                obj={
                    self.tenant: {
                        "msg": "success",
                        "model": 1,
                        "mappings": synthetic.true_test_mapping,
                        "item_features": synthetic.features,
                    }
                }
            )
            mock_open_test = mock_open(read_data=read_data)
            with patch("builtins.open", mock_open_test):
                app = create_app()

        self.client = app.test_client()
        with app.app_context():
            secret_key = current_app.config.get("TOTARA_KEY")
        headers_producer = AuthenticationUtils(
            timestamp=time.time(), secret_key=secret_key
        )
        self.headers = headers_producer.create_headers()

        self.longMessage = False

        self.totara_item_id = "engage_microlearning1"
        self.n_items = 3
        self.test_similar_items = [
            [f"engage_article{n}", 0.1111 * n] for n in range(self.n_items + 1, 1, -1)
        ]

    @patch(
        target=(
            "service.api.route.request_similar_items.PredictRecommender"
            ".get_similar_items"
        )
    )
    def test_endpoint_http_status(self, mock_predictor) -> None:
        """
        This method tests if the status code of the GET request to the "
        "`/similar-items`endpoint is 200
        """
        mock_predictor.return_value = self.test_similar_items
        test_response = self.client.get(
            "/similar-items",
            headers=self.headers,
            query_string={
                "tenant": self.tenant,
                "totara_item_id": self.totara_item_id,
                "n_items": self.n_items,
            },
        )
        self.assertEqual(
            first=test_response.status_code,
            second=200,
            msg=(
                "The status code of the GET request at '/similar-items' with "
                "valid data is not 200"
            ),
        )

    def test_similar_items_response_no_tenant(self) -> None:
        """
        This method tests if the view function of the GET request to the "
        "`/similar-items`endpoint is as expected with invalid tenant
        """
        test_response = self.client.get(
            "/similar-items",
            headers=self.headers,
            query_string={
                "tenant": "2",
                "totara_item_id": self.totara_item_id,
                "n_items": self.n_items,
            },
        )

        response_content = test_response.get_json()
        self.assertEqual(
            first=response_content["success"],
            second=False,
            msg=(
                "The 'success' value of the response of the GET request at"
                "'/similar-items' with invalid tenant for similar items is "
                f"'{response_content['success']}' while it should have been 'False'"
            ),
        )

    def test_similar_items_response_no_item(self) -> None:
        """
        This method tests if the view function of the GET request to the "
        "`/similar-items`endpoint is as expected with invalid content
        """
        test_response = self.client.get(
            "/similar-items",
            headers=self.headers,
            query_string={
                "tenant": self.tenant,
                "totara_item_id": "engage_microlearning1000",
                "n_items": self.n_items,
            },
        )

        response_content = test_response.get_json()
        self.assertEqual(
            first=response_content["success"],
            second=False,
            msg=(
                "The 'success' value of the response of the GET request at"
                "'/similar-items' with invalid item for similar items is "
                f"'{response_content['success']}' while it should have been 'False'"
            ),
        )

    @patch(
        target=(
            "service.api.route.request_similar_items.PredictRecommender"
            ".get_similar_items"
        )
    )
    def test_similar_items_response(self, mock_predictor) -> None:
        """
        This method tests if the view function of the GET request to the "
        "`/similar-items` endpoint calls the correct objects with the correct inputs
        """
        mock_predictor.return_value = self.test_similar_items
        test_response = self.client.get(
            "/similar-items",
            headers=self.headers,
            query_string={
                "tenant": self.tenant,
                "totara_item_id": self.totara_item_id,
                "n_items": self.n_items,
            },
        )

        self.assertEqual(
            first=mock_predictor.call_args,
            second=unittest.mock.call(
                n_items=self.n_items, totara_id=self.totara_item_id
            ),
            msg=(
                "The `get_similar_items` method of the `PredictRecommender` class "
                f"called with \n{mock_predictor.call_args}\nwhile the following call "
                "was expected \n"
                f"call(n_items={self.n_items}, totara_id='{self.totara_item_id}')"
            ),
        )

        response_items = test_response.get_json()["items"]
        for j, test_item in enumerate(self.test_similar_items):
            self.assertEqual(
                first=(response_items[j][0], response_items[j][1]),
                second=(test_item[0], f"{test_item[1]: .4f}"),
                msg=(
                    "The response of the GET request at '/similar-items' with valid "
                    f"data for similar content {test_item[0]} is\n"
                    f"({response_items[j][0]}, {response_items[j][1]})\n"
                    "while it should have been\n"
                    f"({test_item[0]}, "
                    f"{test_item[1]:.4f})"
                ),
            )
