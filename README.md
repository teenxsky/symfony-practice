# symfony-practice

## Usage Guide

**Prerequisites:** 
- Docker installed on your machine.
- Docker Compose installed on your machine.
- **EDIT FILES:** `.env`, `.env.dev`, `.env.test`.

### Building the Docker Images

To build the Docker images for the development environment, run the following command:

```bash
make build-dev
```

### Starting the Services

To start the services in detached mode (running in the background), use:

```bash
make up-dev
```

If you want to start the services and see the logs in real-time, use:

```bash
make up-logs-dev
```

### Stopping the Services

To stop the services, run:

```bash
make down-dev
```

### Cleaning Up

To stop and remove all containers, networks, volumes, and images, use:

```bash
make clean-dev
```

To stop and remove all volumes, use:

```bash
make clean-volumes-dev
```

### Database Migrations

To create a new migration file in the backend container, run:

```bash
make make-migrations-dev
```

To execute the Doctrine migrations in the backend container, use:

```bash
make migrate-dev
```

### Database Backup

To create a database backup (dumb.sql), run:

```bash
make make-dumb-dev
```

### Accessing the Backend Container Shell

To open a shell in the backend container, use:

```bash
make shell-backend-dev
```
