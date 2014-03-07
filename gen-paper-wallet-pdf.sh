#!/bin/bash

BASEDIR=$(cd $(dirname $0); pwd)

if [ $# -le 1 ]; then
	echo "Usage: $0 ARGS..."
	exit 1
fi

LOGFILE=$(mktemp)

${BASEDIR}/gen-paper-wallet.php $* | tee $LOGFILE
ADDR=$(cat $LOGFILE | grep "Wallet address:" | awk "{print \$3}")

convert "${1,,}-${ADDR}-front.png" "${1,,}-${ADDR}-back.png" "${1,,}-${ADDR}.pdf"
rm -f "${1,,}-${ADDR}-front.png" "${1,,}-${ADDR}-back.png"
rm -f $LOGFILE

