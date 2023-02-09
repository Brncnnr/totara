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
import unittest
from service.app import create_app


class TestHomePage(unittest.TestCase):
    """
    The test object to test units of the Index Page of the ML Service
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixture before exercising it
        """
        os.environ["FLASK_ENV"] = "testing"
        app = create_app()
        client = app.test_client()
        self.landing = client.get("/")
        self.longMessage = False

    def test_page_http_status(self) -> None:
        """
        This method tests if the status code of the get request to the index page is 200
        """
        self.assertEqual(
            first=self.landing.status_code,
            second=200,
            msg="The status code of the GET request at '/' is not 200",
        )

    def test_page_has_correct_content(self) -> None:
        """
        This method tests if certain content is available in the returned html page
        """
        html = self.landing.data.decode()
        page_header = "<!DOCTYPE html>"
        page_style_tag = '<html lang="en" style="font-family:sans-serif">'
        health_checks_str = "Request health checks"
        similar_content_str = "Request similar content"
        user_recommendations_str = "Request user recommendations"

        self.assertIn(
            member=page_header,
            container=html,
            msg=f"The returned index page does not contain '{page_header}'",
        )

        self.assertIn(
            member=page_style_tag,
            container=html,
            msg=f"The returned index page does not contain '{page_style_tag}'",
        )

        self.assertIn(
            member=health_checks_str,
            container=html,
            msg=(
                "The returned index page does not contain string "
                f"'{health_checks_str}'"
            ),
        )

        self.assertIn(
            member=similar_content_str,
            container=html,
            msg=(
                "The returned index page does not contain string "
                f"'{similar_content_str}'"
            ),
        )

        self.assertIn(
            member=user_recommendations_str,
            container=html,
            msg=(
                "The returned index page does not contain string "
                f"'{user_recommendations_str}'"
            ),
        )
