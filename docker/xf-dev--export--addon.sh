#!/bin/bash

set -e

_addOnId="${1%/}"
if [ -z "${_addOnId}" ]; then
  echo 'Add-on ID is missing' >&2
  exit 1
fi

devhelper-autogen.sh "${_addOnId}"
devhelper-autocheck.sh "${_addOnId}"

echo 'Running phpcs, it may take a while...'
_addOnDir="/var/www/html/src/addons/${_addOnId}"
_phpcs=$( devhelper-phpcs.sh "${_addOnDir}" 2>&1 || true )
if [ ! -z "$_phpcs" ]; then
  echo "$_phpcs"

  _phpcbfSuggestion=$( echo "$_phpcs" | grep 'PHPCBF CAN FIX' )
  if [ ! -z "$_phpcbfSuggestion" ]; then
    echo "phpcs failed, execute \`devhelper-phpcbf.sh ${_addOnDir}\` to attempt fixing automatically" >&2
    exit 2
  fi

  echo 'phpcs failed' >&2
  exit 1
fi
echo 'phpcs OK'

# Silently enable the add-on because PHPStan has to resolve XFCP classes
xf-addon--enable --no-interaction "${_addOnId}" >/dev/null 2>&1

devhelper-phpstan.sh "${_addOnDir}"

exec cmd-php.sh xf-dev:export --addon "${_addOnId}"
