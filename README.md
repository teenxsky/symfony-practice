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

---

### Testing Guide

To run tests, follow these steps:

1. **Create the test database**:
   Run the following command to create a test database:

   ```bash
   make create-test-db
   ```

2. **Generate migrations (if not already created)**:
   If migrations are not created, generate them using:

   ```bash
   make make-migrations
   ```

3. **Apply migrations to the test database**:
   Apply the migrations to the test database using:

   ```bash
   make migrate-test-db
   ```

4. **Run the tests**:
   Finally, run all tests using:

   ```bash
   make run-tests
   ```

   To run only repository tests:

   ```bash
   make run-tests-repository
   ```

   To run only controller tests:

   ```bash
   make run-tests-controller
   ```

---

### Docker Commands

#### Build the Docker Images

To build the Docker images for the development environment, run:

```bash
make build
```

#### Start the Services

To start the services in detached mode (running in the background), use:

```bash
make up
```

If you want to start the services and see the logs in real-time, use:

```bash
make up-logs
```

#### Stop the Services

To stop the services, run:

```bash
make down
```

#### Clean Up

To stop and remove all containers, networks, volumes, and images, use:

```bash
make clean
```

To stop and remove all volumes, use:

```bash
make clean-volumes
```

---

### Database Commands

#### Create a New Migration

To create a new migration file in the backend container, run:

```bash
make make-migrations
```

#### Apply Migrations to the Database

To execute the Doctrine migrations in the backend container, use:

```bash
make migrate-db
```

#### Create a Database Backup

To create a database backup (dump.sql), run:

```bash
make create-dump
```

---

### Accessing the Backend Container Shell

To open a shell in the backend container, use:

```bash
make shell-backend
```

---

### Debugging with Xdebug

#### Show Xdebug Status

To check the current status of Xdebug, run:

```bash
make xdebug-status
```

#### Enable Xdebug

To enable Xdebug, run:

```bash
make xdebug-enable
```

#### Disable Xdebug

To disable Xdebug, run:

```bash
make xdebug-disable
```