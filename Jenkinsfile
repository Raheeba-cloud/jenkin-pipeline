pipeline {
  agent any

  environment {
    GIT_CRED = 'git'                       
    DOCKER_CRED = 'docker-hub-credentials' 
    IMAGE_NAME = 'raheeba/my-php-site'     
    IMAGE_TAG = "${env.BUILD_NUMBER}"
    CONTAINER_NAME = 'my-php-site'
    HOST_PORT = '8085'
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

    stage('Clean up local image') {
      steps {
        script {
          echo "🧹 Cleaning local Docker image..."
          sh "docker rmi ${IMAGE_NAME}:${IMAGE_TAG} || true"
        }
      }
    }

    stage('Deploy') {
      steps {
        script {
          echo "🚀 Deploying latest container..."
          // Stop old container if it exists
          sh "docker stop ${CONTAINER_NAME} || true"
          sh "docker rm ${CONTAINER_NAME} || true"
          // Run new container with latest image
          sh "docker run -d --name ${CONTAINER_NAME} -p ${HOST_PORT}:80 ${IMAGE_NAME}:latest"
        }
      }
    }
  }

  post {
    success { echo "✅ Build, push, and deploy completed successfully!" }
    failure { echo "❌ Build or deploy failed. Check logs for details." }
  }
}
