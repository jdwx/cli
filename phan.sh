#!/bin/sh
time php ${HOME}/bin/phan >phan.txt
wc -l phan.txt
