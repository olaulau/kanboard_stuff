#!/bin/bash

# cleanup destinations
mv data/devis/done/*.csv data/devis/stock/
mv data/devis/error/*.csv data/devis/stock/
mv data/prod/done/*.csv data/prod/stock/
mv data/prod/error/*.csv data/prod/stock/

# copy data
cp data/devis/stock/* data/devis/
cp data/prod/stock/* data/prod/

# run script
./inotify/cadratin_inotify.sh
