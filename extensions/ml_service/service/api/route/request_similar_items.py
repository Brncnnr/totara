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

from flask import jsonify, make_response, request, current_app
from flask.views import View

from service.recommender.predict_recommender import PredictRecommender


class RequestSimilarItems(View):
    """
    A view class for the endpoint of ML Service that returns similar items to a given
    item in response to a valid request. It is inherited from the `flask.views.View`
    """

    methods = ["GET"]

    def __init__(self):
        """
        Class constructor method
        """
        self.tenant = request.args.get("tenant")
        self.params_dict = {
            "totara_item_id": request.args.get("totara_item_id"),
            "n_items": int(request.args.get("n_items")),
        }

    @staticmethod
    def error_response(message: str) -> dict:
        """
        To prepare a response with error

        :param message: The message to be returned
        :type message: str
        :return: Error object with the 'success' code as False and the given message
        :rtype: dict
        """
        return {"success": False, "message": message, "items": []}

    @staticmethod
    def success_response(items: list) -> dict:
        """
        To prepare a response when items are successfully fetched

        :param items: The list of items to be returned
        :type items: list
        :return: The response object with 'success' code as True and the given items
        :rtype: dict
        """
        return {"success": True, "items": items}

    def dispatch_request(self):
        """
        This overrides the `dispatch_request` method of the parent `View` class. This
        matches the URL and does the request dispatching.

        :return: The return value of the view or error handler
        :rtype: `flask.wrappers.Response`
        """
        if current_app.recommender is None:
            no_model_response = jsonify(
                self.error_response("The recommender model is not ready yet")
            )
            return make_response(no_model_response)

        if self.tenant not in current_app.recommender:
            no_tenant_response = jsonify(
                self.error_response("Bad request: no such tenant")
            )
            return make_response(no_tenant_response)

        if current_app.recommender[self.tenant]["msg"] != "success":
            message = (
                f"Message: The model for tenant {self.tenant} is not trained, "
                "probably for insufficient data"
            )
            no_success_response = jsonify(self.error_response(message))
            return make_response(no_success_response)

        if (
            self.params_dict["totara_item_id"]
            not in current_app.recommender[self.tenant]["mappings"][2]
        ):
            self.params_dict["totara_item_id"] = self.params_dict[
                "totara_item_id"
            ].replace("article", "microlearning")

        if (
            self.params_dict["totara_item_id"]
            not in current_app.recommender[self.tenant]["mappings"][2]
        ):
            no_id_response = jsonify(
                self.error_response("Bad request: no such item id")
            )
            return make_response(no_id_response)

        model = current_app.recommender[self.tenant]["model"]
        algorithm = current_app.config.get("RECOMMENDATION_ALGORITHM")
        mappings = current_app.recommender[self.tenant]["mappings"]
        item_features = current_app.recommender[self.tenant]["item_features"]
        num_threads = int(current_app.config.get("NUM_THREADS"))
        predictor = PredictRecommender(
            model=model,
            algorithm=algorithm,
            mappings=mappings,
            items_features=item_features,
            num_threads=num_threads,
        )
        items = predictor.get_similar_items(
            totara_id=self.params_dict["totara_item_id"],
            n_items=self.params_dict["n_items"],
        )
        items_formatted = [(idx, f"{val: .4f}") for idx, val in items]
        json_response = self.success_response(items_formatted)
        return make_response(json_response)
