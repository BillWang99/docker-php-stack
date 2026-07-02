#!/bin/bash

# HCM HR Artisan Docker Helper
# 用法: ./artisan-docker.sh view:clear
#      ./artisan-docker.sh migrate
#      ./artisan-docker.sh tinker

# 在 Docker 容器中執行 artisan 命令
docker exec php bash -c "cd /var/www/html/hcm_hr && php artisan $*"
