# Security

## Reporting a vulnerability

Please do not report security vulnerabilities in public issues or discussions. If private vulnerability reporting is enabled for this repository, use GitHub's **Report a vulnerability** feature. Otherwise, contact the repository owner through a private channel published on the repository owner's GitHub profile.

Include the affected component, impact, and steps to reproduce where safe. Do not include real credentials, subscriber data, or other personal information in a report.

## Deployment and secret handling

- Supply database and phpList credentials through Kubernetes Secrets or a private Helm values file that is excluded from version control.
- Rotate credentials immediately if they have been committed, shared, or otherwise exposed. Removing them from the current file does not remove them from Git history.
- Restrict the phpList admin Route to trusted networks and review the configured allowlist before deployment.
- Protect backups and subscriber information as sensitive data.
