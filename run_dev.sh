#!/bin/bash

# cleanup destinations
rm data/devis/done/*.csv
rm data/devis/error/*.csv
rm data/prod/done/*.csv
rm data/prod/error/*.csv

# copy data
cp data/devis/stock/* data/devis/
cp data/prod/stock/* data/prod/

# run script
./inotify/cadratin_inotify.sh
