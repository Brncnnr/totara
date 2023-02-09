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
from service.recommender.config import Config


class RequestUserItems(View):
    """
    A view class for the endpoint of ML Service that returns recommended items for a
    given user in response to a valid request. It is inherited from the
    `flask.views.View` class
    """

    methods = ["GET"]

    def __init__(self):
        """
        Class constructor method
        """
        self.tenant = request.args.get("tenant")
        self.params_dict = {
            "totara_user_id": request.args.get("totara_user_id"),
            "n_items": int(request.args.get("n_items")),
            "item_type": request.args.get("item_type"),
        }
        self.item_types_allowed = Config().get_property(property_name="item_types")

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
            self.params_dict["totara_user_id"]
            not in current_app.recommender[self.tenant]["mappings"][0]
        ):
            no_id_response = jsonify(
                self.error_response("Bad request: no such user id")
            )
            return make_response(no_id_response)

        if self.params_dict["item_type"] not in self.item_types_allowed:
            wrong_type_response = jsonify(
                self.error_response("Bad request: no such item type is available")
            )
            return make_response(wrong_type_response)

        model = current_app.recommender[self.tenant]["model"]
        algorithm = current_app.config.get("RECOMMENDATION_ALGORITHM")
        mappings = current_app.recommender[self.tenant]["mappings"]
        user_features = current_app.recommender[self.tenant]["user_features"]
        item_features = current_app.recommender[self.tenant]["item_features"]
        item_type_map = current_app.recommender[self.tenant]["item_type_map"]
        positive_inter_map = current_app.recommender[self.tenant][
            "positive_interactions_map"
        ]
        num_threads = int(current_app.config.get("NUM_THREADS"))
        predictor = PredictRecommender(
            model=model,
            algorithm=algorithm,
            mappings=mappings,
            items_features=item_features,
            num_threads=num_threads,
        )
        items = predictor.get_user_recommendations(
            totara_id=self.params_dict["totara_user_id"],
            n_items=self.params_dict["n_items"],
            items_type=self.params_dict["item_type"],
            user_features=user_features,
            item_type_map=item_type_map,
            positive_inter_map=positive_inter_map,
        )
        items_formatted = [(idx, f"{val: .4f}") for idx, val in items]
        success_response = jsonify(self.success_response(items_formatted))
        return make_response(success_response)
