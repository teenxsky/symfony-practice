# symfony-practice

## Usage Guide

### Requirements:

- Docker installed on your machine.
- Docker Compose installed on your machine.
- **Configure .env local files:** `.env.local`, `.env.dev.local`, `.env.test.local`:

```bash
cp .env .env.local
```

```bash
cp .env.dev .env.dev.local
```

```bash
cp .env.test .env.test.local
```

### Building the Docker Images

To build the Docker images for the development environment, run the following command:

```bash
make build
```

### Starting the Services

To start the services in detached mode (running in the background), use:

```bash
make up
```

If you want to start the services and see the logs in real-time, use:

```bash
make up-logs
```

### Stopping the Services

To stop the services, run:

```bash
make down
```

### Cleaning Up

To stop and remove all containers, networks, volumes, and images, use:

```bash
make clean
```

To stop and remove all volumes, use:

```bash
make clean-volumes
```

### Database Migrations

To create a new migration file in the backend container, run:

```bash
make make-migrations
```

To execute the Doctrine migrations in the backend container, use:

```bash
make migrate
```

### Database Backup

To create a database backup (dumb.sql), run:

```bash
make make-dumb
```

### Accessing the Backend Container Shell

To open a shell in the backend container, use:

```bash
make shell-backend
```
