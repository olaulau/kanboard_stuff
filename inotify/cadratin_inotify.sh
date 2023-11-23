#!/bin/bash

# detect script path
FULL_PATH_TO_SCRIPT="$(realpath "${BASH_SOURCE[-1]}")"
SCRIPT_DIRECTORY="$(dirname "$FULL_PATH_TO_SCRIPT")"
PROJECT_DIRECTORY="$(dirname "$SCRIPT_DIRECTORY")"

# redirect script output
cd $PROJECT_DIRECTORY
log_filename="inotify/cadratin_inotify.log"
exec &> >(tee -a $log_filename)

# begin
echo ""
echo ""
echo "------------------------"
date
IFS=$'\n'

# process estimate files
files=`ls -1 data/devis/*.csv 2> /dev/null`
for file in $files
do
	base=`basename $file`
	php index.php cadratin devis $base
done

# process production files
files=`ls -1 data/prod/*.csv 2> /dev/null`
for file in $files
do
	base=`basename $file`
	php index.php cadratin prod $base
done
