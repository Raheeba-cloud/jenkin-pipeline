pipeline {
  agent any

  environment {
    GIT_CRED    = 'git'                          // Jenkins credentials ID (username + PAT)
    DOCKER_CRED = 'docker-hub-credentials'      // Jenkins Docker Hub credentials ID
    IMAGE_NAME  = 'raheeba/my-php-site'
    IMAGE_TAG   = "${env.BUILD_NUMBER}"
    CHART_PATH  = "charts/mysite"
    REPO_URL    = 'https://github.com/Raheeba-cloud/jenkin-pipeline.git'
    GIT_BRANCH  = 'main'
  }

  options {
    // keep build logs for troubleshooting if needed
    timestamps()
    // limits to avoid runaway builds (adjust to suit)
    timeout(time: 60, unit: 'MINUTES')
  }

  stages {

    stage('Checkout — full clone') {
      steps {
        script {
          echo "📥 Doing a full authenticated checkout of ${REPO_URL} (${GIT_BRANCH})"

          // Explicit full clone via GitSCM ensures a real .git directory (avoids lightweight checkout issues)
          checkout([
            $class: 'GitSCM',
            branches: [[name: "*/${GIT_BRANCH}"]],
            userRemoteConfigs: [[url: env.REPO_URL, credentialsId: env.GIT_CRED]],
            doGenerateSubmoduleConfigurations: false,
            extensions: [
              // ensure a full clone with all history (no shallow), and fetch tags
              [$class: 'CloneOption', depth: 0, noTags: false, shallow: false, reference: '', timeout: 10]
            ]
          ])

          sh '''
            echo "✅ Workspace root:"
            pwd
            echo "📂 Files:"
            ls -la
            echo "📝 Git status (porcelain):"
            git status --porcelain || true
            echo "Git remote:"
            git remote -v || true
          '''
        }
      }
    }

    stage('Build Docker image') {
      steps {
        script {
          echo "🔨 Building Docker image ${IMAGE_NAME}:${IMAGE_TAG}"
          // docker.build will use the Docker daemon available to the agent. If running on Kubernetes node agents,
          // ensure the agent has docker available or use a Docker-in-Docker approach / buildkit / kaniko as appropriate.
          dockerImage = docker.build("${IMAGE_NAME}:${IMAGE_TAG}")
        }
      }
    }

    stage('Push to Docker Hub') {
      steps {
        script {
          echo "📤 Pushing image ${IMAGE_NAME}:${IMAGE_TAG} to Docker Hub"
          docker.withRegistry('https://index.docker.io/v1/', "${DOCKER_CRED}") {
            dockerImage.push("${IMAGE_TAG}")
            // optionally update latest tag
            dockerImage.push('latest')
          }
        }
      }
    }

    stage('Update Helm values.yaml') {
      steps {
        script {
          echo "📝 Updating Helm values.yaml tag => ${IMAGE_TAG}"
          sh '''
            set -eux
            # make sure file exists
            if [ ! -f "${CHART_PATH}/values.yaml" ]; then
              echo "ERROR: ${CHART_PATH}/values.yaml not found"
              exit 1
            fi

            # replace the tag line while preserving indentation
            # This sed matches a line containing "tag:" and replaces the RHS with the new tag
            sed -i.bak -E "s/^(\\s*tag:\\s*).*/\\1\"${IMAGE_TAG}\"/" ${CHART_PATH}/values.yaml || true

            echo "---- updated ${CHART_PATH}/values.yaml ----"
            cat ${CHART_PATH}/values.yaml
            echo "-------------------------------------------"
          '''
        }
      }
    }

    stage('Commit & Push Helm update') {
      steps {
        script {
          echo "🔁 Commit and push Helm values change back to ${REPO_URL}:${GIT_BRANCH}"
          withCredentials([usernamePassword(credentialsId: "${GIT_CRED}", usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
            sh '''
              set -eux

              # confirm we're at repo root and that .git exists; if not, initialize and fetch remote
              pwd
              ls -la

              if [ ! -d .git ]; then
                echo "⚠️ .git missing - initializing and fetching remote branch ${GIT_BRANCH}"
                git init
                git remote add origin "${REPO_URL}"
                # fetch enough history to reset
                git fetch --no-tags --depth=1 origin ${GIT_BRANCH} || git fetch origin ${GIT_BRANCH}
                git reset --hard origin/${GIT_BRANCH} || true
              fi

              # set committer metadata
              git config user.email "jenkins@local"
              git config user.name "Jenkins"

              # add and commit only if there are changes
              git add "${CHART_PATH}/values.yaml"
              if git diff --cached --quiet; then
                echo "No changes to commit"
              else
                git commit -m "ci: Update image tag to ${IMAGE_TAG} [skip ci]" || true

                # Push using credentials; Jenkins masks GIT_USER/GIT_PASS in logs
                # Strip protocol for embedding credentials safely
                REPO_NO_PROTO=${REPO_URL#https://}
                git push "https://${GIT_USER}:${GIT_PASS}@${REPO_NO_PROTO}" HEAD:${GIT_BRANCH}
              fi
            '''
          }
        }
      }
    }

    stage('Trigger ArgoCD sync') {
      steps {
        script {
          echo "🔁 Triggering ArgoCD sync for mysite (if ArgoCD is available)"
          sh '''
            set -eux || true
            # Patch app syncPolicy (idempotent)
            microk8s kubectl -n argocd patch application mysite --type merge -p '{"spec":{"syncPolicy":{"automated":{"prune":true,"selfHeal":true}}}}' || true
            # Force a sync by annotating; safe to run multiple times
            microk8s kubectl -n argocd annotate application mysite argocd.argoproj.io/sync-options=Force=true --overwrite || true
            echo "ArgoCD sync trigger attempted (check ArgoCD app status for details)"
          '''
        }
      }
    }

    stage('Cleanup local images') {
      steps {
        script {
          echo "🧹 Cleaning local Docker images to save disk space"
          sh "docker rmi ${IMAGE_NAME}:${IMAGE_TAG} || true"
        }
      }
    }
  }

  post {
    success {
      echo "✅ Pipeline finished successfully: ${IMAGE_NAME}:${IMAGE_TAG}"
    }
    failure {
      echo "❌ Pipeline failed — check console output for details."
    }
    always {
      // optional workspace cleanup for long-running agents (uncomment if desired)
      // cleanWs()
      echo "🔚 Pipeline finished (always section)"
    }
  }
}
