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

from datetime import datetime
from flask import current_app, render_template, url_for
from flask.views import View


class HomePage(View):
    """
    A view class for the endpoint of ML Service that prepares home page contents. It is
    inherited from the `flask.views.View`
    """

    methods = ["GET"]

    def __init__(self):
        """
        A class constructor method
        """
        self.time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        self.mode = current_app.config.get("APP_MODE")

    def gather_info(self) -> dict:
        """
        Prepares the information dictionary object t be included in the template of the
        home page

        :return:A dictionary containing some basic info
        :rtype: dict
        """
        return {
            "msg": "Welcome to the Totara Machine Learning Service - %s" % self.mode,
            "timestamp": self.time,
            "endpoints": {
                "url_health_check": url_for(endpoint="health_check", _external=True),
                "url_similar_items": url_for(endpoint="s_items", _external=True),
                "url_user_items": url_for(endpoint="user_items", _external=True),
            },
            "config": {"totara_url": current_app.config.get("TOTARA_URL")},
        }

    def dispatch_request(self):
        """
        This overrides the `dispatch_request` method of the parent class. This matches
        the URL and does the request dispatching.

        :return: The return value of the view or error handler
        :rtype: `flask.wrappers.Response`
        """
        return render_template("home.html", home_data=self.gather_info())
