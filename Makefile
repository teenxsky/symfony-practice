-include ./.env.dev.local
DOCKER_COMPOSE = docker-compose -f ./.docker/docker-compose.yaml --env-file .env.local

# Build the Docker images for the development environment
build:
	$(DOCKER_COMPOSE) build

# Starts the services in detached mode
up:
	$(DOCKER_COMPOSE) up -d

# Starts the services and logs are displayed.
up-logs:
	$(DOCKER_COMPOSE) up --watch --no-deps --build

# Stops the services
down:
	$(DOCKER_COMPOSE) down

# Stops and removes all containers, networks, volumes, and images
clean:
	$(DOCKER_COMPOSE) down --rmi all

# Stops and removes all volumes
clean-volumes:
	$(DOCKER_COMPOSE) down -v

# Creates a new migration file in the backend container
make-migrations:
	$(DOCKER_COMPOSE) exec backend bash -c "bin/console make:migration"

# Make dumb and after this executes Doctrine migrations in the backend container
migrate:
	make make-dumb-dev && $(DOCKER_COMPOSE) exec backend bash -c "bin/console doctrine:migrations:migrate"

# Creates database backup (dump.sql)
make-dump:
	$(DOCKER_COMPOSE) exec database bash -c 'PGPASSWORD="admin" pg_dump --username $(POSTGRES_USER) app > /docker-entrypoint-initdb.d/dump__$$(date +%H:%M:%S__%d-%m-%Y).sql'

# Open shell in the backend container
shell-backend:
	$(DOCKER_COMPOSE) exec backend bash
