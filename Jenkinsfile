pipeline {
  agent any

  environment {
    GIT_CRED   = 'git'                         // replace with your Git credentials id if different
    REPO_URL   = 'https://github.com/Raheeba-cloud/jenkin-pipeline.git'
    GIT_BRANCH = 'main'
  }

  stages {
    stage('Checkout - full clone (diagnostic)') {
      steps {
        script {
          echo ">>> Starting full checkout of ${REPO_URL} (${GIT_BRANCH})"
          checkout([
            $class: 'GitSCM',
            branches: [[name: "*/${GIT_BRANCH}"]],
            userRemoteConfigs: [[url: env.REPO_URL, credentialsId: env.GIT_CRED]],
            doGenerateSubmoduleConfigurations: false,
            extensions: [
              [$class: 'CloneOption', depth: 0, noTags: false, shallow: false, reference: '', timeout: 10]
            ]
          ])

          echo ">>> Basic workspace info"
          sh '''
            set -eux
            echo "WORKSPACE: ${WORKSPACE:-unknown}"
            pwd
            echo "ls -la (workspace):"
            ls -la
            echo
            echo "Check for .git directory and list"
            if [ -d .git ]; then
              echo ".git exists. Listing .git:"
              ls -la .git || true
              echo "cat .git/config (redact URL if you want):"
              sed -n '1,120p' .git/config || true
            else
              echo "WARNING: .git directory not present"
            fi

            echo "git binary info:"
            if command -v git >/dev/null 2>&1; then
              git --version
            else
              echo "git not found on PATH"
            fi

            echo "Attempting a safe git command: git rev-parse --is-inside-work-tree"
            git rev-parse --is-inside-work-tree || true

            echo "Print environment variables relevant to git:"
            env | egrep 'GIT|WORKSPACE|HOME' || true
          '''
        }
      }
    }

    stage('No-op') {
      steps {
        echo "Diagnostic checkout finished."
      }
    }
  }

  post {
    always {
      echo "Diagnostic job complete"
    }
  }
}
