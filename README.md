# symfony-practice

## Usage Guide

### Requirements:

- Docker installed on your machine.
- Docker Compose installed on your machine.
- **Configure .env local files:** `.env.local`, `.env.dev.local`, `.env.test.local`:

1. Make `.env.local` files from examples :

```bash
cp .env .env.local
```

```bash
cp .env.dev .env.dev.local
```

```bash
cp .env.test .env.test.local
```

2. Create your unique `APP_SECRET` (optional):

```bash
sed -i "" "/^APP_SECRET=/d" .env.local && echo "APP_SECRET=$(php -r "print substr(base64_encode(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789')), 0, 32);")" >> .env.local
```

```bash
sed -i "" "/^APP_SECRET=/d" .env.dev.local && echo "APP_SECRET=$(php -r "print substr(base64_encode(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789')), 0, 32);")" >> .env.dev.local
```

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
