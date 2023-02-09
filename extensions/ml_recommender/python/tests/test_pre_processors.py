"""
This file is part of Totara Enterprise Extensions.

Copyright (C) 2020 onwards Totara Learning Solutions LTD

Totara Enterprise Extensions is provided only to Totara
Learning Solutions LTD's customers and partners, pursuant to
the terms and conditions of a separate agreement with Totara
Learning Solutions LTD or its affiliate.

If you do not have an agreement with Totara Learning Solutions
LTD, you may not access, use, modify, or distribute this software.
Please contact [licensing@totaralearning.com] for more information.

@author Amjad Ali <amjad.ali@totaralearning.com>
@package ml_recommender
@deprecated since Totara 17.0 ml_recommender has been deprecated.
"""

import lorem
import random
import unittest
from unittest.mock import patch, call

from config import Config
from subroutines.pre_processors import PreProcessors


class TestPreProcessors(unittest.TestCase):
    """
    This object is set up to test units of the `PreProcessors` class
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        self.raw_doc = lorem.paragraph()
        self.pre_processor = PreProcessors()
        cfg = Config()
        self.resources = cfg.get_property("lang_config")

    def test_remove_urls(self):
        """
        This method tests if the `__remove_urls` method of the `PreProcessors` class
        removes urls from the text documents
        """
        test_doc = (
            "The website address of Totara Learning Solutions Ltd is "
            "https://www.totaralearning.com/"
        )
        returned_doc = self.pre_processor._PreProcessors__remove_urls(in_doc=test_doc)
        url_removed_doc = "The website address of Totara Learning Solutions Ltd is"
        self.assertEqual(returned_doc, url_removed_doc)

    def test_lower_applied(self):
        """
        This method tests if the `preprocess_docs` method of the `PreProcessors` class
        changes the upper case characters to the lower case ones.
        """
        test_doc = (
            "The website address of Totara Learning Solutions Ltd is "
            "https://www.totaralearning.com/"
        )
        processed_doc = self.pre_processor.preprocess_docs(raw_doc=test_doc)
        self.assertTrue(processed_doc.islower())

    def test_numerics_removed(self):
        """
        This method tests if the `preprocess_docs` method of the `PreProcessors` class
        removes the numeric characters from the given text
        """
        test_doc = "The eighteenth birthday happens when you turn 18"
        processed_doc = self.pre_processor.preprocess_docs(raw_doc=test_doc)
        self.assertFalse("18" in processed_doc)

    def test_whitespaces_removed(self):
        """
        This method tests if the `preprocess_docs` method of the `PreProcessors` class
        removes the white spaces from the given text
        """
        test_doc = "  A quick   brown fox jumps over the lazy    dog "
        processed_doc = self.pre_processor.preprocess_docs(raw_doc=test_doc)
        self.assertFalse("  " in processed_doc)

    @patch("subroutines.pre_processors.is_package")
    def test_check_installed_resources(self, mock_is_package):
        """
        This method checks if the `__check_installed_resources` method of the
        `PreProcessors` class has called the `is_package` from `spacy.utils` with right
        arguments and right counts and if returns the result as expected when no
        language model is installed
        """
        lang_model = random.choice(
            (
                "de",
                "en",
                "es",
                "it",
                "nl",
            )
        )
        models = self.resources[lang_model]["models"]
        self.pre_processor._PreProcessors__check_installed_resources(
            lang=lang_model, models_from=models
        )
        self.assertEqual(mock_is_package.call_count, len(models))
        self.assertEqual(mock_is_package.call_args_list, [call(m) for m in models])

        lang_no_model = [
            lang
            for lang in self.resources.keys()
            if len(self.resources[lang]["models"]) == 0
        ]
        avail_resources = self.pre_processor._PreProcessors__check_installed_resources(
            lang=random.choice(lang_no_model), models_from=()
        )
        self.assertIn(avail_resources, ["spacy-lookups-data", "None"])

    @patch(
        "subroutines.pre_processors.PreProcessors"
        "._PreProcessors__check_installed_resources"
    )
    def test_collect_lemmatizer_comps(self, mock_checker):
        """
        This method checks if the `__collect_lemmatizer_comps` calls the method
        `__check_installed_resources` of the `PreProcessors` class for every language in
        the `config.py`
        """
        self.pre_processor._PreProcessors__collect_lemmatizer_comps(self.resources)
        lang = random.choice(list(self.resources.keys()))
        self.assertIn(
            call(lang=lang, models_from=self.resources[lang]["models"]),
            mock_checker.call_args_list,
        )
