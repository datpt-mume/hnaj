FROM python:3.12-slim

ARG GRAPHIFY_VERSION=0.9.20

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

RUN pip install --no-cache-dir "graphifyy==${GRAPHIFY_VERSION}"

WORKDIR /workspace

ENTRYPOINT ["graphify"]