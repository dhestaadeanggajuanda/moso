#!/bin/sh
# Health check container Redis. redis-cli membaca kata sandi dari REDISCLI_AUTH (redis.env),
# jadi PONG hanya muncul bila server hidup dan kata sandinya benar.
redis-cli ping | grep -q PONG
