#!/bin/sh
project_root=${1}
base=$(dirname $BASH_SOURCE[0])
. ${base}/config.sh ${project_root}

main() {
  local name=${1}
  local project_root=${2}

  echo "Killing Mutagen for: ${name}"

  (
    cd ${project_root}

    if ! docker-compose ps ${name} 2>/dev/null|grep -q ' Up ' ; then
      return 0
    fi

    docker-compose exec --user root ${name} sh -c \
      "which ps || (apt-get update && apt-get install -y procps)"

    docker-compose exec --user root ${name} sh -c \
      'ps aux|grep mutagen|awk "{print \$2;}"|xargs kill'

    sleep 1
    mutagen project terminate
    sleep 1
  )
}

main ${SERVICE_NAME} ${project_root}
