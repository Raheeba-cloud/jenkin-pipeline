pipeline {
    agent any

    environment {
        DOCKERHUB_CREDENTIALS = credentials('dockerhub-cred') // Jenkins credential ID for Docker Hub
        IMAGE_NAME = "raheebamk/mysite"  // your Docker Hub repo
    }

    stages {

        stage('Checkout') {
            steps {
                echo "🔹 Stage: Checkout source code from Git"
                checkout([$class: 'GitSCM', branches: [[name: '*/main']],
                    userRemoteConfigs: [[url: 'https://github.com/Raheeba-cloud/jenkin-pipeline.git']]])
            }
        }

        stage('Agent pre-checks') {
            steps {
                echo "🔹 Stage: Agent pre-checks (docker, git)"
                sh '''
                    set -euo pipefail
                    echo "Checking required commands..."
                    command -v git
                    command -v docker || echo "⚠️ Docker not found, using docker-in-docker agent in next stage"
                '''
            }
        }

        stage('Build & Push Docker Image') {
            agent {
                docker {
                    image 'docker:latest'
                    args '-v /var/run/docker.sock:/var/run/docker.sock'
                }
            }
            steps {
                script {
                    def imageTag = "${env.BUILD_NUMBER}"
                    echo "✅ Building Docker image with tag: ${imageTag}"

                    sh """
                        docker build -t ${IMAGE_NAME}:${imageTag} .
                        echo "${DOCKERHUB_CREDENTIALS_PSW}" | docker login -u "${DOCKERHUB_CREDENTIALS_USR}" --password-stdin
                        docker push ${IMAGE_NAME}:${imageTag}
                    """
                }
            }
        }

        stage('Update Helm values and Push to Git (trigger ArgoCD)') {
            steps {
                echo "🔹 Stage: Update Helm values"
                sh '''
                    sed -i "s|tag:.*|tag: \\"${BUILD_NUMBER}\\"|g" charts/mysite/values.yaml
                    git config user.name "jenkins"
                    git config user.email "jenkins@local"
                    git add charts/mysite/values.yaml
                    git commit -m "ci: update image tag to ${BUILD_NUMBER}"
                    git push origin main
                '''
            }
        }
    }

    post {
        success {
            echo "✅ Pipeline completed successfully!"
        }
        failure {
            echo "❌ Pipeline failed — check console output"
        }
    }
}
