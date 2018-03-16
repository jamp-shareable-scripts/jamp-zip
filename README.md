# jamp-zip
Jamp PHP scripts for compressing and decompressing zip files.

# Examples
`> jamp zip plain-backup.zip`

Zips all the files in the current directory into the backup.zip archive.

```
> jamp zip -p encrypted-backup.zip
Enter a password:
Zip file created: encrypted-backup.zip
```
Zips all the files in the current directory into the encrypted-backup.zip file,
encrypting each file with the given password.
