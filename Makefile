-include ./.env.local
DOCKER_COMPOSE = docker-compose -f ./.docker/docker-compose.yaml --env-file .env.local
XDEBUG = $(DOCKER_COMPOSE) exec -u root backend php .docker/php/xdebug.php


#--------------- DOCKER COMPOSE COMMANDS ---------------#

# Build the Docker images for the development environment
build:
	$(DOCKER_COMPOSE) build

# Starts the services in detached mode
up:
	$(DOCKER_COMPOSE) up -d

# Starts the services and logs are displayed.
up-logs:
	$(DOCKER_COMPOSE) up

# Stops the services
down:
	$(DOCKER_COMPOSE) down

# Stops and removes all containers, networks, volumes, and images
clean:
	$(DOCKER_COMPOSE) down --rmi all

# Stops and removes all volumes
clean-volumes:
	$(DOCKER_COMPOSE) down -v


#--------------- DATABASE COMMANDS ---------------#

# Creates a new migration file
make-migrations:
	$(DOCKER_COMPOSE) exec backend bash -c "bin/console make:migration"

# Creates a database for backend
create-db:
	$(DOCKER_COMPOSE) exec backend bash -c 'bin/console doctrine:database:create'

# Make dumb and after this executes Doctrine migrations
migrate-db:
	make create-dump && $(DOCKER_COMPOSE) exec backend bash -c "bin/console doctrine:migrations:migrate"

# Creates a new database for tests
create-test-db:
	$(DOCKER_COMPOSE) exec backend bash -c 'bin/console doctrine:database:create --env=test'

# Executes Doctrine migrations for test database
migrate-test-db:
	$(DOCKER_COMPOSE) exec backend bash -c 'bin/console doctrine:migrations:migrate --env=test'

# Creates database backup (dump.sql)
create-dump:
	$(DOCKER_COMPOSE) exec database bash -c 'PGPASSWORD=$(POSTGRES_PASSWORD) pg_dump --username $(POSTGRES_USER) $(POSTGRES_DB) > /docker-entrypoint-initdb.d/dump__$$(date +%H:%M:%S__%d-%m-%Y).sql'


#--------------- SYMFONY COMMANDS ---------------#

# Makes a new symfony entity
create-entity:
	$(DOCKER_COMPOSE) exec backend bash -c "bin/console make:entity"

# Makes a new symfony controller
create-controller:
	$(DOCKER_COMPOSE) exec backend bash -c "bin/console make:controller"

# Add a new symfony dependency
add-dependency:
	@bash -c 'read -p "Enter the dependency name: " dep_name && \
	echo "Installing dependency: $$dep_name" && \
	$(DOCKER_COMPOSE) exec backend bash -c "composer require $$dep_name"'

# Open backend container shell
shell-backend:
	$(DOCKER_COMPOSE) exec backend bash


#--------------- TESTING COMMANDS ---------------#

# Run all tests in the backend container
run-tests:
	$(DOCKER_COMPOSE) exec backend bash -c "vendor/bin/phpunit"

# Run repository tests in the backend container 
run-tests-repository:
	$(DOCKER_COMPOSE) exec backend bash -c "vendor/bin/phpunit tests/repository"

# Run controller tests in the backend container
run-tests-controller:
	$(DOCKER_COMPOSE) exec backend bash -c "vendor/bin/phpunit tests/Controller"


#--------------- DEBUG COMMANDS ---------------#

# Show Xdebug status
xdebug-status:
	$(XDEBUG) status

# Enable Xdebug
xdebug-enable:
	$(XDEBUG) enable
	$(DOCKER_COMPOSE) restart backend
	$(XDEBUG) status

# Disable Xdebug
xdebug-disable:
	$(XDEBUG) disable
	$(DOCKER_COMPOSE) restart backend
	$(XDEBUG) status


#--------------- HELP COMMAND ---------------#
help:
	@echo "\033[1;34mMakefile commands:\033[0m"
	@echo "  \033[1;33m------------------------\033[0m"
	@echo "  \033[1;32mDocker Compose commands:\033[0m"
	@echo "  \033[1;36mbuild\033[0m                - Build the Docker images for the development environment"
	@echo "  \033[1;36mup\033[0m                   - Start the services in detached mode"
	@echo "  \033[1;36mup-logs\033[0m              - Start the services and display logs"
	@echo "  \033[1;36mdown\033[0m                 - Stop the services"
	@echo "  \033[1;36mclean\033[0m                - Stop and remove all containers, networks, volumes, and images"
	@echo "  \033[1;36mclean-volumes\033[0m        - Stop and remove all volumes"
	@echo "  \033[1;33m------------------------\033[0m"
	@echo "  \033[1;32mDatabase commands:\033[0m"
	@echo "  \033[1;36mmake-migrations\033[0m      - Create a new migration file in the backend container"
	@echo "  \033[1;36mcreate-db\033[0m            - Create a database for backend"
	@echo "  \033[1;36mmigrate-db\033[0m           - Execute Doctrine migrations for the database"
	@echo "  \033[1;36mcreate-test-db\033[0m       - Create a new database for tests"
	@echo "  \033[1;36mmigrate-test-db\033[0m      - Execute Doctrine migrations for test database"
	@echo "  \033[1;36mcreate-dump\033[0m          - Create database backup (dump.sql)"
	@echo "  \033[1;33m------------------------\033[0m"
	@echo "  \033[1;32mSymfony commands:\033[0m"
	@echo "  \033[1;36mcreate-entity\033[0m        - Make a new entity in the backend container"
	@echo "  \033[1;36mcreate-controller\033[0m    - Make a new symfony controller"
	@echo "  \033[1;36madd-dependency\033[0m       - Add a new symfony dependency"
	@echo "  \033[1;36mshell-backend\033[0m        - Open backend container shell"
	@echo "  \033[1;33m------------------------\033[0m"
	@echo "  \033[1;32mTesting commands:\033[0m"
	@echo "  \033[1;36mrun-tests\033[0m            - Run all tests in the backend container"
	@echo "  \033[1;36mrun-tests-repository\033[0m - Run repository tests in the backend container"
	@echo "  \033[1;36mrun-tests-controller\033[0m - Run controller tests in the backend container"
	@echo "  \033[1;33m------------------------\033[0m"
	@echo "  \033[1;32mDebug commands:\033[0m"
	@echo "  \033[1;36mxdebug-status\033[0m        - Show Xdebug status"
	@echo "  \033[1;36mxdebug-enable\033[0m        - Enable Xdebug"
	@echo "  \033[1;36mxdebug-disable\033[0m       - Disable Xdebug"

.PHONY: build up up-logs down clean clean-volumes make-migrations create-db migrate-db create-test-db migrate-test-db create-dump create-entity create-controller add-dependency shell-backend run-tests run-tests-repository run-tests-controller xdebug-status xdebug-enable xdebug-disable help
