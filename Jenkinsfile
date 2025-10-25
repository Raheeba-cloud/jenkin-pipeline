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
          GIT_SHA = sh(script: "git rev-parse --short HEAD", returnStdout: true).trim()
          IMAGE_TAG = "${GIT_SHA}"
          echo "Using image tag: ${IMAGE_TAG}"
        }
      }
    }

    stage('Build & Push Docker Image') {
      steps {
        withCredentials([usernamePassword(credentialsId: "${DOCKERHUB_CREDS}", usernameVariable: 'DH_USER', passwordVariable: 'DH_PASS')]) {
          sh '''
            echo "$DH_PASS" | docker login -u "$DH_USER" --password-stdin
            docker build -t ${IMAGE_REPO}:latest -t ${IMAGE_REPO}:${IMAGE_TAG} .
            docker push ${IMAGE_REPO}:latest
            docker push ${IMAGE_REPO}:${IMAGE_TAG}
            docker logout
          '''
        }
      }
    }

    stage('Update Helm values and Push to Git (trigger ArgoCD)') {
      steps {
        withCredentials([usernamePassword(credentialsId: "${GIT_CREDS}", usernameVariable: 'GH_USER', passwordVariable: 'GH_TOKEN')]) {
          sh '''
            set -e
            git config user.email "jenkins@ci"
            git config user.name "jenkins-ci"

            # create branch for change
            BRANCH="ci/update-image-${IMAGE_TAG}"
            git checkout -b ${BRANCH}

            # in-place edit of values.yaml using python (no external YAML lib required)
            python3 - <<PY
import os, re
f = os.getenv("CHART_VALUES")
repo = os.getenv("IMAGE_REPO")
tag = os.getenv("IMAGE_TAG")
s = open(f).read()
new = "image:\\n  repository: %s\\n  tag: %s" % (repo, tag)
s2 = re.sub(r'(?ms)^image:\\n\\s*repository:.*?\\n\\s*tag:.*', new, s)
if s2 == s:
    s2 = new + "\\n\\n" + s
open(f, "w").write(s2)
PY

            git add ${CHART_VALUES}
            git commit -m "ci: update chart image to ${IMAGE_REPO}:${IMAGE_TAG}" || true

            # push back to repo using HTTPS token
            git push "https://${GH_USER}:${GH_TOKEN}@${GIT_REMOTE_URL#https://}" HEAD:${GIT_BRANCH}
          '''
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
