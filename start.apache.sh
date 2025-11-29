#!/bin/bash
# Set Apache port from Render
echo "Listen ${PORT}" > /etc/apache2/ports.conf
exec "$@"
