#!/bin/bash
cd /home/u289261429/domains/certibot.1jeune1metier.com/public_html/1j1m-attestation
php artisan schedule:run >> /dev/null 2>&1
