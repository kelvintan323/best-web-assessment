# Best Web Assessment

A Laravel + Vue.js application with Docker support.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Vue.js 3 + Vuetify + Vite
- **Database**: MySQL 8.0
- **Server**: Nginx

## Prerequisites

- [Docker](https://www.docker.com/get-started) installed
- [Docker Compose](https://docs.docker.com/compose/install/) installed

## Quick Start

### Step 1: Clone the Repository

```bash
git clone https://github.com/kelvintan323/best-web-assessment.git
cd best-web-assessment
```

### Step 2: Setup Environment File

```bash
cp .env.example .env
```

The default configuration uses:
- **Database**: `best_web`
- **Username**: `root`
- **Password**: (empty)
- **Host**: `mysql` (Docker container name)

### Step 3: Build Docker Containers

```bash
make build
```

### Step 4: Start Containers

```bash
make up
```

This starts 4 containers:

| Container | Port | Description |
|-----------|------|-------------|
| `php-service` | 9000 (internal) | PHP-FPM application |
| `nginx-service` | **8000** | Nginx web server |
| `mysql-service` | **3306** | MySQL database |
| `frontend` | - | Frontend build (only runs once) |

### Step 5: Install Dependencies & Setup Database

```bash
make install
```

This command will:
1. Install Composer dependencies
2. Generate application key
3. Run database migrations
4. Seed the database

### Step 6: Access the Application

Open your browser and visit:

- **Application**: http://localhost:8000

## Available Commands

| Command | Description |
|---------|-------------|
| `make up` | Start all containers |
| `make down` | Stop all containers |
| `make build` | Build containers (no cache) |
| `make install` | Install dependencies and setup database |
| `make migrate` | Run database migrations |
| `make seed` | Run database seeders |
| `make fresh` | Fresh migration with seeding |
| `make frontend` | Rebuild frontend (clean install) |
| `make shell` | Access app container bash |
| `make logs` | View container logs |
| `make clear` | Clear Laravel caches |
| `make test` | Run tests |

## Development Workflow

### After Making Frontend Changes (Vue/JS/CSS)

```bash
make frontend
```

### After Making Backend Changes (PHP)

Changes are reflected immediately - no action needed.

### After Changing Nginx Config

```bash
docker-compose restart nginx
```

### After Changing Docker Config (Dockerfile/docker-compose.yml)

```bash
make down
make build
make up
```

## Troubleshooting

### Permission Issues

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Refused

Make sure your `.env` file has:
```
DB_HOST=mysql
```
**Not** `127.0.0.1` (that's for local development without Docker).

### Clear All Caches

```bash
make clear
```

### Reset Database

```bash
make fresh
```

### Reset Everything (Nuclear Option)

```bash
make down
docker volume rm best-web-assessment_mysql-data
rm -rf frontend/node_modules
make build
make up
make install
```

## Project Structure

```
.
├── app/                    # Laravel application
├── frontend/               # Vue.js frontend
│   ├── src/
│   │   ├── pages/         # Vue pages (file-based routing)
│   │   ├── components/    # Vue components
│   │   └── layouts/       # Layout components
│   └── package.json
├── public/                 # Public assets (frontend build output)
├── docker/
│   ├── nginx/default.conf # Nginx configuration
│   └── php/local.ini      # PHP configuration
├── docker-compose.yml      # Docker services
├── Dockerfile             # PHP-FPM image
├── Makefile               # Shortcut commands
└── .env.example           # Environment template
```

## License

This project is open-sourced software.
