<?php
// Save 24bit BMP files

// Author: de77
// Licence: MIT
// Webpage: de77.com
// Version: 07.02.2010

function AgPaymentModesimagebmp(&$img, $filename = false) {
	$wid = imagesx($img);
	$hei = imagesy($img);
	$wid_pad = str_pad('', $wid % 4, "\0");
	
	$size = 54 + ($wid + $wid_pad) * $hei;
	
	//prepare & save header
	$header['identifier'] = 'BM';
	$header['file_size'] = AgPaymentModesdword($size);
	$header['reserved'] = AgPaymentModesdword(0);
	$header['bitmap_data'] = AgPaymentModesdword(54);
	$header['header_size'] = AgPaymentModesdword(40);
	$header['width'] = AgPaymentModesdword($wid);
	$header['height'] = AgPaymentModesdword($hei);
	$header['planes'] = AgPaymentModesword(1);
	$header['bits_per_pixel']= AgPaymentModesword(24);
	$header['compression']= AgPaymentModesdword(0);
	$header['data_size'] = AgPaymentModesdword(0);
	$header['h_resolution'] = AgPaymentModesdword(0);
	$header['v_resolution'] = AgPaymentModesdword(0);
	$header['colors'] = AgPaymentModesdword(0);
	$header['important_colors'] = AgPaymentModesdword(0);

    if ($filename) {
		$f = fopen($filename, "wb");
		foreach ($header AS $h) {
			fwrite($f, $h);
		}
		
		//save pixels
		for ($y=$hei-1; $y>=0; $y--) {
			for ($x=0; $x<$wid; $x++) {
				$rgb = imagecolorat($img, $x, $y);
				fwrite($f, AgPaymentModesbyte3($rgb));
			}
			fwrite($f, $wid_pad);
		}

		return fclose($f);
	}
	
	else {
		foreach ($header AS $h) {
		    echo $h;
		}
		
		//save pixels
		for ($y = $hei - 1; $y >= 0; $y--) {
			for ($x=0; $x<$wid; $x++) {
				$rgb = imagecolorat($img, $x, $y);
				echo AgPaymentModesbyte3($rgb);
			}
			echo $wid_pad;
		}

		return true;
	}
}

function AgPaymentModesbyte3($n) {
	return chr($n & 255) . chr(($n >> 8) & 255) . chr(($n >> 16) & 255);    
}

function AgPaymentModesdword($n) {
	return pack("V", $n);
}

function AgPaymentModesword($n) {
	return pack("v", $n);
} 
