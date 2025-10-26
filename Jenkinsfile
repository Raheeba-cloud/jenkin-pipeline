pipeline {
    agent any

    environment {
        GIT_CRED = 'git'
        DOCKER_CRED = 'docker-hub-credentials'
        REPO_URL = 'https://github.com/Raheeba-cloud/your-repo-name.git'   // <-- change this
        IMAGE_NAME = 'raheebamk/myapp'   // <-- change to your DockerHub repo name
        IMAGE_TAG = 'latest'
    }

    stages {

        stage('Checkout Code') {
            steps {
                git branch: 'main',
                    credentialsId: "${GIT_CRED}",
                    url: "${REPO_URL}"
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    echo "🔨 Building Docker image..."
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
        success {
            echo "✅ Build and push completed successfully!"
        }
        failure {
            echo "❌ Build failed. Check logs for details."
        }
    }
}
