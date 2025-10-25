pipeline {
  agent any

  environment {
    DOCKERHUB_CREDS = "dockerhub-creds"   // Jenkins credential ID for Docker Hub
    GIT_CREDS       = "github-creds"      // Jenkins credential ID for GitHub (username/token)
    IMAGE_REPO      = "raheeba/mysite"    // change to your Docker Hub repo (user/repo)
    CHART_VALUES    = "charts/mysite/values.yaml"
    GIT_REMOTE_URL  = "https://github.com/Raheeba-cloud/jenkin-pipeline.git" // change to your repo URL
    GIT_BRANCH      = "main"              // branch ArgoCD watches
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
        script {
          // Use Jenkins build number for the image tag
          env.IMAGE_TAG = "${env.BUILD_NUMBER}"
          echo "Using image tag (build number): ${env.IMAGE_TAG}"
        }
      }
    }

    stage('Agent pre-checks') {
      steps {
        sh '''
          set -euo pipefail
          echo "Checking required commands..."
          for cmd in docker git; do
            if ! command -v $cmd >/dev/null 2>&1; then
              echo "ERROR: required command '$cmd' not found on agent. Install it or use an agent/pod that provides it."
              exit 127
            fi
          done
          echo "docker version:"
          docker --version || true
        '''
      }
    }

    stage('Build & Push Docker Image') {
      steps {
        withCredentials([usernamePassword(credentialsId: "${DOCKERHUB_CREDS}", usernameVariable: 'DH_USER', passwordVariable: 'DH_PASS')]) {
          sh """
            set -euo pipefail
            set -x
            echo "$DH_PASS" | docker login -u "$DH_USER" --password-stdin
            docker build --pull -t ${IMAGE_REPO}:latest -t ${IMAGE_REPO}:${IMAGE_TAG} .
            docker push ${IMAGE_REPO}:latest
            docker push ${IMAGE_REPO}:${IMAGE_TAG}
            docker logout
          """
        }
      }
    }

    stage('Update Helm values and Push to Git (trigger ArgoCD)') {
      steps {
        withCredentials([usernamePassword(credentialsId: "${GIT_CREDS}", usernameVariable: 'GH_USER', passwordVariable: 'GH_TOKEN')]) {
          script {
            def valsPath = "${env.WORKSPACE}/${CHART_VALUES}"
            def text = readFile(file: valsPath)
            def repoLine = "  repository: \"${IMAGE_REPO}\""
            def tagLine  = "  tag: \"${IMAGE_TAG}\""
            if (text =~ /(?m)^image:\n(?:[ \t].*\n)*$/) {
              text = text.replaceAll("(?ms)^image:\\n(?:\\s*repository:.*\\n)?(?:\\s*tag:.*\\n)?", "image:\\n${repoLine}\\n${tagLine}\\n")
            } else {
              text = "image:\\n${repoLine}\\n${tagLine}\\n\n" + text
            }
            writeFile(file: valsPath, text: text)

            sh """
              set -euo pipefail
              set -x
              git config user.email "jenkins@ci"
              git config user.name "jenkins-ci"
              git checkout -b ci/update-image-${IMAGE_TAG}
              git add ${CHART_VALUES}
              git commit -m "ci: update chart image to ${IMAGE_REPO}:${IMAGE_TAG}" || true
              REMOTE="${GIT_REMOTE_URL}"
              REMOTE_NO_PROTO=$(echo "$REMOTE" | sed 's#^https://##')
              git push "https://${GH_USER}:${GH_TOKEN}@${REMOTE_NO_PROTO}" HEAD:${GIT_BRANCH}
            """
          }
        }
      }
    }
  }

  post {
    success {
      echo "Build, push and Git update complete. ArgoCD should detect and sync the change."
    }
    failure {
      echo "Pipeline failed — check console output"
    }
  }
}
