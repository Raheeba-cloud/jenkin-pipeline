pipeline {
  agent any

  environment {
    GIT_CRED = 'git'                       // Jenkins credential id for GitHub (username + PAT)
    DOCKER_CRED = 'docker-hub-credentials' // Docker Hub credential id in Jenkins
    IMAGE_NAME = 'raheeba/my-php-site'     // <-- set your Docker Hub repo name
    IMAGE_TAG = "${env.BUILD_NUMBER}"
  }

  stages {
    stage('Checkout') {
      steps {
        // Use the same repo that contains the Jenkinsfile (safer)
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
            dockerImage.push('latest') // optional
          }
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
    success { echo "✅ Build and push completed successfully!" }
    failure { echo "❌ Build failed. Check logs for details." }
  }
}
