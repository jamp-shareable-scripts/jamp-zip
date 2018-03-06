<?php

/**
 * Creates a zip file and copies all files in the current directory into it.
 * 
 * Usage: jamp zip [-p|--password] <new zip filename>
 * 
 *   -p,--password Set a password to use to encrypt each of the items.
 *
 * @author  jampperson <https://github.com/jampperson>
 * @license GPL-2.0
 */

jampUse('jampEcho');

$items = array_diff(scandir(getcwd()), ['.','..']);

$options = getopt('p', ['password'], $lastArg);
$zipnameRaw = $argv[$lastArg];

$usePassword = isset($options['p']) || isset($options['password']);
$zipname = substr_compare($zipnameRaw, '.zip', -4) === 0
? $zipnameRaw : "$zipnameRaw.zip";

if (file_exists($zipname)) {
	throw new Error("Unable to create file, it already exists: $zipname");
}

if ($usePassword) {
	echo 'Enter a password: ';
}
$password = $usePassword ? rtrim(fgets(STDIN), PHP_EOL) : '';

$newZip = new ZipArchive;
$result = $newZip->open($zipname, ZipArchive::CREATE);
if ($result !== true) {
	throw new Error($newZip->getStatusString());
}

if ($usePassword) {
	$newZip->setPassword($password);
}
foreach ($items as $item) {
	$path = getcwd() . DIRECTORY_SEPARATOR . $item;
	$newZip->addFile($path, $item);
	if ($usePassword) {
		$newZip->setEncryptionName($item, ZipArchive::EM_AES_256);
	}
}
$newZip->close();
jampEcho("Zip file created: $zipname");
