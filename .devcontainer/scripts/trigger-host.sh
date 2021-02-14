#!/bin/sh
project_root="${*}"
base=$(dirname $BASH_SOURCE[0])
source ${base}/config.sh ${project_root}

os_type=$(uname | tr '[:upper:]' '[:lower:]')
project_id=$(pwd|md5)
config_root=".devcontainer"
# Sync files
ping="${SYNC_ROOT}/.ping"
pong="${SYNC_ROOT}/.pong"
daemon_script="${SYNC_ROOT}/com.mutagen.project-daemon.plist"

rm "${daemon_script}" "${ping}" "${pong}" 2>/dev/null
. ${base}/mutagen-killer.sh ${project_root}

if [ -f ${SCRIPTS_ROOT}/config.sh ] ; then
  rm -rf ${SCRIPTS_ROOT}
fi

cp -R ${config_root}/scripts ${SCRIPTS_ROOT}
mkdir -p $(dirname ${SCRIPTS_ROOT})
echo 'export SYNC_ROOT="'${SYNC_ROOT}'"' >> ${SCRIPTS_ROOT}/config.sh
echo 'export SERVICE_NAME="'${SERVICE_NAME}'"' >> ${SCRIPTS_ROOT}/config.sh
echo 'export SCRIPTS_ROOT="'${SCRIPTS_ROOT}'"' >> ${SCRIPTS_ROOT}/config.sh

if [ "${os_type}" = "darwin" ]; then
  service_name="com.mutagen.project-daemon.${project_id}"

cat <<EOT >> "${daemon_script}"
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
  <dict>
    <key>Label</key>
    <string>${service_name}</string>
    <key>KeepAlive</key>
    <dict>
      <key>Crashed</key>
      <true/>
      <key>SuccessfulExit</key>
      <false/>
     </dict>
    <key>ProgramArguments</key>
    <array>
        <string>/bin/sh</string>
        <string>${project_root}/${config_root}/scripts/mutagen-starter.sh</string>
        <string>${project_root}</string>
    </array>
    <key>StandardOutPath</key>
    <string>${project_root}/mutagen.log</string>
    <key>StandardErrorPath</key>
    <string>${project_root}/mutagen.log</string>
  </dict>
</plist>
EOT

  while launchctl list|grep -q "${service_name}\$" ; do
    launchctl unload "${daemon_script}"
    sleep 1
  done

  launchctl load "${daemon_script}"
fi
