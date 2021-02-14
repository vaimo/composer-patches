#!/bin/sh
project_root=${1}
base=$(dirname $BASH_SOURCE[0])
source ${base}/config.sh ${project_root}

name="Mutagen Project Daemon"
ping="${project_root}/${SYNC_ROOT}/.ping"
pong="${project_root}/${SYNC_ROOT}/.pong"
daemon_script="${project_root}/${SYNC_ROOT}/com.mutagen.project-daemon.plist"

cd "${project_root}"

while true; do
  if [ ! -f "${ping}" ] ; then
    sleep 1
    continue
  fi

  /usr/local/bin/mutagen project start && \
    touch "${pong}"

  launchctl unload ${daemon_script}
  break
done
