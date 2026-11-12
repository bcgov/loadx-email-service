# Github Workflows

### Initial Github Setup

The dev, uat, prod workflows need to have environment variables setup in OpenShift

Add these variables like this:
1. In Github go to Settings
1. Click Environments
1. Click New Environment
1. Give it a name (dev, uat, prod)
1. Add these Environment and Secret Variables
    1. Variable: `OPENSHIFT_NAMESPACE`
        - Use the project namespace with the `-dev` (ie `abc123-dev`)
    1. Variable: `OPENSHIFT_SERVER`
        - This will be the API Server you see when you get your token from OpenShift
    1. Secret: `OPENSHIFT_TOKEN`
        - Use the Pipeline Token (From `pipeline-token-xxxxxxxx` secret)
