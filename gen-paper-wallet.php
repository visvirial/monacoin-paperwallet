#!/usr/bin/env php
<?php

require_once '/usr/share/phpqrcode/qrlib.php';

define('GENERATOR_SCRIPT', './gen-wallet-addr.sh');
define('FONT_PATH', './SourceCodePro-Regular.ttf');

$version_bytes = array(
	'MONA' => 50,
	'BTC'  => 0,
);

$symbol2name = array(
	'MONA' => 'Monacoin',
	'BTC' => 'Bitcoin',
);

if($argc < 3){
	echo "Usage: {$argv[0]} (";
	$first = true;
	foreach($version_bytes as $symbol => $vbyte){
		if(!$first) echo '|';
		else $first=false;
		echo $symbol;
	}
	echo ") AMOUNT\n";
	exit(1);
}

$symbol = $argv[1];
$amount = floatval($argv[2]);

if(!isset($version_bytes[$symbol])){
	echo "Error: invalid currency symbol.\n";
	exit(1);
}

$vbyte = $version_bytes[$symbol];

// Get a private key and a wallet address.
echo "Generating a key pair for $symbol (version byte = $vbyte)...\n";
$tmp = exec(GENERATOR_SCRIPT." {$vbyte}");
$keypair = json_decode($tmp);

function generate_front($outfile, $address='MMonatrZw9NzoFCkV9LX9yB7vE9rDbtSab', $amount=1234){
	global $symbol, $symbol2name;
	// Load the template PNG image.
	$im = imagecreatefrompng(strtolower($symbol2name[$symbol])."/".strtolower($symbol2name[$symbol])."-paperwallet-template-front.png");
	// Create color.
	$black = imagecolorallocate($im, 0, 0, 0);
	// Draw amount if positive.
	if($amount > 0){
		$amount_str = number_format($amount);
		imagettftext($im, 96, 0, 1022, 980, $black, FONT_PATH, strtoupper(substr($symbol, 0, 1)).$amount_str);
	}
	// Draw address.
	imagettftext($im, 64, 0, 90, 1220, $black, FONT_PATH, $address);
	// Create QR code image.
	QRcode::png(strtolower($symbol2name[$symbol]).':'.$address.($amount>0?'?amount='.$amount:''), 'address-qr.png', QR_ECLEVEL_L, 10);
	$qr = imagecreatefrompng('address-qr.png');
	// Draw.
	$size = getimagesize('address-qr.png');
	imagecopy($im, $qr, 1640, 342, 0, 0, $size[0], $size[1]);
	// Delete QR code image.
	unlink('address-qr.png');
	// Write to a file.
	imagepng($im, $outfile);
}

function generate_back($outfile, $privkey_wif){
	global $symbol, $symbol2name;
	// Load the template PNG image.
	$im = imagecreatefrompng(strtolower($symbol2name[$symbol])."/".strtolower($symbol2name[$symbol])."-paperwallet-template-back.png");
	// Create color.
	$black = imagecolorallocate($im, 0, 0, 0);
	// Draw private key.
	$basex = 1090;
	$basey = 800;
	$linesep = 90;
	imagettftext($im, 64, 0, $basex, $basey+0*$linesep, $black, FONT_PATH, substr($privkey_wif, 0, 10));
	imagettftext($im, 64, 0, $basex, $basey+1*$linesep, $black, FONT_PATH, substr($privkey_wif, 10, 10));
	imagettftext($im, 64, 0, $basex, $basey+2*$linesep, $black, FONT_PATH, substr($privkey_wif, 20, 10));
	imagettftext($im, 64, 0, $basex, $basey+3*$linesep, $black, FONT_PATH, substr($privkey_wif, 30, 10));
	imagettftext($im, 64, 0, $basex, $basey+4*$linesep, $black, FONT_PATH, substr($privkey_wif, 40));
	// Create QR code image.
	QRcode::png($privkey_wif, 'privkey-wif-qr.png', QR_ECLEVEL_L, 9);
	$qr = imagecreatefrompng('privkey-wif-qr.png');
	// Draw.
	$size = getimagesize('privkey-wif-qr.png');
	imagecopy($im, $qr, 1650, 720, 0, 0, $size[0], $size[1]);
	// Delete QR code image.
	unlink('privkey-wif-qr.png');
	// Write to a file.
	imagepng($im, $outfile);
}

echo 'Wallet address: '.$keypair->address."\n";
echo 'Private key (raw): '.$keypair->privkey->raw."\n";
echo 'Private key (WIF): '.$keypair->privkey->wif."\n";

generate_front(strtolower($symbol).'-'.$keypair->address.'-front.png', $keypair->address, $amount);
generate_back(strtolower($symbol).'-'.$keypair->address.'-back.png', $keypair->privkey->wif, $amount);




?>
