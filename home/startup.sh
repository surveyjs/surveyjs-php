#!/bin/bash

echo "Copying custom default.conf over to /etc/nginx/sites-available/default.conf"

NGINX_CONF=/home/default.conf

if [ -f "$NGINX_CONF" ]; then
    cp /home/default.conf /etc/nginx/sites-available/default
    service nginx reload
else
    echo "File does not exist, skipping cp."
fi