# syntax=docker/dockerfile:1
# Create a base image with the python requirements installed - used for development
FROM python:3.9-slim as mlbase

COPY ./requirements.txt /etc/ml/requirements.txt

# Install the packages & then remove the build tools
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get -y install gcc curl git && \
    pip install --upgrade pip && \
    pip install --no-cache-dir -r /etc/ml/requirements.txt && \
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Download the stopwords for this project
RUN python -m nltk.downloader stopwords

ENV TZ=Pacific/Auckland
ENV ML_OFFENSIVE_LANGUAGE_LENGTH=1500
ENV ML_MODELS_DIR=/etc/ml/data/models
ENV ML_LOGS_DIR=/etc/ml/data/train_logs
ENV FLASK_ENV=production
ENV ML_MODELS_GIT_HTTPS="1"
ENV ML_BIND=0.0.0.0:5000

# Create the base directory we run the service from
RUN mkdir -p /etc/ml/service

COPY scripts /etc/ml/scripts
RUN chmod +x /etc/ml/scripts/entrypoint.sh

COPY start.sh /etc/ml
RUN chmod +x /etc/ml/start.sh

WORKDIR /etc/ml
ENTRYPOINT [ "/bin/bash", "/etc/ml/scripts/entrypoint.sh" ]

# Built image with source - used for distribution
FROM mlbase as mlservice

COPY ./service /etc/ml/service
EXPOSE 5000
