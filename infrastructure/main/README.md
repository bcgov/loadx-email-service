# phpList Helm chart

This chart deploys the Seasonal Load Restrictions email service to OpenShift. It includes phpList, MariaDB, persistent storage, Routes, NetworkPolicies, and the scheduled jobs for RSS processing and database cleanup.

## Deploy

Run Helm commands from this directory. Choose the values file for the target environment:

```powershell
helm lint . -f values-tst.yaml
helm template tst-loadx . -f values-tst.yaml
helm upgrade --install tst-loadx . --namespace <namespace> --create-namespace -f values-tst.yaml
```

For production, substitute `prd-loadx` and `values-prd.yaml`. The target OpenShift namespace must have access to the configured image registry and storage class.

The checked-in values files have blank credential fields. Before the first installation, create a private values file with non-empty values for `mariadb.secret.database`, `mariadb.secret.username`, `mariadb.secret.password`, `mariadb.secret.rootPassword`, and `phplist.secrets.*`. Start from [values-secrets.example.yaml](values-secrets.example.yaml), replace every placeholder, and keep the resulting file outside version control (the repository `.gitignore` excludes common local secret-file patterns). Do not pass credentials using `--set`, which may expose them in shell history or process listings.

For example, save the completed values in a local file such as `values-local.secrets.yaml`, then pass it after the environment file:

```powershell
helm upgrade --install tst-loadx . --namespace <namespace> --create-namespace `
  -f values-tst.yaml -f values-local.secrets.yaml
```

Treat the values file as a secret. The chart renders Secret resources from these values on the first install.

## Environment values

`values-tst.yaml` and `values-prd.yaml` configure the environment-specific release names, database credentials, application images, route host, and resource allocations. Common settings include:

| Values | Purpose |
| --- | --- |
| `mariadb.image`, `mariadb.replicas`, `mariadb.resources` | MariaDB image, replica count, and container requests/limits |
| `mariadb.service.port` | MariaDB service port |
| `mariadb.secret.*` | Initial MariaDB database, user, and passwords |
| `mariadb.persistence.data.*` | Database PVC access modes and requested size |
| `mariadb.persistence.backup.*` | Backup PVC access modes, storage class, and requested size |
| `phplist.image`, `phplist.jobsImage`, `phplist.replicas`, `phplist.resources` | phpList and jobs images, phpList replica count, and phpList container requests/limits |
| `phplist.service.port` | phpList service and container port |
| `phplist.config.*` | phpList mail host, feed path, and backup retention period |
| `phplist.secrets.*` | Initial phpList user, admin credentials, and remote-processing secret |
| `phplist.route.host` | Shared host for both public and admin Routes and the generated `PUBLIC_BASE_URL` |
| `phplist.route.vanityHost` | Shared host for the generated public and admin vanity Routes |
| `phplist.route.allowlist` | IP allowlist applied to admin Routes; also applied to public Routes when `phplist.route.applyAllowlistToAllRoutes` is enabled |
| `phplist.route.applyAllowlistToAllRoutes` | Whether to apply the IP allowlist to all public and admin Routes (enabled for test, disabled for production) |
| `phplist.cron.*` | Job schedules, concurrency, history, deadlines, and requests/limits for all three job containers |

Cron schedules are standard five-field cron expressions. The cleanup schedule is `0 1 * * *`; the chart does not set a `timeZone`, so the Kubernetes controller's timezone determines the schedule's timezone.

The phpList and jobs containers read MariaDB database name, user, and password from the MariaDB Secret. The database host is derived from the release fullname, and its port comes from `mariadb.service.port`.

## Persistent resources and manual edits

The MariaDB data and backup PVCs, both Secrets, the phpList ConfigMap, and both vanity Routes have the `helm.sh/resource-policy: keep` annotation. Helm leaves these resources behind when the release is uninstalled. They remain in the namespace until manually removed; deleting the namespace deletes them as well.

The Secret and ConfigMap templates use Helm `lookup`:

- On the first install, values create their initial data.
- On later upgrades, existing live Secret data and ConfigMap data take precedence over values, preserving manual data edits.
- Changing the corresponding values after creation does not update those existing data fields. To apply new values, deliberately edit the live resource or remove it and run a Helm upgrade so the chart recreates it from values.

The vanity Route templates also use `lookup`: they create the routes with default edge TLS settings initially, then preserve the live TLS settings on upgrades. This keeps certificates and keys added manually in OpenShift from being overwritten. The vanity Routes are retained on uninstall; remove them manually when they are no longer needed.

Because kept resources outlive their release, clean them up manually when they are no longer needed. Reusing the same resource names for a different release may require removing or adopting the existing resources first.

The chart uses `lookup` against the cluster API during install and upgrade. Helm needs permission to read the Secrets, ConfigMap, and Routes in the target namespace.
