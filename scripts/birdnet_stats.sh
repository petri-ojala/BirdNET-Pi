#!/usr/bin/env bash
# BirdNET Stats Page
trap 'setterm --cursor on && exit' EXIT
trap 'rm -f "${TMP_FILE}" && exit' EXIT
source /etc/birdnet/birdnet.conf
setterm --cursor off
TMP_FILE="$(mktemp)"

while true;do
cat << "EOF"
 .+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.
(   _           ____  __                 _                 )
 ) |_)o.__||\ ||_ |__(_    __|_ _ ._ _  |_) _ ._  _ .__|_ (
(  |_)||(_|| \||_ |  __)\/_> |_(/_| | | | \(/_|_)(_)|  |_  )
 )                      /                     |           (
 "+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"+.+"
EOF
if [ "$(find ${EXTRACTED} -name '*.wav' | wc -l)" -ge 1 ];then
  a=$( find "${EXTRACTED}" -name '*.wav' \
    | awk -F "/" '{print $NF}' \
    | cut -d'-' -f1 \
    | sort -n \
    | tail -n1 )
else
  a=0
fi
echo
if [ "${a}" -ge "1" ];then
  SOFAR=$(($(wc -l ${IDFILE}| cut -d' ' -f1)/2))
else
  SOFAR=0
fi
if [ $SOFAR = 1 ];then
  verbage=detection
else
  verbage=detections
fi
echo "  -$a $verbage so far"
echo
echo "  -$SOFAR species identified so far"
echo
if [ ${a} -ge 1 ];then
while read -r line;do
  SPECIES="$(echo "${line}" | awk -F: '/Common Name/ {print $2}')"
  SPECIES="${SPECIES// /_}"
  SPECIES="$(echo ${SPECIES/_} | tr -d "'")"
  [ -z ${SPECIES} ] && continue

  ALL_DETECTION_FILES="$(ls -1 ${EXTRACTED}/By_Date/*/${SPECIES})"
  ALL_DETECTION_FILES="$(echo ${ALL_DETECTION_FILES[@]} | tr ' ' '\n')"
  MAX_SCORE="$(echo "${ALL_DETECTION_FILES}"| awk -F% '{print $1}')"
  MAX_SCORE="$(echo "${MAX_SCORE[@]}" | rev |cut -d"-" -f1|rev | sort -r | head -n1)"
  
  DETECTIONS="$(ls -1 ${EXTRACTED}/By_Date/*/${SPECIES} | wc -l)"
  if [ ${DETECTIONS} = 1 ];then
    verbage=detection
  else
    verbage=detections
  fi
  echo "${DETECTIONS} $verbage for ${SPECIES//_/ } | max conf ${MAX_SCORE}%"
done < "${IDFILE}" > ${TMP_FILE}
sort -rk1 -h "${TMP_FILE}"
fi
echo
echo -n "Listening since "${INSTALL_DATE}""
sleep 20
clear
done
