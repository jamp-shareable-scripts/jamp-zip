<?php

/**
 * Unzips the given zip file.
 * 
 * Usage: jamp unzip [-p|--password] [-d|--delete] <zip filename>
 *
 *   -p,--password Use a password to decrypt the files.
 *   -d,--delete   Delete the original zip file when done.
 * 
 * @author  jampperson <https://github.com/jampperson>
 * @license GPL-2.0
 */

$opts = getopt('pd', ['password','delete'], $lastArg);
$zipFilename = $argv[$lastArg];
$usePassword = isset($opts['p']) || isset($opts['password']);
$deleteInput = isset($opts['d']) || isset($opts['delete']);

// Determine the type of compressed file.
$filetype = '';
if (substr_compare($zipFilename, '.tar.gz', -7) === 0) {
	$filetype = '.tar.gz';
} elseif (substr_compare($zipFilename, '.gz', -3) === 0) {
	$filetype = '.gz';
} elseif (substr_compare($zipFilename, '.zip', -4) === 0) {
	$filetype = '.zip';
} elseif (substr_compare($zipFilename, '.tar.bz2', -8) === 0) {
	$filetype = '.tar.bz2';
	echo 'Note: for now, only a tarball is extractable from .tar.bz2 files.'
	. PHP_EOL;
} else {
	throw new Error('Unzipping only works with .tar.gz, .gz, .tar.bz2 and .zip '
	. 'files');
}

$zipFilepath = realpath($zipFilename);
$targetItem = substr($zipFilepath, 0, -1 * strlen($filetype));
if ($filetype === '.tar.bz2') {
	$targetItem .= '.tar'; // Until a fix is found for this bug.
}

if (!is_file($zipFilepath)) {
	throw new Error("Unable to read file: $zipFilepath");
}

$isZippedDir = in_array($filetype, ['.zip', '.tar.gz']);
if ($isZippedDir && is_dir($targetItem) && count(scandir($targetItem)) > 2) {
	throw new Error("Directory already exists and is not empty: $targetItem");
}

if (in_array($filetype, ['.gz','.tar.bz2']) && file_exists($targetItem)) {
	throw new Error("File already exists: $targetItem");
}

if ($filetype === '.gz' && !extension_loaded('zlib')) {
	throw new Exception("Requires PHP extension zlib but it is not loaded. "
	. "Please load zlib and try again.");
}

if ($filetype === '.tar.bz2' && !extension_loaded('bz2')) {
	throw new Error('The bz2 extension must be loaded to extract a .tar.bz2 '
	. 'file.');
}

echo "Extracting $zipFilepath to $targetItem" . PHP_EOL;

if ($filetype === '.tar.gz') {
	$gz = new PharData($zipFilepath);
	$result = $gz->extractTo($targetItem);
	if ($result && $deleteInput) {
		unlink($zipFilepath);
	}
}
elseif ($filetype === '.gz') {
	$bufferSize = 4096;
	$input = gzopen($zipFilepath, 'rb');
	$output = fopen($targetItem, 'wb');
	while (!gzeof($input)) {
		fwrite($output, gzread($input, $bufferSize));
	}
	fclose($output);
	gzclose($input);
}
elseif ($filetype === '.zip') {
	$zip = new ZipArchive;
	if ($usePassword) {
		echo 'Enter password: ';
	}
	$password = $usePassword ? rtrim(fgets(STDIN), PHP_EOL) : '';
	if ($zip->open($zipFilepath) === true) {
		if ($usePassword) {
			$zip->setPassword($password);
		}
		$result = $zip->extractTo($targetItem);
		$status = $zip->getStatusString();
		$zip->close();
		if ($status && $status !== 'No error') {
			echo $status . PHP_EOL;
			echo 'Extraction may not have worked for some or all items.' . PHP_EOL;
		}
		if ($result && $status === 'No error'&& $deleteInput) {
			unlink($zipFilepath);
		}
	}
	else {
		throw new Error($zip->getStatusString());
	}
}
elseif ($filetype === '.tar.bz2') {
	$tempHandle = fopen($targetItem, 'wb');
	if (!$tempHandle) {
		throw new Error("Unable to open: $targetItem");
	}
	$bz = bzopen($zipFilepath, 'r');
	if (!$bz) {
		fclose($tempHandle);
		unlink($targetItem);
		throw new Error("Unable to read: $zipFilepath");
	}
	while (!feof($bz)) {
		fwrite($tempHandle, bzread($bz));
	}
	bzclose($bz);
	fclose($tempHandle);
	if ($deleteInput) {
		unlink($zipFilepath);
	}
}

echo 'Extraction completed.';
