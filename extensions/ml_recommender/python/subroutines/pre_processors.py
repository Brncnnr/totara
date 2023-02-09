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

import re
import importlib
import stopwordsiso
from langdetect import detect
from langdetect.lang_detect_exception import LangDetectException
import spacy
from spacy.util import registry, is_package

from config import Config


class PreProcessors:
    """
    This is a conceptual representation of cleaning the document strings
    """

    def __init__(self):
        """
        Class constructor method
        """
        cfg = Config()
        lang_config = cfg.get_property(property_name="lang_config")
        self.resources = self.__collect_lemmatizer_comps(lang_config=lang_config)

    @staticmethod
    def __check_installed_resources(lang, models_from):
        """
        This method finds the best available resource installed on the host machine from
        a list of models and lookups data
        :param lang: The language for which the resource needs to be found
        :type lang: str
        :param models_from: A tuple of the `spaCy` language models for the given
            language from which the best available will be found out
        :type models_from: tuple
        :return: The name of the best resource available on the machine from the
            provided models and the lookups data. The method will return
            `spacy-lookups-data` if the language model is not installed and `None` (str)
            if the lookups data is also not available
        :rtype: str
        """
        models_available = [x for x in models_from if is_package(x)]

        # Return the best model name that is installed
        if len(models_available) > 0:
            return models_available[0]

        # Return 'spacy-lookups-data' if no model is installed
        if lang in registry.lookups:
            return "spacy-lookups-data"
        else:
            return "None"

    def __collect_lemmatizer_comps(self, lang_config):
        """
        This method takes all the languages listed in the `config.py` file one-by-one
        and looks for the best available resource for that language
        :param lang_config: A dictionary comprising of the required languages that whose
            resources need to be found on the host machine. The dictionary format must
            be as {'lang_id': {'name': 'Name',  'models': ('model1', 'model2', ...)}}
        :return: A dictionary in the form
            {'lang_id': 'the_best_available_resource_name'}
        :rtype: Dictionary
        """
        lang_lemmatizer = {}
        for lang in lang_config.keys():
            lang_resource = self.__check_installed_resources(
                lang=lang, models_from=lang_config[lang]["models"]
            )
            if isinstance(lang_resource, str) and "_core_" in lang_resource:
                lang_lemmatizer[lang] = {
                    # spaCy's Natural Language Processing object (language model)
                    "nlp": spacy.load(lang_resource),
                    "lemmatizer": None,
                }
            elif lang_resource == "spacy-lookups-data":
                tables = registry.lookups.get(lang)
                if "lemma_lookup" in tables:
                    mod = getattr(
                        importlib.import_module(f"spacy.lang.{lang}"),
                        lang_config[lang]["name"],
                    )
                    nlp = mod()
                    lemmatizer = nlp.add_pipe("lemmatizer", config={"mode": "lookup"})
                    lemmatizer.initialize()
                    lang_lemmatizer[lang] = {"nlp": nlp, "lemmatizer": lemmatizer}
        return lang_lemmatizer

    @staticmethod
    def __remove_urls(in_doc=None):
        """
        This static method removes part of string that matches with a URL pattern
        :param in_doc: The document string from which the URL needs to be removed
        :type in_doc: str
        :return: A string after URLs have been removed
        :rtype: str
        """
        reg_patt = r"(@[A-Za-z0-9]+)|([^0-9A-Za-z \t])|(\w+://\S+)"
        new_doc = " ".join(re.sub(reg_patt, " ", in_doc).split())
        return new_doc

    def preprocess_docs(self, raw_doc=None):
        """
        This method preprocesses the strings and returns a string that is in lower case,
        has URLs removed, numeric characters removed, has no stopwords, and has been
        lemmatized.
        :param raw_doc: The document to be preprocessed/cleaned
        :type raw_doc: str
        :return: A preprocessed document
        :rtype: str
        """
        try:
            lang = detect(text=raw_doc)
        except LangDetectException:
            lang = "en"

        # For every language predicted if it has a spacy support, apply spacy's
        # framework to clean and lemmatize the text
        if lang in self.resources.keys():
            doc = self.resources[lang]["nlp"](raw_doc)
            if self.resources[lang]["lemmatizer"] is not None:
                doc = self.resources[lang]["lemmatizer"](doc)
            modified_text = " ".join(
                [
                    t.lemma_.strip().lower()
                    for t in doc
                    if not (
                        t.like_url
                        or t.is_digit
                        or t.is_punct
                        or t.is_space
                        or t.like_num
                        or t.is_currency
                        or t.like_email
                        or t.is_stop
                    )
                ]
            )
        else:
            # Convert the document to the lower case
            doc = raw_doc.lower()  # type, str
            # Remove the URLs
            doc = self.__remove_urls(in_doc=doc)  # type, str
            # Remove the numeric characters
            doc = re.sub(r"\d+", "", doc)  # type, str
            # Remove punctuations with white spaces
            doc = re.sub(r"[^\w\s]", " ", doc)  # type, str
            # Replace multiple white spaces with single one
            doc = " ".join(doc.split(sep=" "))
            # Remove the trailing and leading white spaces
            doc = doc.strip()
            # Convert the document string into list of words
            doc = doc.split(sep=" ")  # type, list
            # Remove stopwords
            if stopwordsiso.has_lang:
                doc = [
                    t for t in doc if t not in stopwordsiso.stopwords(langs=lang)
                ]  # type, list
            else:
                doc = doc  # type, list
            # Join the words into a single string of document
            modified_text = " ".join(doc)  # type, str
        return modified_text
