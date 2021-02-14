#!/bin/sh
project_root=${1}

script_path=$(
  cat ${project_root}/.devcontainer/devcontainer.json \
    |grep postAttachCommand \
    |sed 's/.*:.*".*}\/\(.*\)".*/\1/g'
)

export SERVICE_NAME=$(
  cat ${project_root}/.devcontainer/devcontainer.json \
    |grep service \
    |sed 's/.*:.*"\(.*\)".*/\1/g'
)

export SCRIPTS_ROOT=$(
  echo ${script_path} \
    |xargs dirname
)

export SYNC_ROOT=$(
  echo ${script_path} \
    |xargs dirname|xargs dirname
)
