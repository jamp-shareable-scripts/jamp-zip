<?php

/**
 * Creates a zip file of the specified name and copies all files and
 * subdirectories in the current directory into it.
 * 
 * Usage: jamp zip [-p|--password] <new zip filename>
 * 
 *   -p,--password If present, it will ask for a password to use to encrypt
 *                 the files.
 *
 * @author  jampperson <https://github.com/jampperson>
 * @license GPL-2.0
 */

jampUse('jampEcho');

$options = getopt('p', ['password'], $lastArg);

if (empty($argv[$lastArg])) {
	passthru('jamp usage zip');
	exit;
}

// Check if a password should be used.
$usePassword = isset($options['p']) || isset($options['password']);
$password = '';

// Ensure that the extension is .zip.
$zipnameRaw = $argv[$lastArg];
$zipname = substr_compare($zipnameRaw, '.zip', -4) === 0
? $zipnameRaw : "$zipnameRaw.zip";

// Make sure we're not overwriting any files.
// TODO: Add an option (maybe -f) to force an overwrite.
if (file_exists($zipname)) {
	throw new Error("Unable to create file, it already exists: $zipname");
}

// If a password should be used, ask the user for it.
// TODO: hide user input.
if ($usePassword) {
	echo 'Enter a password: ';
	$password = rtrim(fgets(STDIN), PHP_EOL);
}

// Create zip archive.
$newZip = new ZipArchive;
$result = $newZip->open($zipname, ZipArchive::CREATE);
if ($result !== true) {
	throw new Error($newZip->getStatusString());
}

// Add the password, if a password is being used.
if ($usePassword) {
	$newZip->setPassword($password);
}

// Recursively add items from the current working directory.
addItems($newZip, getcwd(), getcwd(), $usePassword);

// Close the new zip archive.
$newZip->close();

/**
 * Adds items in $dir directory to $zip zip archive. It is aware of the $base
 * directory so that all paths can be truncated into local paths for the zip
 * archive. $usePassword acts like a flag indicating whether or not to encrypt
 * the files in the zip archive.
 * @param ZipArchive $zip Zip archive.
 * @param string $dir Current directory being added.
 * @param string $base Base directory of all items being added.
 * @param boolean $usePassword Whether files should be encrypted.
 */
function addItems(&$zip, $dir, $base, $usePassword) {
	$i = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
	while($i->valid()) {
		$item = $i->current();
		$path = $item->getRealPath();
		$localPath = str_replace('\\', '/', substr($path, strlen($base) + 1));
		if ($item->isDir() && count(scandir($path)) === 2) {
			$zip->addEmptyDir($localPath);
		}
		elseif ($item->isDir()) {
			addItems($zip, $path, $base, $usePassword);
		}
		elseif ($item->isFile()) {
			$zip->addFile($path, $localPath);
			if ($usePassword) {
				$zip->setEncryptionName($localPath, ZipArchive::EM_AES_256);
			}
		}
		else {
			echo "Warning, item not added: $path" . PHP_EOL;
		}
		$item = $i->next();
	}
}

jampEcho("Zip file created: $zipname");
