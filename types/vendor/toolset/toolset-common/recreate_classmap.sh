#!/bin/bash

# Run the Zend classmap generator utility to recreate a classmap for Toolset Common.
#
# Classmap generator info:
# https://framework.zend.com/manual/2.2/en/modules/zend.loader.classmap-generator.html
#
# Usage:
# Run in the plugin root directory. You only need to let the script know where is the
# classmap generator located. You can either pass its path as a first parameter,
# set the ZEND_CLASSMAP_GENERATOR environment variable or let it fall back to a default value.
#
# Then simply cd to the plugin directory and run: ./recreate_classmap.sh

DEFAULT_GENERATOR_PATH='/srv/www/ZendFramework-2.4.9/bin/classmap_generator.php'

if [ -z "$ZEND_CLASSMAP_GENERATOR" ]; then
    if [ -z "$1" ]; then
        GENERATOR="$DEFAULT_GENERATOR_PATH"
    else
        GENERATOR="$1"
    fi
else
    GENERATOR="$ZEND_CLASSMAP_GENERATOR"
fi

function sort_classmap {
    local classmap="$1"
    local classmap_temp="$classmap.tmp"
    (head -n 3 "$classmap"; tail -n +4 "$classmap" | sed '$d' | sed 's/^ *//;s/ *$//' | sort; tail -n 1 "$classmap") | awk '$1=$1' > "$classmap_temp"
    rm "$classmap"
    mv "$classmap_temp" "$classmap"
}

echo "Toolset Common classmap regenerator"
echo
echo

echo "Using classmap generator at: $GENERATOR"
echo

# the main plugin structure - overwrites existing classmap
echo "Generating classmap for autoloaded classes..."
php "$GENERATOR" --library ./inc/autoloaded --output ./autoload_classmap.php --overwrite
php "$GENERATOR" --library ./utility/admin --output ./autoload_classmap.php --append
php "$GENERATOR" --library ./utility/condition --output ./autoload_classmap.php --append
php "$GENERATOR" --library ./toolset-blocks --output ./autoload_classmap.php --append
php "$GENERATOR" --library ./user-editors --output ./autoload_classmap.php --append
php "$GENERATOR" --library ./expression-parser --output ./autoload_classmap.php --append
php "$GENERATOR" --library ./lib/whip/src --output ./autoload_classmap.php --append
sort_classmap ./autoload_classmap.php
echo

echo

# separate m2m classmap that is loaded on-demand
echo "Generating classmap for m2m..."
php "$GENERATOR" --library ./inc/m2m --overwrite
sort_classmap ./inc/m2m/autoload_classmap.php
echo

# This will generate another classmap which is loaded by Toolset_Common_Bootstrap::register_toolset_forms().
echo "Generating classmap for legacy classes (toolset-forms)..."
php "$GENERATOR" --library ./toolset-forms/ --overwrite
sort_classmap ./toolset-forms/autoload_classmap.php
echo

# Ensure PHP 5.2 compatibility
echo "Replacing __DIR__ with dirname( __FILE__ ) because of PHP 5.2..."
sed -i -e 's/__DIR__/dirname( __FILE__ )/g' ./autoload_classmap.php
sed -i -e 's/__DIR__/dirname( __FILE__ )/g' ./inc/m2m/autoload_classmap.php
sed -i -e 's/__DIR__/dirname( __FILE__ )/g' ./toolset-forms/autoload_classmap.php
echo

echo "Done"