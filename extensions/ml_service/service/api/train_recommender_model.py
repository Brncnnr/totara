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

import io
import os
import nltk
import pandas as pd
import pickle
from flask import current_app
from datetime import datetime

from service.recommender.train_recommender import TrainRecommender
from service.communicator.totara_files import TotaraFiles


class TrainRecommenderModel:
    """
    Mechanism for data fetching from totara and model training in the background on
    scheduled times
    """

    def __init__(self, application):
        """
        Class constructor method

        :param application: The Flask object
        :type application: Flask
        """
        self.application = application
        with application.app_context():
            self.totara_url = current_app.config.get("TOTARA_URL")
            self.totara_key = current_app.config.get("TOTARA_KEY")
            self.logs_path = current_app.config.get("LOGS_DIR", "/var/log/mlservice")
            self.models_path = current_app.config.get("MODELS_DIR")
            self.algorithm = current_app.config.get("RECOMMENDATION_ALGORITHM")
            self.num_threads = int(current_app.config.get("NUM_THREADS"))
        self.time_stamp = datetime.now().strftime("%d-%b-%Y (%H:%M:%S)")
        if self.models_path not in nltk.data.path:
            nltk.data.path.append(self.models_path)

    def fetch_data(self) -> dict:
        """
        This method fetches data from Totara

        :return: A dictionary that contains data content with relevant keys
        :rtype: dict
        """
        training_data = {"data_status": "success"}
        files_fetcher = TotaraFiles(
            totara_url=self.totara_url, totara_key=self.totara_key
        )
        tenants_download = files_fetcher.download(filename="tenants.csv")
        if tenants_download["status"] == "success":
            tenants = pd.read_csv(
                filepath_or_buffer=io.StringIO(
                    tenants_download["content"].decode("utf-8")
                ),
                sep=",",
                encoding="utf-8",
                dtype=str,
            )
            training_data["tenants"] = tenants
            for tenant in tenants.tenants.tolist():
                interactions_download = files_fetcher.download(
                    filename=f"user_interactions_{tenant}.csv"
                )
                user_data_download = files_fetcher.download(
                    filename=f"user_data_{tenant}.csv"
                )
                item_data_download = files_fetcher.download(
                    filename=f"item_data_{tenant}.csv"
                )
                if (
                    interactions_download["status"] == "success"
                    and user_data_download["status"] == "success"
                    and item_data_download["status"] == "success"
                ):
                    training_data[f"user_interactions_{tenant}"] = pd.read_csv(
                        filepath_or_buffer=io.StringIO(
                            interactions_download["content"].decode(encoding="utf-8")
                        ),
                        sep=",",
                        encoding="utf-8",
                    )
                    training_data[f"user_interactions_{tenant}"][
                        "user_id"
                    ] = training_data[f"user_interactions_{tenant}"]["user_id"].astype(
                        str
                    )
                    training_data[f"user_data_{tenant}"] = pd.read_csv(
                        filepath_or_buffer=io.StringIO(
                            user_data_download["content"].decode(encoding="utf-8")
                        ),
                        sep=",",
                        encoding="utf-8",
                        index_col="user_id",
                    )
                    training_data[f"user_data_{tenant}"].index = training_data[
                        f"user_data_{tenant}"
                    ].index.map(str)
                    training_data[f"item_data_{tenant}"] = pd.read_csv(
                        filepath_or_buffer=io.StringIO(
                            item_data_download["content"].decode(encoding="utf-8")
                        ),
                        sep=",",
                        encoding="utf-8",
                        index_col="item_id",
                    )
                else:
                    training_data["data_status"] = "fail"
        else:
            training_data["data_status"] = "fail"
        return training_data

    def train_model(self) -> None:
        """
        This method uses the data fetched from totara and trains recommendation models
        for all tenants
        """
        training_data = self.fetch_data()
        if training_data["data_status"] == "success":
            # In case data fetch was a success
            trainer = TrainRecommender(
                data=training_data, query=self.algorithm, num_threads=self.num_threads
            )
            models = trainer.train_models()
            models["algorithm"] = self.algorithm

            # Add models to service cache
            self.application.recommender = models

            # Write models to hard disk so they can be reloaded in case of service
            # crashing
            if self.models_path:
                recommender_model_dir_path = os.path.join(
                    self.models_path, "recommender"
                )
                recommender_model_path = os.path.join(
                    recommender_model_dir_path, "recommender_model.sav"
                )
                if not os.path.isdir(recommender_model_dir_path):
                    os.mkdir(path=recommender_model_dir_path)
                    with open(file=recommender_model_path, mode="wb") as handle:
                        pickle.dump(
                            obj=models, file=handle, protocol=pickle.HIGHEST_PROTOCOL
                        )
                else:
                    with open(file=recommender_model_path, mode="wb") as handle:
                        pickle.dump(
                            obj=models, file=handle, protocol=pickle.HIGHEST_PROTOCOL
                        )

            # Gather list of logging parameters
            summary_list = []
            for key in models.keys():
                if key != "algorithm":
                    if models[key]["msg"] == "success":
                        summary_list.append(
                            {
                                "TIMESTAMP": self.time_stamp,
                                "ALGORITHM": self.algorithm,
                                "TENANT_ID": key,
                                "MSG": models[key]["msg"],
                                "EPOCHS": ", ".join(
                                    [str(e) for e in models[key]["epochs"]]
                                ),
                                "N_EMBEDDINGS": ", ".join(
                                    [str(n) for n in models[key]["n_components"]]
                                ),
                                "SCORE": ", ".join(
                                    [str(s) for s in models[key]["score"]]
                                ),
                            }
                        )
                    else:
                        summary_list.append(
                            {
                                "TIMESTAMP": self.time_stamp,
                                "ALGORITHM": self.algorithm,
                                "TENANT_ID": key,
                                "MSG": models[key]["msg"],
                                "EPOCHS": "",
                                "N_EMBEDDINGS": "",
                                "SCORE": "",
                            }
                        )
        else:
            # In case the data fetch was a failure, gather logging parameters
            summary_list = [
                {
                    "TIMESTAMP": self.time_stamp,
                    "ALGORITHM": self.algorithm,
                    "TENANT_ID": "all",
                    "MSG": (
                        "The model training process failed as service was not "
                        "successful in fetching the data from Totara instance"
                    ),
                    "EPOCHS": "",
                    "N_EMBEDDINGS": "",
                    "SCORE": "",
                }
            ]

        # Write training logs to the file
        summaries_log_exists = os.path.exists(
            os.path.join(self.logs_path, "train_summary.log")
        )
        if summaries_log_exists:
            log_summaries = pd.read_csv(
                os.path.join(self.logs_path, "train_summary.log"),
                sep=",",
                dtype={
                    "TIMESTAMP": str,
                    "ALGORITHM": str,
                    "TENANT_ID": str,
                    "MSG": str,
                    "EPOCHS": str,
                    "N_EMBEDDINGS": str,
                    "SCORE": str,
                },
            )
            log_summaries = log_summaries.append(
                other=pd.DataFrame(data=summary_list), ignore_index=True
            ).tail(100)
        else:
            log_summaries = pd.DataFrame(data=summary_list)

        log_summaries.to_csv(
            os.path.join(self.logs_path, "train_summary.log"),
            sep=",",
            header=True,
            index=False,
            mode="w",
        )
