#!/bin/sh
PHAN_ALLOW_XDEBUG=1
export PHAN_ALLOW_XDEBUG
time php -d xdebug.mode=off ${HOME}/bin/phan >phan.txt
wc -l phan.txt
