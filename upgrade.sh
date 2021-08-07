#!/bin/bash
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd $SCRIPT_DIR
sudo -u www-data git pull
sudo -u www-data rm assets/css/*.min.css
sudo -u www-data rm assets/js/*.min.js
sudo -u www-data css-minify -d assets/css -o assets/css
sudo -u www-data uglifyjs-folder assets/js -e -o assets/js