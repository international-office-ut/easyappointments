#!/bin/sh
sudo -u www-data git pull
sudo -u www-data css-minify -d assets/css -o assets/css
sudo -u www-data uglifyjs-folder assets/js -e -o assets/js