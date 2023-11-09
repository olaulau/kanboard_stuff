#!/bin/bash

# cleanup destinations
mv -f data/devis/done/*.csv data/devis/stock/
mv -f data/devis/error/*.csv data/devis/stock/
mv -f data/prod/done/*.csv data/prod/stock/
mv -f data/prod/error/*.csv data/prod/stock/

# copy data
cp data/devis/stock/* data/devis/
cp data/prod/stock/* data/prod/
