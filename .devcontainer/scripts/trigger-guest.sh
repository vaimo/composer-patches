#!/bin/sh
base="$(dirname "$(readlink -f "$0")")"
. ${base}/config.sh

ping="${SYNC_ROOT}/.ping"
pong="${SYNC_ROOT}/.pong"
daemon_script="${SYNC_ROOT}/com.mutagen.project-daemon.plist"

echo "Creating: ${ping}"
touch ${ping}

echo "Waiting: ${pong}"
while test ! -f ${pong} ; do
    if [ ! -f ${ping} ] ; then
      break
    fi
    
    echo "Polling Mutagen ..."
    sleep 1
done

rm -rf \
  ${ping} \
  ${pong} \
  ${daemon_script} \
  ${SCRIPTS_ROOT}
