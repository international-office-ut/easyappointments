#!/bin/bash
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd $SCRIPT_DIR
sudo -u www-data git pull
sudo -u www-data rm public/assets/css/*.min.css
sudo -u www-data rm public/assets/js/*.min.js
sudo -u www-data css-minify -d public/assets/css -o public/assets/css
sudo -u www-data uglifyjs-folder public/assets/js -e -o public/assets/js