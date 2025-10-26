pipeline {
    agent any

    environment {
        DOCKERHUB_CREDS = "docker-hub-credentials"   // Jenkins credential ID for Docker Hub
        GIT_CREDS       = "git"      // Jenkins credential ID for GitHub
        IMAGE_REPO      = "raheeba/mysite"    // Docker Hub repo
        CHART_VALUES    = "charts/mysite/values.yaml"
        GIT_REMOTE_URL  = "https://github.com/Raheeba-cloud/jenkin-pipeline.git"
        GIT_BRANCH      = "main"
    }

    stages {

        stage('Checkout') {
            steps {
                echo "🔹 Stage: Checkout source code from Git"
                checkout scm
                script {
                    // Jenkins build number used as image tag
                    env.IMAGE_TAG = "${env.BUILD_NUMBER}"
                    echo "✅ Using image tag (build number): ${env.IMAGE_TAG}"
                }
            }
        }

        stage('Agent pre-checks') {
            steps {
                echo "🔹 Stage: Agent pre-checks (docker, git)"
                sh '''
                    set -euo pipefail
                    echo "Checking required commands..."
                    for cmd in docker git; do
                        if ! command -v $cmd >/dev/null 2>&1; then
                            echo "❌ Required command '$cmd' not found"
                            exit 127
                        fi
                    done
                    echo "✅ All required commands found"
                    echo "Docker version:"
                    docker --version || true
                '''
            }
        }

        stage('Build & Push Docker Image') {
            steps {
                echo "🔹 Stage: Build & Push Docker Image to DockerHub"
                withCredentials([usernamePassword(credentialsId: "${DOCKERHUB_CREDS}", usernameVariable: 'DH_USER', passwordVariable: 'DH_PASS')]) {
                    sh '''
                        set -euo pipefail
                        set -x
                        echo "Logging in to DockerHub..."
                        echo "$DH_PASS" | docker login -u "$DH_USER" --password-stdin

                        echo "Building Docker image..."
                        docker build --pull -t ${IMAGE_REPO}:latest -t ${IMAGE_REPO}:${IMAGE_TAG} .

                        echo "Pushing Docker image to DockerHub..."
                        docker push ${IMAGE_REPO}:latest
                        docker push ${IMAGE_REPO}:${IMAGE_TAG}

                        echo "Logging out from DockerHub..."
                        docker logout
                    '''
                }
            }
        }

        stage('Update Helm values and Push to Git (trigger ArgoCD)') {
            steps {
                echo "🔹 Stage: Update Helm chart values and push to Git"
                withCredentials([usernamePassword(credentialsId: "${GIT_CREDS}", usernameVariable: 'GH_USER', passwordVariable: 'GH_TOKEN')]) {
                    script {
                        def valsPath = "${env.WORKSPACE}/${CHART_VALUES}"
                        echo "Updating Helm values file: ${valsPath}"
                        def text = readFile(file: valsPath)
                        def repoLine = "  repository: \"${IMAGE_REPO}\""
                        def tagLine  = "  tag: \"${IMAGE_TAG}\""
                        if (text =~ /(?m)^image:\n(?:[ \t].*\n)*$/) {
                            text = text.replaceAll("(?ms)^image:\\n(?:\\s*repository:.*\\n)?(?:\\s*tag:.*\\n)?", "image:\\n${repoLine}\\n${tagLine}\\n")
                        } else {
                            text = "image:\\n${repoLine}\\n${tagLine}\\n\n" + text
                        }
                        writeFile(file: valsPath, text: text)
                        echo "✅ Helm values updated"

                        sh '''
                            set -euo pipefail
                            set -x
                            echo "Configuring git user..."
                            git config user.email "jenkins@ci"
                            git config user.name "jenkins-ci"

                            echo "Creating branch for CI update..."
                            git checkout -b ci/update-image-${IMAGE_TAG}

                            echo "Adding updated Helm values file..."
                            git add ${CHART_VALUES}

                            echo "Committing changes..."
                            git commit -m "ci: update chart image to ${IMAGE_REPO}:${IMAGE_TAG}" || true

                            echo "Pushing changes to Git..."
                            REMOTE="${GIT_REMOTE_URL}"
                            REMOTE_NO_PROTO=$(echo "$REMOTE" | sed 's#^https://##')
                            git push "https://${GH_USER}:${GH_TOKEN}@${REMOTE_NO_PROTO}" HEAD:${GIT_BRANCH}

                            echo "✅ Helm chart changes pushed to Git"
                        '''
                    }
                }
            }
        }

    }

    post {
        success {
            echo "🎉 Pipeline completed successfully. ArgoCD should detect and sync the change."
        }
        failure {
            echo "❌ Pipeline failed — check console output"
        }
    }
}
