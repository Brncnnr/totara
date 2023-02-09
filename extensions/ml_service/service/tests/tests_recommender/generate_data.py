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

import lorem
import pandas as pd
import random
import time

from service.recommender.config import Config


class GenerateData:
    """
    This is a conceptual representation of the process of generating fake data for the
    Recommender Engine
    """

    def __init__(
        self,
        n_tenants=5,
        n_users=10,
        n_items=20,
        users_interacted=0.95,
        items_interacted=1.0,
    ):
        """
        Class constructor method
        :param n_tenants: Number of tenants to be created as on `tenants.csv`, defaults
            to 5
        :type n_tenants: int, optional
        :param n_users: Number of users to be generated, defaults to 10
        :type n_users: int, optional
        :param n_items: Number of items to be generated, defaults to 20
        :type n_items: int, optional
        :param users_interacted: The percentage of users who interacted with any item
            (0 < `users_interacted` <= 1), defaults to 0.95
        :type users_interacted: float, optional
        :param items_interacted: The percentage of items that got interacted by any user
            (0 < `items_interacted` <= 1), defaults to 1
        :type items_interacted: float, optional
        """
        self.n_tenants = n_tenants
        self.user_id = list(range(2, (n_users + 2)))

        article_n = int(0.2 * n_users)
        course_n = int(0.2 * n_users)
        micro_n = int(0.2 * n_users)
        playlist_n = int(0.2 * n_users)
        workspace_n = n_users - (article_n + course_n + micro_n + playlist_n)

        self.item_id = (
            ["engage_article" + str(x) for x in range(1, (article_n + 1))]
            + ["container_course" + str(x) for x in range(1, (course_n + 1))]
            + ["engage_microlearning" + str(x) for x in range(1, (micro_n + 1))]
            + ["totara_playlist" + str(x) for x in range(1, (playlist_n + 1))]
            + ["container_workspace" + str(x) for x in range(1, (workspace_n + 1))]
        )

        self.user_id_inter = self.user_id
        self.item_id_inter = self.item_id

        if 0.0 < users_interacted < 1.0:
            self.user_id_inter = random.sample(
                population=self.user_id, k=int(users_interacted * n_users)
            )
        if 0 < items_interacted < 1.0:
            self.item_id_inter = random.sample(
                population=self.item_id, k=int(items_interacted * n_items)
            )

    def get_tenants(self):
        """
        :return: A DataFrame containing data like the one in the `tenants.csv`
        """
        tenants_df = pd.DataFrame(data={"tenants": list(range(self.n_tenants))})
        return tenants_df

    def get_users(self):
        """
        :return: A DataFrame containing data like the one in the `user_data_x.csv` where
            `x` is the tenant
        """
        lang = random.choices(
            population=["en", "es", "de", "it", "ne"], k=len(self.user_id)
        )
        country = random.choices(population=["nz", "au", "pk"], k=len(self.user_id))

        def get_city():
            city_name = random.choice(seq=["City1", "City2"])
            town_name = random.choice(seq=["Town1", "Town2", "Town3", "Town4"])
            return city_name + ", " + town_name

        def get_piped_strings(string="string", max_cats=5, total=10, scale=False):
            if scale:
                result = [
                    "|".join(
                        random.sample(
                            population=[
                                string
                                + "_id"
                                + str(j)
                                + ":"
                                + str(random.randint(a=1, b=5))
                                for j in range(1, (max_cats + 1))
                            ],
                            k=random.randint(a=1, b=max_cats),
                        )
                    )
                    for _ in range(total)
                ]
            else:
                result = [
                    "|".join(
                        random.sample(
                            population=[
                                string + "_id" + str(j)
                                for j in range(1, (max_cats + 1))
                            ],
                            k=random.randint(a=1, b=max_cats),
                        )
                    )
                    for _ in range(total)
                ]
            return result

        city = [get_city() for _ in range(len(self.user_id))]
        description = [lorem.paragraph() for _ in range(len(self.item_id))]

        data = {
            "lang": lang,
            "city": city,
            "country": country,
            "description": description,
        }

        cfg = Config()
        for col in cfg.get_property("spread_hor")["users"]:
            data.update(
                {
                    col: get_piped_strings(
                        string=col.rstrip("s"), total=len(self.user_id)
                    )
                }
            )

        for col in cfg.get_property("expand_dict")["users"]:
            data.update(
                {
                    col: get_piped_strings(
                        string=col.replace("ies", "y"),
                        total=len(self.user_id),
                        scale=True,
                    )
                }
            )

        users_data = pd.DataFrame(data=data, index=self.user_id)
        users_data.index.name = "user_id"
        return users_data

    def get_items(self):
        """
        :return: A DataFrame containing data like the one in the `item_data_x.csv` where
            `x` is the tenant
        """
        document = [lorem.paragraph() for _ in range(len(self.item_id))]
        items_data = pd.DataFrame(
            {
                "topic_1": random.choices([0, 0, 1], k=len(self.item_id)),
                "topic_2": random.choices([0, 0, 1], k=len(self.item_id)),
                "topic_3": random.choices([0, 0, 1], k=len(self.item_id)),
                "document": document,
            },
            index=self.item_id,
        )
        item_types = pd.get_dummies(
            data=["".join(c for c in i if not c.isdigit()) for i in self.item_id]
        )
        item_types = item_types.rename(
            index=dict(zip(range(len(self.item_id)), self.item_id))
        )
        items_data = pd.concat([item_types, items_data], axis=1)
        items_data.index.name = "item_id"

        return items_data

    def get_interactions(self):
        """
        :return: A DataFrame containing data like the one in the
            `user_interactions_x.csv` where `x` is the tenant
        """
        df_users = pd.DataFrame(
            {"key": [1] * len(self.user_id_inter), "user_id": self.user_id_inter}
        )
        df_items = pd.DataFrame(
            {"key": [1] * len(self.item_id_inter), "item_id": self.item_id_inter}
        )

        interactions = pd.merge(left=df_users, right=df_items, on="key")
        interactions.drop(labels=["key"], axis=1, inplace=True)
        interactions = interactions.sample(frac=0.6).reset_index(drop=True)
        inter_len = interactions.shape[0]
        interactions["rating"] = random.choices(population=[0, 1, 1, 1, 1], k=inter_len)

        time_now = int(time.time())
        time_start = time_now - 32000000

        interactions["timestamp"] = [
            random.randint(a=time_start, b=time_now) for _ in range(inter_len)
        ]

        return interactions
