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
          echo "📥 Full authenticated checkout (${GIT_BRANCH})"
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
            ls -la
            git remote -v || true
            git rev-parse --is-inside-work-tree || true
          '''
        }
      }
    }

    stage('Build Docker Image') {
      steps {
        script {
          echo "🔨 Building Docker image ${IMAGE_NAME}:${IMAGE_TAG}"
          dockerImage = docker.build("${IMAGE_NAME}:${IMAGE_TAG}")
        }
      }
    }

    stage('Push to Docker Hub') {
      steps {
        script {
          echo "📤 Pushing image to Docker Hub"
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
          echo "📝 Update Helm values.yaml with tag ${IMAGE_TAG}"
          sh '''
            set -eu
            if [ ! -f "${CHART_PATH}/values.yaml" ]; then
              echo "ERROR: ${CHART_PATH}/values.yaml not found" >&2
              exit 1
            fi
            sed -i.bak -E "s/^(\\s*tag:\\s*).*/\\1\"${IMAGE_TAG}\"/" "${CHART_PATH}/values.yaml" || true
            echo "---- ${CHART_PATH}/values.yaml ----"
            cat "${CHART_PATH}/values.yaml"
            echo "-----------------------------------"
          '''
        }
      }
    }

    stage('Commit & Push Helm update') {
      steps {
        script {
          echo "🔁 Commit & push Helm change"
          withCredentials([usernamePassword(credentialsId: "${GIT_CRED}", usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
            // We deliberately do NOT enable -x here to avoid printing the push command (which causes masking issues).
            sh '''
              set -euo pipefail

              pwd
              ls -la

              if [ ! -d .git ]; then
                echo "⚠️ .git not found - aborting push" >&2
                exit 1
              fi

              git config user.email "jenkins@local"
              git config user.name "Jenkins"

              git add "${CHART_PATH}/values.yaml" || true

              if git diff --cached --quiet; then
                echo "No changes to commit"
              else
                git commit -m "ci: Update image tag to ${IMAGE_TAG} [skip ci]" || true

                # Build push URL inside shell and quote it as a single argument.
                REPO_NO_PROTO=$(echo "${REPO_URL}" | sed -e 's#^https://##' -e 's#^http://##')
                PUSH_URL="https://${GIT_USER}:${GIT_PASS}@${REPO_NO_PROTO}"

                # Perform the push without printing the full command (so masking won't corrupt it)
                git push "${PUSH_URL}" "HEAD:${GIT_BRANCH}"
              fi
            '''
          }
        }
      }
    }

    stage('Trigger ArgoCD sync') {
      steps {
        script {
          echo "🔁 Triggering ArgoCD sync (if configured)..."
          sh '''
            set -eux || true
            # If you are using kubectl via a Jenkins kubeconfig credential, ensure that is configured.
            microk8s kubectl -n argocd patch application mysite --type merge -p '{"spec":{"syncPolicy":{"automated":{"prune":true,"selfHeal":true}}}}' || true
            microk8s kubectl -n argocd annotate application mysite argocd.argoproj.io/sync-options=Force=true --overwrite || true
          '''
        }
      }
    }

    stage('Cleanup local images') {
      steps {
        script {
          echo "🧹 Cleaning local Docker image"
          sh "docker rmi ${IMAGE_NAME}:${IMAGE_TAG} || true"
        }
      }
    }
  }

  post {
    success { echo "✅ Pipeline completed successfully" }
    failure { echo "❌ Pipeline failed — check console output" }
  }
}
