pipeline {
    agent any

    environment {
        DOCKERHUB_CREDENTIALS = credentials('dockerhub-cred')
        GIT_CREDENTIALS = credentials('git')
        IMAGE_NAME = "raheeba/my-php-site"
        IMAGE_TAG = "${BUILD_NUMBER}"
        CHART_PATH = "charts/mysite/values.yaml"
        GIT_REPO = "https://github.com/Raheeba-cloud/jenkin-pipeline.git"
    }

    stages {
        stage('Checkout — full clone') {
            steps {
                script {
                    echo "📥 Doing a full authenticated checkout (main)"
                    checkout([
                        $class: 'GitSCM',
                        branches: [[name: '*/main']],
                        userRemoteConfigs: [[
                            url: "${GIT_REPO}",
                            credentialsId: 'git'
                        ]]
                    ])
                    sh '''
                        echo "Workspace: $(pwd)"
                        echo "Files:"
                        ls -la
                        echo "Git remote:"
                        git remote -v
                        echo "Inside git worktree?"
                        git rev-parse --is-inside-work-tree
                    '''
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    def dockerImage = docker.build("${IMAGE_NAME}:${IMAGE_TAG}")
                    echo "🔨 Building Docker image ${IMAGE_NAME}:${IMAGE_TAG}"
                }
            }
        }

        stage('Push to Docker Hub') {
            steps {
                script {
                    echo "📤 Pushing image ${IMAGE_NAME}:${IMAGE_TAG} to Docker Hub"
                    docker.withRegistry('https://index.docker.io/v1/', 'dockerhub-cred') {
                        sh """
                            docker tag ${IMAGE_NAME}:${IMAGE_TAG} index.docker.io/${IMAGE_NAME}:${IMAGE_TAG}
                            docker push index.docker.io/${IMAGE_NAME}:${IMAGE_TAG}
                            docker tag ${IMAGE_NAME}:${IMAGE_TAG} index.docker.io/${IMAGE_NAME}:latest
                            docker push index.docker.io/${IMAGE_NAME}:latest
                        """
                    }
                }
            }
        }

        stage('Update Helm values.yaml') {
            steps {
                script {
                    echo "📝 Update Helm values.yaml with tag ${IMAGE_TAG}"
                    sh '''
                        set -eux
                        [ ! -f ${CHART_PATH} ] && echo "❌ Missing values.yaml" && exit 1
                        sed -i.bak -E "s/^(\\s*tag:\\s*).*/\\1${BUILD_NUMBER}/" ${CHART_PATH}
                        echo "---- updated ${CHART_PATH} ----"
                        cat ${CHART_PATH}
                        echo "------------------------------------------"
                    '''
                }
            }
        }

        stage('Commit & Push Helm update') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'git', usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
                    script {
                        echo "🔁 Commit & push Helm change"
                        sh '''
                            set -eux
                            git config user.name "Jenkins CI"
                            git config user.email "jenkins@local"
                            git add ${CHART_PATH}
                            git commit -m "Update Helm image tag to ${BUILD_NUMBER}" || echo "No changes to commit"
                            git push https://${GIT_USER}:${GIT_PASS}@github.com/Raheeba-cloud/jenkin-pipeline.git main
                        '''
                    }
                }
            }
        }

        stage('Trigger ArgoCD Sync') {
            steps {
                script {
                    echo "🚀 Triggering ArgoCD Sync for mysite"
                    sh '''
                        argocd app sync mysite --grpc-web || echo "⚠️ ArgoCD not configured"
                    '''
                }
            }
        }
    }

    post {
        success {
            echo "✅ Pipeline completed successfully!"
        }
        failure {
            echo "❌ Pipeline failed. Check logs above."
        }
    }
}
