#!/bin/sh
PHAN_DISABLE_XDEBUG_WARN=1
export PHAN_DISABLE_XDEBUG_WARN
time php ${HOME}/bin/phan >phan.txt
wc -l phan.txt
