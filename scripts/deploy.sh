#!/bin/bash
/usr/local/bin/php -r "opcache_reset();" 2>/dev/null || true
