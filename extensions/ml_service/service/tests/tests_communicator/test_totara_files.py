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

import hashlib
import time
import unittest
from unittest.mock import patch, MagicMock
from requests.exceptions import ConnectionError

from service.communicator.totara_files import TotaraFiles


class TestTotaraFiles(unittest.TestCase):
    """
    The test object to test the units of the `TotaraFiles` class in
    `service.communicator.totara_files`
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixtures before exercising it
        """
        self.test_totara_url = "https://totara.server"
        self.totara_key = "totara_key"
        self.totara_files = TotaraFiles(
            totara_url=self.test_totara_url, totara_key=self.totara_key
        )
        self.longMessage = False

    def test_make_headers(self) -> None:
        """
        This method tests if the headers are produced correctly in the `make_headers`
        method
        """
        test_time = int(time.time())
        computed_headers = self.totara_files.make_headers()
        test_key = hashlib.sha256(
            f"{test_time}{self.totara_key}".encode(encoding="utf8")
        ).hexdigest()
        test_headers = {"x-totara-ml-key": test_key, "x-totara-time": str(test_time)}

        self.assertDictEqual(
            d1=computed_headers,
            d2=test_headers,
            msg=(
                f"The computed headers are\n{computed_headers}\n"
                f"while these were expected to be\n{test_headers}"
            ),
        )

    def test_validate_schema(self) -> None:
        """
        To verify that the validate_schema method of the 'TotaraFiles' can detect
        invalid schemas provided with the Totara URL
        """
        self.totara_files.totara_url = "https://valid/schema"
        valid_schema_1_response = self.totara_files.validate_schema()

        self.totara_files.totara_url = "http://valid/schema"
        valid_schema_2_response = self.totara_files.validate_schema()

        self.totara_files.totara_url = "128.55.41.99 notreal.local"
        invalid_schema_response = self.totara_files.validate_schema()

        # Revert the totara_url of TotaraFiles class back to the original testing URL
        self.totara_files.totara_url = self.test_totara_url

        self.assertTrue(
            expr=valid_schema_1_response,
            msg=(
                "The response of the 'validate_schema' method of the TotaraFiles class "
                "was expected to be 'True' which is 'False' currently"
            ),
        )

        self.assertTrue(
            expr=valid_schema_2_response,
            msg=(
                "The response of the 'validate_schema' method of the TotaraFiles class "
                "was expected to be 'True' which is 'False' currently"
            ),
        )

        self.assertFalse(
            expr=invalid_schema_response,
            msg=(
                "The response of the 'validate_schema' method of the TotaraFiles class "
                "was expected to be 'False' which is 'True' currently"
            ),
        )

    @patch(target="service.communicator.totara_files.get")
    def test_download_200(self, mock_get) -> None:
        """
        This method tests if the `requests.get` method is called with the correct
        arguments and if the response of `download` method is as expected when
        `requests.get` returns 200 `status_code`
        """
        test_content = b"fake test content"
        test_file = "test_file.csv"
        mock_get.return_value = MagicMock(status_code=200, content=test_content)
        downloaded_content = self.totara_files.download(filename=test_file)

        expected_call = unittest.mock.call(
            url=(
                f"{self.test_totara_url}"
                f"/pluginfile.php/1/ml_recommender/export/{test_file}"
            ),
            headers=self.totara_files.make_headers(),
            verify=False,
            timeout=30,
        )
        self.assertEqual(
            first=mock_get.call_args,
            second=expected_call,
            msg=(
                "The get request was made with the arguments\n"
                f"{mock_get.call_args}\n"
                "while it was expected to be made with the arguments\n"
                f"{expected_call}"
            ),
        )
        expected_response = {"status": "success", "content": test_content}
        self.assertDictEqual(
            d1=downloaded_content,
            d2=expected_response,
            msg=(
                "The response of the 'download' method of 'TotaraFiles' class is\n"
                f"{downloaded_content}\n"
                f"while the expected response was\n"
                f"{expected_response}"
            ),
        )

    @patch(target="service.communicator.totara_files.time.sleep", return_value=None)
    @patch(target="service.communicator.totara_files.get")
    def test_download_202(self, mock_get, _) -> None:
        """
        This method checks if the `requests.get` method is called with the correct
        arguments and if the returned response is as expected when the `status_code` of
        `requests.get` is 202. The sleep time is removed in this test method so that
        request does not keep hanging for long time repeatedly when the data is not
        ready to fetch
        """
        test_file = "test_file.csv"
        mock_get.return_value = MagicMock(status_code=202)
        downloaded_content = self.totara_files.download(filename=test_file)

        expected_call = unittest.mock.call(
            url=(
                f"{self.test_totara_url}"
                f"/pluginfile.php/1/ml_recommender/export/{test_file}"
            ),
            headers=self.totara_files.make_headers(),
            verify=False,
            timeout=30,
        )
        self.assertEqual(
            first=mock_get.call_args,
            second=expected_call,
            msg=(
                "The get request was made with the arguments\n"
                f"{mock_get.call_args}\n"
                "while it was expected to be made with the arguments\n"
                f"{expected_call}"
            ),
        )
        expected_response = {"status": "fail", "content": ""}
        self.assertDictEqual(
            d1=downloaded_content,
            d2=expected_response,
            msg=(
                "The response of the 'download' method of 'TotaraFiles' class is\n"
                f"{downloaded_content}\n"
                f"while the expected response was\n"
                f"{expected_response}"
            ),
        )

    @patch(target="service.communicator.totara_files.get")
    def test_download_204(self, mock_get) -> None:
        """
        To test if the `requests.get` is called with the correct arguments and if the
        response of the `download` method is as expected when the `status_code` of
        `requests.get` is 204
        """
        mock_get.return_value = MagicMock(status_code=204)
        downloaded_content = self.totara_files.download(filename="tenants.csv")

        expected_call = unittest.mock.call(
            url=(
                f"{self.test_totara_url}"
                "/pluginfile.php/1/ml_recommender/export/tenants.csv"
            ),
            headers=self.totara_files.make_headers(),
            verify=False,
            timeout=30,
        )
        self.assertEqual(
            first=mock_get.call_args,
            second=expected_call,
            msg=(
                "The get request was made with the arguments\n"
                f"{mock_get.call_args}\n"
                "while it was expected to be made with the arguments\n"
                f"{expected_call}"
            ),
        )
        expected_response = {"status": "success", "content": b"tenants\n0\n"}
        self.assertDictEqual(
            d1=downloaded_content,
            d2=expected_response,
            msg=(
                "The response of the 'download' method of 'TotaraFiles' class is\n"
                f"{downloaded_content}\n"
                f"while the expected response was\n"
                f"{expected_response}"
            ),
        )

    @patch(target="service.communicator.totara_files.get")
    def test_download_connection_error(self, mock_get) -> None:
        """
        To test if the `requests.get` method is called with the correct arguments and if
        the response of the `download` method is as expected when the `requests.get`
        fails for connection error
        """
        test_file = "test_file.csv"
        test_exception = ConnectionError("Unsuccessful connection")
        mock_get.side_effect = test_exception
        downloaded_content = self.totara_files.download(filename=test_file)

        expected_call = unittest.mock.call(
            url=(
                f"{self.test_totara_url}"
                f"/pluginfile.php/1/ml_recommender/export/{test_file}"
            ),
            headers=self.totara_files.make_headers(),
            verify=False,
            timeout=30,
        )
        self.assertEqual(
            first=mock_get.call_args,
            second=expected_call,
            msg=(
                "The get request was made with the arguments\n"
                f"{mock_get.call_args}\n"
                "while it was expected to be made with the arguments\n"
                f"{expected_call}"
            ),
        )
        expected_response = {"status": "fail", "content": test_exception}
        self.assertDictEqual(
            d1=downloaded_content,
            d2=expected_response,
            msg=(
                "The response of the 'download' method of 'TotaraFiles' class is\n"
                f"{downloaded_content}\n"
                f"while the expected response was\n"
                f"{expected_response}"
            ),
        )

    def test_download_invalid_schema(self) -> None:
        """
        To test the response of the `download` method is as expected when the Totara URL
        does not have a valid schema
        """
        test_file = "test_file.csv"
        self.totara_files.totara_url = "128.55.41.99 notreal.local"
        downloaded_content = self.totara_files.download(filename=test_file)
        self.totara_files.totara_url = self.test_totara_url
        expected_response = {"status": "fail", "content": ""}
        self.assertDictEqual(
            d1=downloaded_content,
            d2=expected_response,
            msg=(
                "The response of the 'download' method of 'TotaraFiles' class is\n"
                f"{downloaded_content}\n"
                f"while the expected response was\n"
                f"{expected_response}"
            ),
        )
