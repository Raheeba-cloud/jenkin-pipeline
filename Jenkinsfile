pipeline {
  agent any
  environment {
    GIT_CRED    = 'git'                          // Jenkins credential id (username + PAT)
    DOCKER_CRED = 'docker-hub-credentials'      // Docker Hub credential id in Jenkins
    IMAGE_NAME  = 'raheeba/my-php-site'
    IMAGE_TAG   = "${env.BUILD_NUMBER}"
    CHART_PATH  = "charts/mysite"
    REPO_URL    = 'https://github.com/Raheeba-cloud/jenkin-pipeline.git'
    GIT_BRANCH  = 'main'
  }
  options {
    timestamps()
    timeout(time: 60, unit: 'MINUTES')
  }
  stages {
    stage('Checkout — full clone') {
      steps {
        script {
          echo ":inbox_tray: Doing a full authenticated checkout (${GIT_BRANCH})"
          checkout([
            $class: 'GitSCM',
            branches: [[name: "*/${GIT_BRANCH}"]],
            userRemoteConfigs: [[url: env.REPO_URL, credentialsId: env.GIT_CRED]],
            doGenerateSubmoduleConfigurations: false,
            extensions: [
              [$class: 'CloneOption', depth: 0, noTags: false, shallow: false, timeout: 10]
            ]
          ])
          sh '''
            echo "Workspace: $WORKSPACE"
            pwd
            echo "Files:"
            ls -la
            echo "Git remote:"
            git remote -v || true
            echo "Inside git worktree?"
            git rev-parse --is-inside-work-tree || true
          '''
        }
      }
    }
    stage('Build Docker Image') {
      steps {
        script {
          echo ":hammer: Building Docker image ${IMAGE_NAME}:${IMAGE_TAG}"
          dockerImage = docker.build("${IMAGE_NAME}:${IMAGE_TAG}")
        }
      }
    }
    stage('Push to Docker Hub') {
      steps {
        script {
          echo ":outbox_tray: Pushing image ${IMAGE_NAME}:${IMAGE_TAG} to Docker Hub"
          docker.withRegistry('https://index.docker.io/v1/', "${DOCKER_CRED}") {
            dockerImage.push("${IMAGE_TAG}")
            dockerImage.push('latest')
          }
        }
      }
    }
    stage('Update Helm values.yaml') {
      steps {
        script {
          echo ":memo: Update Helm values.yaml with tag ${IMAGE_TAG}"
          sh '''
            set -eux
            if [ ! -f "$CHART_PATH/values.yaml" ]; then
              echo "ERROR: $CHART_PATH/values.yaml not found"
              exit 1
            fi
            sed -i.bak -E "s/^(\\s*tag:\\s*).*/\\1\"${IMAGE_TAG}\"/" "$CHART_PATH/values.yaml" || true
            echo "---- updated $CHART_PATH/values.yaml ----"
            cat "$CHART_PATH/values.yaml"
            echo "------------------------------------------"
          '''
        }
      }
    }
    stage('Commit & Push Helm update') {
      steps {
        script {
          echo ":repeat: Commit & push Helm change"
          // IMPORTANT: single-quoted sh block so Jenkins expands secrets inside the shell, not in Groovy
          withCredentials([usernamePassword(credentialsId: "${GIT_CRED}", usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
            sh '''
              set -eux
              # ensure we are at repo root and .git exists
              pwd
              ls -la
              if [ ! -d .git ]; then
                echo "ERROR: .git not found in workspace - aborting push"
                exit 1
              fi
              # Trim any CR/LF or stray whitespace from credentials (common when credentials were copied with newline)
              GIT_USER=$(printf "%s" "$GIT_USER" | tr -d '\r\n' | sed -e 's/^[[:space:]]*//;s/[[:space:]]*$//')
              GIT_PASS=$(printf "%s" "$GIT_PASS" | tr -d '\r\n' | sed -e 's/^[[:space:]]*//;s/[[:space:]]*$//')
              echo "Using git user: <${GIT_USER}> (hidden in logs, shown here for debug only brackets)"
              # Do not echo GIT_PASS
              git config user.email "jenkins@local"
              git config user.name "Jenkins"
              git add "$CHART_PATH/values.yaml"
              if git diff --cached --quiet; then
                echo "No changes to commit"
              else
                git commit -m "ci: Update image tag to $IMAGE_TAG [skip ci]" || true
                # Build a safe push URL inside the shell (strip protocol from REPO_URL)
                REPO_NO_PROTO=$(echo "$REPO_URL" | sed -e 's#^https://##' -e 's#^http://##' -e 's#/$##')
                # Remove any stray whitespace from REPO_NO_PROTO
                REPO_NO_PROTO=$(printf "%s" "$REPO_NO_PROTO" | tr -d '\r\n' | sed -e 's/^[[:space:]]*//;s/[[:space:]]*$//')
                PUSH_URL="https://${GIT_USER}:${GIT_PASS}@${REPO_NO_PROTO}"
                # Set the origin remote to the credentialed URL (one argument, quoted)
                git remote set-url origin "$PUSH_URL"
                # verify remote
                git remote -v
                # push to branch
                git push origin "HEAD:${GIT_BRANCH}"
              fi
            '''
          }
        }
      }
    }
    stage('Trigger ArgoCD sync') {
      steps {
        script {
          echo ":repeat: Triggering ArgoCD sync..."
          sh '''
            set -eux || true
            microk8s kubectl -n argocd patch application mysite --type merge -p '{"spec":{"syncPolicy":{"automated":{"prune":true,"selfHeal":true}}}}' || true
            microk8s kubectl -n argocd annotate application mysite argocd.argoproj.io/sync-options=Force=true --overwrite || true
          '''
        }
      }
    }
    stage('Cleanup local images') {
      steps {
        script {
          echo ":broom: Cleaning local Docker images"
          sh "docker rmi ${IMAGE_NAME}:${IMAGE_TAG} || true"
        }
      }
    }
  }
  post {
    success { echo ":white_tick: Pipeline completed successfully" }
    failure { echo ":x: Pipeline failed — check console output" }
  }
}
