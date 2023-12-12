up:
	docker-compose up -d

down:
	docker-compose down

cli:
	docker exec -it php82-container /bin/bash

make test:
	docker exec -it php82-container /bin/bash -c "php bin/phpunit"

make coverage:
	docker exec -it php82-container /bin/bash -c "XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage"

make migrate:
	docker exec -it php82-container /bin/bash -c "php bin/console doctrine:migrations:migrate"