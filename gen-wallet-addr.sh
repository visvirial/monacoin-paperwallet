#!/bin/bash 

# this was derived from here: https://bitcointalk.org/index.php?topic=23081.20
# I simply slimmed it down so it could be used by a script -RobKohr

# Downloaded from https://github.com/RobKohr/PHP-Bitcoin-Address-Creator
# Modified by Mona Tore <monatr.jp@gmail.com> 2014.

base58=({1..9} {A..H} {J..N} {P..Z} {a..k} {m..z})
bitcoinregex="^[$(printf "%s" "${base58}")]{34}$"

decodeBase58() {
    local s=$1
    for i in {0..57}
    do s="${s//${base58}/ $i}"
    done
    dc <<< "16o0d${s// /+58*}+f" 
}

encodeBase58() {
    # 58 = 0x3A
    bc <<<"ibase=16; n=${1^^}; while(n>0) { n%3A ; n/=3A }" |
    tac |
    while read n
    do echo -n ${base58[n]}
    done
}

checksum() {
    xxd -p -r <<<"$1" |
    openssl dgst -sha256 -binary 2>/dev/null |
    openssl dgst -sha256 -binary 2>/dev/null |
    xxd -p -c 80 |
    head -c 8
}

checkBitcoinAddress() {
    if [[ "$1" =~ $bitcoinregex ]]
    then
        h=$(decodeBase58 "$1")
        checksum "00${h::${#h}-8}" |
        grep -qi "^${h: -8}$"
    else return 2
    fi
}

hash160() {
    openssl dgst -sha256 -binary 2>/dev/null |
    openssl dgst -rmd160 -binary 2>/dev/null |
    xxd -p -c 80
}

# hash160ToAddress HASH160 VERSION_BYTE
hash160ToAddress() {
    printf "%34s\n" "$(encodeBase58 "$2$1$(checksum "$2$1")")" |
    sed "y/ /1/"
}

hash256ToAddress() {	
	#printf "80$1$(checksum "80$1")"
    printf "%34s\n" "$(encodeBase58 "80$1$(checksum "80$1")")" |
    sed "y/ /1/"
}

# publicKeyToAddress VERSION_BYTE
publicKeyToAddress() {
    hash160ToAddress $(
    openssl ec -pubin -pubout -outform DER 2>/dev/null |
    tail -c 65 |
    hash160
    ) $1
}

privateKeyToWIF() {
    hash256ToAddress $(openssl ec -text -noout -in data.pem 2>/dev/null | head -5 | tail -3 | fmt -120 | sed 's/[: ]//g')
}

# genWalletAddr VERSION_BYTE
#   @param VERSION_BYTE version byte for Base58Check encoding in HEXADECIMAL.
genWalletAddr() {
	openssl ecparam -genkey -name secp256k1 | tee data.pem &>/dev/null
	privkey_raw=$(openssl ec -text -noout -in data.pem 2>/dev/null | head -5 | tail -3 | fmt -120 | sed 's/[: ]//g')
	privkey_wif=$(privateKeyToWIF)
	wallet_addr=$(openssl ec -pubout < data.pem 2>/dev/null | publicKeyToAddress $1)
	rm data.pem
	# Print as JSON.
	echo \{\"privkey\":\{\"raw\":\"$privkey_raw\",\"wif\":\"$privkey_wif\"\},\"address\":\"$wallet_addr\"\}
}

if [ $# -le 0 ]; then
	echo "Usage: $0 VERSION_BYTE"
	exit 1
fi

genWalletAddr $1

