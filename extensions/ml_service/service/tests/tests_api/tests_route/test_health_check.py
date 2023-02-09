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
import pandas as pd
import time
import unittest
import requests.exceptions
from unittest.mock import patch
from flask import current_app

from service.app import create_app
from service.tests.tests_api.tests_route.authentication_utils import AuthenticationUtils
from service.tests.util_objects import Elapsed


class TestHealthCheck(unittest.TestCase):
    """
    The test object to test units of the Health Checks Endpoint of the ML Service
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixtures before exercising it
        """
        os.environ["FLASK_ENV"] = "testing"
        self.app = create_app()
        self.client = self.app.test_client()
        with self.app.app_context():
            secret_key = current_app.config.get("TOTARA_KEY")
            self.totara_url = current_app.config.get("TOTARA_URL")
            self.auth_info = current_app.config.get("auth_info", {})
        headers_producer = AuthenticationUtils(
            timestamp=time.time(), secret_key=secret_key
        )
        self.headers = headers_producer.create_headers()
        self.longMessage = False

    @patch(target="service.api.route.health_check.socket.gethostbyname")
    @patch(target="service.communicator.totara_graphql.TotaraGraphql.send")
    def test_http_status(self, mock_graphql, mock_hostname) -> None:
        """
        To test if the http status code is 200 when a legitimate request is made at
        /health-check
        """
        test_elapse = Elapsed()
        mock_graphql.return_value = (
            {"totara_webapi_status": {"status": "ok"}},
            test_elapse,
        )
        fake_test_ip = "0.0.0.0"
        mock_hostname.return_value = fake_test_ip
        test_response = self.client.get("/health-check", headers=self.headers)
        self.assertEqual(
            first=test_response.status_code,
            second=200,
            msg=(
                "The status code of the GET request at '/health-check' with "
                "valid data is not 200"
            ),
        )

    @patch(target="service.api.route.health_check.socket.gethostbyname")
    @patch(target="service.communicator.totara_graphql.TotaraGraphql.send")
    def test_health_check_content_without_recommender(
        self, mock_graphql, mock_hostname
    ) -> None:
        """
        To test that the returned json object is as expected. Does it have all the
        content that it is expected to be returned when a legitimate request is made at
        /health-check and the recommendation model is not trained yet
        """
        test_elapse = Elapsed()
        fake_test_ip = "0.0.0.0"
        mock_graphql.return_value = (
            {"totara_webapi_status": {"status": "ok"}},
            test_elapse,
        )
        mock_hostname.return_value = fake_test_ip
        test_raw_response = self.client.get("/health-check", headers=self.headers)
        test_response = test_raw_response.get_json()

        totara_info = {
            "url": self.totara_url,
            "totara_ip": fake_test_ip,
            "elapsed_seconds": test_elapse.total_seconds(),
            "recommendation_model": "false",
        }

        expected_response = {
            "success": True,
            "totara": {**totara_info, **self.auth_info},
        }

        self.assertIsInstance(
            obj=test_response,
            cls=dict,
            msg=(
                f"The returned object is of {type(test_response)} while it was "
                f"expected to be an instance of <class 'dict'>"
            ),
        )

        self.assertEqual(
            first=test_response["success"],
            second=expected_response["success"],
            msg=(
                "The 'success' value of the returned response is "
                f"{test_response['success']} while it was expected to be "
                f"{expected_response['success']}"
            ),
        )

        self.assertIsInstance(
            obj=test_response["totara"],
            cls=dict,
            msg=(
                "The 'totara' value of the returned response is "
                f"of type {type(test_response['totara'])} while it was expected to be "
                " an instance of <class 'dict'>"
            ),
        )

        self.assertDictEqual(
            d1=test_response["totara"],
            d2=expected_response["totara"],
            msg=(
                "The returned response from health check contains the dictionary "
                f"{test_response['totara']} while it was expected to be "
                f"{expected_response['totara']}"
            ),
        )

    @patch(target="service.recommender.recommender_health_check.path.exists")
    @patch(target="service.api.route.health_check.socket.gethostbyname")
    @patch(target="service.communicator.totara_graphql.TotaraGraphql.send")
    def test_health_check_content_with_recommender_no_logs(
        self, mock_graphql, mock_hostname, mock_exists
    ) -> None:
        """
        To test that the returned json object is as expected. Does it have all the
        content that it is expected to be returned when a legitimate request is made at
        /health-check, the recommendation model is successfully trained and the logs
        file does not exist on the file system
        """
        test_elapse = Elapsed()
        fake_test_ip = "0.0.0.0"
        mock_graphql.return_value = (
            {"totara_webapi_status": {"status": "ok"}},
            test_elapse,
        )
        mock_hostname.return_value = fake_test_ip
        mock_exists.return_value = False

        self.app.recommender = {"recommender_model": "fake"}
        test_raw_response = self.client.get("/health-check", headers=self.headers)
        test_response = test_raw_response.get_json()

        totara_info = {
            "url": self.totara_url,
            "totara_ip": fake_test_ip,
            "elapsed_seconds": test_elapse.total_seconds(),
            "recommendation_model": "true",
            "logs_file_exists": "false",
        }

        expected_response = {
            "success": True,
            "totara": {**totara_info, **self.auth_info},
        }

        self.assertDictEqual(
            d1=test_response["totara"],
            d2=expected_response["totara"],
            msg=(
                "The returned response from health check contains the dictionary "
                f"{test_response['totara']} while it was expected to be "
                f"{expected_response['totara']}"
            ),
        )

    @patch(target="service.recommender.recommender_health_check.pd.read_csv")
    @patch(target="service.recommender.recommender_health_check.path.exists")
    @patch(target="service.api.route.health_check.socket.gethostbyname")
    @patch(target="service.communicator.totara_graphql.TotaraGraphql.send")
    def test_health_check_content_with_recommender_with_logs(
        self, mock_graphql, mock_hostname, mock_exists, mock_read_csv
    ) -> None:
        """
        To test that the returned json object is as expected. Does it have all the
        content that it is expected to be returned when a legitimate request is made at
        /health-check, the recommendation model is successfully trained and logs file
        exists in the file system
        """
        test_elapse = Elapsed()
        fake_test_ip = "0.0.0.0"
        mock_graphql.return_value = (
            {"totara_webapi_status": {"status": "ok"}},
            test_elapse,
        )
        mock_hostname.return_value = fake_test_ip
        mock_exists.return_value = True
        test_logs = {
            "TIMESTAMP": ["2021-10-20 (16:18:30)", "2021-10-20 (16:18:30)"],
            "ALGORITHM": ["hybrid", "hybrid"],
            "TENANT_ID": ["0", "1"],
            "STATUS": ["success", "success"],
        }
        mock_read_csv.return_value = pd.DataFrame(data=test_logs)

        self.app.recommender = {"recommender_model": "fake"}
        test_raw_response = self.client.get("/health-check", headers=self.headers)
        test_response = test_raw_response.get_json()

        totara_info = {
            "url": self.totara_url,
            "totara_ip": fake_test_ip,
            "elapsed_seconds": test_elapse.total_seconds(),
            "recommendation_model": "true",
            "logs_file_exists": "true",
            "last_recommendation_model_training": str(
                {key: ", ".join(value) for (key, value) in test_logs.items()}
            ),
        }

        expected_response = {
            "success": True,
            "totara": {**totara_info, **self.auth_info},
        }

        self.assertDictEqual(
            d1=test_response["totara"],
            d2=expected_response["totara"],
            msg=(
                "The returned response from health check contains the dictionary "
                f"{test_response['totara']} while it was expected to be "
                f"{expected_response['totara']}"
            ),
        )

    @patch(target="service.api.route.health_check.socket.gethostbyname")
    @patch(target="service.communicator.totara_graphql.TotaraGraphql.send")
    def test_health_check_totara_communication_errors(
        self, mock_graphql: unittest.mock.MagicMock, mock_hostname
    ) -> None:
        """
        Assert that the HealthCheck script will correctly handle the timeout exceptions
        thrown when communicating with Totara.
        """
        test_elapse = Elapsed()
        fake_test_ip = "0.0.0.0"

        mock_graphql.return_value = (
            {"totara_webapi_status": {"status": "ok"}},
            test_elapse,
        )
        mock_hostname.return_value = fake_test_ip

        self.app.recommender = {"recommender_model": "fake"}

        # Force a HttpError
        mock_graphql.side_effect = requests.exceptions.HTTPError("No")

        test_raw_response = self.client.get("/health-check", headers=self.headers)
        test_response = test_raw_response.get_json()

        self.assertIn("errors", test_response)
        self.assertListEqual(
            test_response["errors"],
            [
                "Unable to communicate with Totara, there was an error with the response: No"
            ],
        )

        # Force a Timeout
        mock_graphql.side_effect = requests.exceptions.Timeout("Timedout")

        test_raw_response = self.client.get("/health-check", headers=self.headers)
        test_response = test_raw_response.get_json()

        self.assertIn("errors", test_response)
        self.assertListEqual(
            test_response["errors"],
            [
                "Timed out trying to connect to Totara (try again in a few seconds): Timedout"
            ],
        )

        # Force a RequestException
        mock_graphql.side_effect = requests.exceptions.RequestException("RE")

        test_raw_response = self.client.get("/health-check", headers=self.headers)
        test_response = test_raw_response.get_json()

        self.assertIn("errors", test_response)
        self.assertListEqual(
            test_response["errors"], ["Unable to communicate with Totara: RE"]
        )
