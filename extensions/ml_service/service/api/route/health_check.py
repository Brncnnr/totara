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

@author Cody Finegan <cody.finegan@totaralearning.com>
@package ml_service
"""

import socket
from datetime import datetime
from flask import current_app, jsonify, make_response
from flask.views import View
import requests.exceptions
from urllib import parse

from service.communicator.totara_graphql import TotaraGraphql
from service.recommender.recommender_health_check import RecommenderHealthCheck


def resolve_with_errors(errors: list, totara_info: dict):
    """
    To prepare a response as json object when there are errors in communication with
    Totara

    :param errors: A list of errors
    :type errors: list
    :param totara_info: A key-value pair of information about Totara
    :type totara_info: dict
    :return: A json object to be returned
    :rtype: json object
    """
    return make_response(
        jsonify({"success": False, "errors": errors, "totara": totara_info})
    )


class HealthCheck(View):
    """
    Perform a health check of the instance, including if we can communicate with Totara
    """

    methods = ["GET"]

    def __init__(self):
        """
        Class constructor method
        """
        self.time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        self.totara_url = current_app.config.get("TOTARA_URL")
        self.logs_dir = current_app.config.get("LOGS_DIR")
        self.recommender_model = current_app.recommender

    def dispatch_request(self):
        """
        This overrides the `dispatch_request` method of the parent class. This matches
        the URL and does the request dispatching.

        :return: The return value of the view or error handler
        :rtype: `flask.wrappers.Response`
        """
        totara_info = {"url": self.totara_url}

        # Check that we're able to resolve the totara hostname
        totara_hostname = parse.urlparse(self.totara_url).hostname

        if not totara_hostname:
            return resolve_with_errors(
                ["Unable to get Totara instance hostname"], totara_info
            )

        try:
            totara_info["totara_ip"] = socket.gethostbyname(totara_hostname)
        except socket.gaierror as e:
            error_code, error_message = e.args
            return resolve_with_errors(
                ["Unable to resolve the Totara instance hostname: %s" % error_message],
                totara_info,
            )

        # We need to see if we're able to communicate with the Totara instance
        # This is checked by calling the ml_service Graphql API
        q = TotaraGraphql(self.totara_url)

        try:
            check, elapsed = q.check()
            totara_info["elapsed_seconds"] = elapsed.total_seconds()
        except requests.exceptions.HTTPError as e:
            return resolve_with_errors(
                [
                    f"Unable to communicate with Totara, there was an error with the response: {str(e)}"
                ],
                totara_info,
            )
        except requests.exceptions.Timeout as cto:
            return resolve_with_errors(
                [
                    f"Timed out trying to connect to Totara (try again in a few seconds): {str(cto)}"
                ],
                totara_info,
            )
        except requests.exceptions.RequestException as cto:
            return resolve_with_errors(
                [f"Unable to communicate with Totara: {str(cto)}"], totara_info
            )

        auth_info = current_app.config.get("auth_info", {})
        totara_info = {**totara_info, **auth_info}

        if not check:
            return resolve_with_errors(
                [
                    (
                        "Could not connect to Totara instance. Please check your URL is"
                        " correct."
                    )
                ],
                totara_info,
            )

        # To get some information on the recommendation model
        r = RecommenderHealthCheck(self.recommender_model, self.logs_dir)
        recommender_health = r.recommender_health()
        totara_info = {**totara_info, **recommender_health}

        return make_response(jsonify({"success": True, "totara": totara_info}))
