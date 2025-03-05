-include ./.env
DOCKER_COMPOSE_DEV = docker-compose -f ./.docker/docker-compose.dev.yaml
DOCKER_COMPOSE_PROD = docker-compose -f ./.docker/docker-compose.prod.yaml

# Build the Docker images for the development environment
build-dev:
	$(DOCKER_COMPOSE_DEV) build

# Starts the services in detached mode
up-dev:
	$(DOCKER_COMPOSE_DEV) up -d --watch

# Starts the services and logs are displayed.
up-logs-dev:
	$(DOCKER_COMPOSE_DEV) up --watch

# Stops the services
down-dev:
	$(DOCKER_COMPOSE_DEV) down

# Stops and removes all containers, networks, volumes, and images
clean-dev:
	$(DOCKER_COMPOSE_DEV) down --rmi all

# Stops and removes all volumes
clean-volumes-dev:
	$(DOCKER_COMPOSE_DEV) down -v

# Creates a new migration file in the backend container
make-migrations-dev:
	$(DOCKER_COMPOSE_DEV) exec backend bash -c "bin/console make:migration"

# Make dumb and after this executes Doctrine migrations in the backend container
migrate-dev:
	make make-dumb-dev && $(DOCKER_COMPOSE_DEV) exec backend bash -c "bin/console doctrine:migrations:migrate"

# Creates database backup (dumb.sql)
make-dumb-dev:
	$(DOCKER_COMPOSE_DEV) exec database bash -c 'PGPASSWORD="admin" pg_dump --username $(POSTGRES_USER) app > /docker-entrypoint-initdb.d/dumb__$$(date +%H:%M:%S__%d-%m-%Y).sql'

# Open shell in the backend container
shell-backend-dev:
	$(DOCKER_COMPOSE_DEV) exec backend bash
