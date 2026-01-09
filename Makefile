.PHONY: help php-bash artisan composer npm

help:
	@echo "可用指令："
	@echo "  make php-bash              - 進入 PHP 容器"
	@echo "  make artisan project=專案名 cmd=指令 - 執行 artisan 指令"
	@echo "  make composer project=專案名 cmd=指令 - 執行 composer 指令"
	@echo "  make npm project=專案名 cmd=指令      - 執行 npm 指令"
	@echo ""
	@echo "範例："
	@echo "  make artisan project=oppa_pos cmd=migrate"
	@echo "  make artisan project=test-mongo cmd='make:controller UserController'"
	@echo "  make composer project=oppa_pos cmd=install"
	@echo "  make npm project=oppa_pos cmd='run dev'"

php-bash:
	docker exec -it php bash

artisan:
	@if [ -z "$(project)" ] || [ -z "$(cmd)" ]; then \
		echo "錯誤：請指定 project 和 cmd"; \
		echo "範例：make artisan project=oppa_pos cmd=migrate"; \
		exit 1; \
	fi
	docker exec -it php php /var/www/html/$(project)/artisan $(cmd)

composer:
	@if [ -z "$(project)" ] || [ -z "$(cmd)" ]; then \
		echo "錯誤：請指定 project 和 cmd"; \
		echo "範例：make composer project=oppa_pos cmd=install"; \
		exit 1; \
	fi
	docker exec -it php composer -d /var/www/html/$(project) $(cmd)

npm:
	@if [ -z "$(project)" ] || [ -z "$(cmd)" ]; then \
		echo "錯誤：請指定 project 和 cmd"; \
		echo "範例：make npm project=oppa_pos cmd='run dev'"; \
		exit 1; \
	fi
	docker exec -it php npm --prefix /var/www/html/$(project) $(cmd)
