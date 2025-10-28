pipeline {
  agent any
  options {
    skipDefaultCheckout(true)
  }

  environment {
    GIT_CRED = 'git'                                // Jenkins GitHub credentials ID
    DOCKER_CRED = 'docker-hub-credentials'          // Jenkins Docker Hub credentials ID
    IMAGE_NAME = 'raheeba/my-php-site'
    IMAGE_TAG = "${env.BUILD_NUMBER}"
    CHART_PATH = "charts/mysite"
    REPO_URL = 'https://github.com/Raheeba-cloud/jenkin-pipeline.git'
  }

  stages {

    stage('Checkout') {
      steps {
        script {
          echo "📥 Cloning repository manually..."
          deleteDir()
          withCredentials([usernamePassword(credentialsId: "${GIT_CRED}", usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
            sh """
              git clone https://${GIT_USER}:${GIT_PASS}@github.com/Raheeba-cloud/jenkin-pipeline.git .
              git checkout main
              echo "✅ Current directory:" && pwd
              echo "📂 Files:" && ls -la
            """
          }
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
          echo "📝 Updating Helm values.yaml with new tag..."
          sh """
            sed -i 's|tag:.*|tag: "${IMAGE_TAG}"|' ${CHART_PATH}/values.yaml
            cat ${CHART_PATH}/values.yaml
          """
        }
      }
    }

    stage('Commit & Push Helm Update') {
      steps {
        script {
          echo "🧭 Committing Helm update to GitHub..."
          withCredentials([usernamePassword(credentialsId: "${GIT_CRED}", usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
            sh """
              git config user.email "jenkins@local"
              git config user.name "Jenkins"

              git add ${CHART_PATH}/values.yaml
              git commit -m "Update image tag to ${IMAGE_TAG}" || echo "No changes to commit"
              git push https://${GIT_USER}:${GIT_PASS}@github.com/Raheeba-cloud/jenkin-pipeline.git HEAD:main
            """
          }
        }
      }
    }

    stage('Trigger ArgoCD Sync') {
      steps {
        script {
          echo "🔁 Triggering ArgoCD sync..."
          sh """
            microk8s kubectl patch application mysite -n argocd --type merge -p '{"spec":{"syncPolicy":{"automated":{"prune":true,"selfHeal":true}}}}' || true
            microk8s kubectl annotate application mysite -n argocd argocd.argoproj.io/sync-options=Force=true --overwrite || true
          """
        }
      }
    }

    stage('Clean up') {
      steps {
        script {
          echo "🧹 Cleaning local Docker images..."
          sh "docker rmi ${IMAGE_NAME}:${IMAGE_TAG} || true"
        }
      }
    }
  }

  post {
    success {
      echo "✅ Pipeline completed successfully — image pushed & deployed via ArgoCD!"
    }
    failure {
      echo "❌ Pipeline failed — check logs for more details."
    }
  }
}
