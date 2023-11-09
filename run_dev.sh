#!/bin/bash

# cleanup destinations
mv data/devis/done/*.csv data/devis/
mv data/devis/error/*.csv data/devis/
mv data/prod/done/*.csv data/prod/
mv data/prod/error/*.csv data/prod/

# copy data
cp data/devis/stock/* data/devis/
cp data/prod/stock/* data/prod/

# run script
./inotify/cadratin_inotify.sh
