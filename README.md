# Seasonal Load Restrictions Email Service

This repository contains the application and OpenShift deployment configuration for an email subscription service that notifies subscribers about new and updated seasonal load restrictions.

The service is built on [phpList](https://github.com/phpList/phplist3) and includes a custom subscription frontend, the phpList Common and RSS Feed plugins, a service-area filtering plugin, and scheduled jobs for importing RSS items, processing the message queue, backing up the database, and cleaning up old feed data.

## How it works

1. Load restriction data is published as district text files and exposed as an RSS feed.
2. A scheduled job fetches new RSS items before the hour.
3. A second scheduled job processes the phpList queue just after the hour.
4. A nightly job backs up the MariaDB database and removes expired data.

The service comprises:

- phpList and the custom frontend in `compose/phplist/`
- Job scripts and their container image in `compose/jobs/`
- The Helm chart and environment values in `infrastructure/main/`

## Build container images

Build from the repository root with Docker or a compatible OCI builder:

```sh
docker build -t phplist:local compose/phplist
docker build -t phplist-jobs:local compose/jobs
```

The phpList image extends the upstream `phplist/phplist` image and downloads the Common plugin tag `3.35.6` and RSS Feed plugin tag `3.0.3` from GitHub during the image build. The jobs image is based on Alpine Linux and includes `curl` and the MariaDB client.

## Deploy with Helm

The Helm chart targets OpenShift. It creates phpList and MariaDB workloads, persistent volumes, network policies, Routes, and scheduled jobs.

```sh
cd infrastructure/main
helm lint . -f values-tst.yaml
helm template tst-loadx . -f values-tst.yaml
```

To install, provide the target namespace and a private values file containing the required credentials:

```sh
helm upgrade --install tst-loadx . \
  --namespace <namespace> --create-namespace \
  -f values-tst.yaml -f <private-secrets-file>
```

You will not yet have the remoteSecret as that is generated once it has been setup. You can go in later and get it from Config -> Settings and then add it to the Secret in OpenShift. This is used by a few of the cronjobs.

Do not put credentials in the checked-in values files or pass them with `--set` on the command line. The first install creates Kubernetes Secrets from the `mariadb.secret.*` and `phplist.secrets.*` values. Existing Secrets and the phpList ConfigMap are retained by the chart on upgrade; see [the chart deployment notes](infrastructure/main/README.md) before changing them.

The checked-in environment values retain the service's current public route and cluster-specific image references. Adapt the image references, route, mail host, storage class, and admin IP allowlist for a different cluster. Do not expose the phpList admin Route to untrusted networks.

## Configuration and operations

See [the chart README](infrastructure/main/README.md) for the value reference, secret handling, persistence behavior, and deployment commands. Job scripts document their required environment variables in their headers.

The public subscription frontend uses local feed paths and phpList URLs. The service's public route is configured in the environment values; the admin interface is served under `/list/admin/` and restricted by the configured OpenShift Route allowlist.


## Security

Do not commit passwords, tokens, private keys, subscriber data, database dumps, or other sensitive material. See [SECURITY.md](SECURITY.md) for vulnerability reporting guidance.

An earlier copy of the legacy OpenShift manifest contained credentials and an administrator email address in plain text. If that file exists in local copies or Git history, treat the credentials as exposed and rotate them before publishing.
