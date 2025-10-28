pipeline {
  agent any

  environment {
    GIT_CRED = 'git'
    DOCKER_CRED = 'docker-hub-credentials'
    IMAGE_NAME = 'raheeba/my-php-site'
    IMAGE_TAG = "${env.BUILD_NUMBER}"
    CHART_PATH = "charts/mysite"
    REPO_URL = 'https://github.com/Raheeba-cloud/jenkin-pipeline.git'
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
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
          echo "📤 Pushing image to Docker Hub..."
          docker.withRegistry('https://index.docker.io/v1/', "${DOCKER_CRED}") {
            dockerImage.push()
            dockerImage.push('latest')
          }
        }
      }
    }

    stage('Update Helm values.yaml') {
      steps {
        script {
          echo "📝 Updating Helm values.yaml with new image tag..."
          sh '''
          sed -i "s|tag:.*|tag: \\"${IMAGE_TAG}\\"|" ${CHART_PATH}/values.yaml
          '''
        }
      }
    }

    stage('Commit & Push Helm Update') {
      steps {
        script {
          echo "🧭 Committing Helm chart changes to GitHub..."
          sh '''
          git config user.email "jenkins@local"
          git config user.name "Jenkins"
          git add ${CHART_PATH}/values.yaml
          git commit -m "Update image tag to ${IMAGE_TAG}" || echo "No changes to commit"
          git push https://${GIT_CRED_USR}:${GIT_CRED_PSW}@github.com/Raheeba-cloud/jenkin-pipeline.git HEAD:main
          '''
        }
      }
    }

    stage('Trigger ArgoCD Sync') {
      steps {
        script {
          echo "🔁 Forcing ArgoCD to sync latest changes..."
          sh '''
          microk8s kubectl patch application mysite -n argocd --type merge -p '{"spec":{"syncPolicy":{"automated":{"prune":true,"selfHeal":true}}}}' || true
          '''
        }
      }
    }
  }

  post {
    success {
      echo "✅ Successfully built, pushed, and deployed via ArgoCD!"
    }
    failure {
      echo "❌ Pipeline failed! Check logs for details."
    }
  }
}
