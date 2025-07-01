#!/bin/bash
# This script sets up a CRON job to run cron.php every hour
PHP_PATH=$(which php)
CRON_FILE="/tmp/task_scheduler_cron"
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
CRON_PHP="$PROJECT_DIR/cron.php"
# Remove any previous job for this file
crontab -l | grep -v "$CRON_PHP" > $CRON_FILE 2>/dev/null
# Add new job
echo "0 * * * * $PHP_PATH $CRON_PHP" >> $CRON_FILE
crontab $CRON_FILE
rm $CRON_FILE
echo "CRON job set: $PHP_PATH $CRON_PHP every hour."
