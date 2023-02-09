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
from datetime import datetime, timezone
from flask import Flask, jsonify, make_response
from flask_apscheduler import APScheduler
import pickle

import service.settings as settings
from service.api.train_recommender_model import TrainRecommenderModel
from service.api.middleware.authentication import AuthenticationMiddleware
from service.api.route.favicon import Favicon
from service.api.route.home_page import HomePage
from service.api.route.request_similar_items import RequestSimilarItems
from service.api.route.request_user_items import RequestUserItems
from service.api.route.health_check import HealthCheck


def create_app():
    """
    Creates the Totara ML Service

    :return: Totara ML Service object
    :rtype: Flask.app
    """
    app_run_mode = str(os.environ.get("FLASK_ENV", "Production")).strip()
    app = Flask(__name__)
    app.config.from_object(getattr(settings, app_run_mode.title()))
    app.wsgi_app = AuthenticationMiddleware(app.wsgi_app, app.config)
    recommender_model_path = os.path.join(
        os.path.join(app.config.get("MODELS_DIR"), "recommender"),
        "recommender_model.sav",
    )
    if os.path.isfile(recommender_model_path):
        with open(file=recommender_model_path, mode="rb") as handle:
            app.recommender = pickle.load(file=handle)
    else:
        app.recommender = None

    if not app.config.get("TESTING"):
        scheduler = APScheduler()
        scheduler.init_app(app)
        scheduler.start()

        recommendation_retrain_freq = app.config.get("RECOMMENDATION_RETRAIN_FREQ")

        trainer = TrainRecommenderModel(application=app)

        if os.path.isfile(recommender_model_path):
            scheduler.add_job(
                id="RECOMMENDER_SCHEDULED_TRAIN_TASK",
                func=trainer.train_model,
                trigger="interval",
                minutes=int(recommendation_retrain_freq),
            )
        else:
            scheduler.add_job(
                id="RECOMMENDER_SCHEDULED_TRAIN_TASK",
                func=trainer.train_model,
                trigger="interval",
                minutes=int(recommendation_retrain_freq),
                next_run_time=datetime.now(tz=timezone.utc),
            )

    app.add_url_rule(
        rule="/favicon.ico", endpoint=None, view_func=Favicon.as_view(name="icon")
    )

    @app.errorhandler(404)
    def not_found(error):
        """HTTP Error 404 Not Found"""
        headers = {}
        return make_response(
            jsonify({"error": "true", "msg": str(error)}), 404, headers
        )

    @app.errorhandler(405)
    def not_allowed(error):
        """HTTP Error 405 Not Allowed"""
        headers = {}
        return make_response(
            jsonify({"error": "true", "msg": str(error)}), 405, headers
        )

    @app.errorhandler(500)
    def internal_error(error):
        """HTTP Error 500 Internal Server Error"""
        headers = {}
        return make_response(
            jsonify({"error": "true", "msg": str(error)}), 500, headers
        )

    # — This is where the API effectively starts. — — — — — — — — — — —
    app.add_url_rule(rule="/", endpoint=None, view_func=HomePage.as_view(name="home"))

    app.add_url_rule(
        rule="/similar-items",
        endpoint=None,
        view_func=RequestSimilarItems.as_view(name="s_items"),
    )
    app.add_url_rule(
        rule="/user-items",
        endpoint=None,
        view_func=RequestUserItems.as_view(name="user_items"),
    )
    app.add_url_rule(
        rule="/health-check",
        endpoint=None,
        view_func=HealthCheck.as_view(name="health_check"),
    )
    return app
