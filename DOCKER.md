# Build all services
docker compose build

# Start BE (kèm MySQL) and FE
docker compose up -d

# Follow logs
docker compose logs -f be fe

# Stop services
docker compose down

# Stop and remove database volume
docker compose down -v

# Backend container contains both Laravel/PHP and MySQL.
# MySQL is available from Laravel at 127.0.0.1:3306.

## Graphify

Graphify is packaged as the `graphify` Docker Compose service. The PyPI package
is `graphifyy==0.9.20`; it provides the `graphify` CLI. The service uses the
`tools` profile, so it does not start with the application stack by default.

Build the image:

```bash
docker compose --profile tools build graphify
```

Build a code/docs graph from the repository:

```bash
docker compose --profile tools run --rm graphify extract /workspace --force
```

For a code-only offline graph without HTML visualization:

```bash
docker compose --profile tools run --rm graphify extract /workspace --force --no-viz
```

The generated files are written to `graphify-out/` in the repository. Query an
existing graph without rebuilding it:

```bash
docker compose --profile tools run --rm graphify query \
	"what connects authentication to the database?" \
	--graph /workspace/graphify-out/graph.json
```

Show the installed CLI version:

```bash
docker compose --profile tools run --rm graphify --version
```
