#!/usr/bin/env bash
: <<'COPYRIGHT'
 Copyright (c) Vaimo Group. All rights reserved.
 See LICENSE_VAIMO.txt for license details.
COPYRIGHT

### Color shortcuts for echoed color output ###
txtrst=$(tput sgr0)         # Reset color/styling
txtund=$(tput sgr 0 1)      # Underline
txtbld=$(tput bold)         # Bold
red=$(tput setaf 9)         # bright red
drkred=$(tput setaf 1)      # dark red
drkmag=$(tput setaf 5)      # dark magenta
grn=$(tput setaf 2)         # dark green
brghtgrn=$(tput setaf 10)   # bright green
brghtcya=$(tput setaf 14)   # bright cyan
dkylw=$(tput setaf 3)       # dark yellow
yel=$(tput setaf 11)        # yellow
gry=$(tput setaf 7)         # grey (default terminal grey)
dgry=$(tput setaf 8)        # dark grey
blu=$(tput setaf 12)        # blue
mag=$(tput setaf 13)        # magenta
cya=$(tput setaf 6)         # cyan
wht=$(tput setaf 15)        # white
blck=$(tput setaf 0)         # black 
bldred=${txtbld}${red}      # bold-red
bldgrn=${txtbld}${grn}      # bold-green
bldyel=${txtbld}${yel}      # bold-yellow
bldblu=${txtbld}${blu}      # bold-blue
bldwht=${txtbld}${wht}      # bold-white
bgdred=$(tput setab 1)      # dark red background
bldcya=${txtbld}${cya}      # bold-cya
bgdblu=$(tput setab 4)      # dark blue background
bgdgrn=$(tput setab 2)      # green background
bgdylw=$(tput setab 3)      # dark yellow background
bgcol=$(tput sgr 1 0)       # Switch to background (coloring mode)
fgcol=$(tput sgr 0 0)       # Switch to foreground (coloring mode)

_info() {
    topic=${1}
    group=${2}
    label=${3}
    
    local separator=
    
    if [ "${group}" != '' ] ; then
        local separator=' - '
    fi

    echo "${grn}${1}${txtrst} ${brghtgrn}$(echo ${group}|tr '[:lower:]' '[:upper:]')${txtrst}${separator}${3}"
}

_title() {
    topic=${1}
    group=${2}
    label=${3}
    
    local separator=
    
    if [ "${label}" != '' ] ; then
        local separator=' - '
    fi

    echo "${cya}${1}${txtrst} ${brghtcya}$(echo ${group}|tr '[:lower:]' '[:upper:]')${txtrst}${separator}${label}"
}

_warning()
{
    echo "${bgdylw}${txtbld}${blck}${@}${txtrst}"
}

_error() {
    echo "${bgdred}${bldwht}${@}${txtrst}"
}

_line() {
    local character=${1}

    eval echo $(printf -- '${character}%.0s' {1..80})
}

_trim_string() {
    echo "${@}"|sed 's/^ *//g' | sed 's/ *$//g'
}