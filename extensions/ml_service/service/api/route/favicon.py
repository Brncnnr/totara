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

from flask.views import View
from flask import send_from_directory


class Favicon(View):
    """
    A view class for the endpoint of ML Service that returns the icon. It is inherited
    from the `flask.views.View` class
    """

    methods = ["GET"]

    def __init__(self):
        """
        Class constructor method
        """
        self.dir = "static"
        self.file = "favicon.ico"

    def dispatch_request(self):
        """
        This overrides the `dispatch_request` method of the parent class. This matches
        the URL and does the request dispatching.

        :return: The return value of the view or error handler
        :rtype: `flask.wrappers.Response`
        """
        return send_from_directory(directory=self.dir, path=self.file)
