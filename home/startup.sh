#!/bin/bash
echo "https://azureossd.github.io/2022/04/22/PHP-Laravel-deploy-on-App-Service-Linux-copy/index.html"
echo "Copying custom default.conf over to /etc/nginx/sites-available/default.conf"
NGINX_CONF=/home/default.conf
if [ -f "$NGINX_CONF" ]; then
    cp /home/default.conf /etc/nginx/sites-available/default
    service nginx reload
else
    echo "File does not exist, skipping cp."
fi
