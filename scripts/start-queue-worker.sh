#!/bin/bash
nohup /opt/alt/php82/usr/bin/php artisan queue:work --sleep=3 --tries=3 > /home/jnusa/demo50.jnusa.id/storage/logs/queue.log 2>&1 &

