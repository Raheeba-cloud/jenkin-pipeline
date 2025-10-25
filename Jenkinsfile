pipeline {
  agent any
  environment {
    # if Jenkins runs inside cluster and can reach argocd via cluster DNS, change this to the in-cluster service
    ARGO_SERVER = 'argocd-server.argocd.svc.cluster.local:443'
  }
  stages {
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    stage('Prepare CLIs') {
      steps {
        sh '''
        set -e
        # install argocd CLI if missing
        if ! command -v argocd >/dev/null 2>&1; then
          curl -sSL -o /tmp/argocd https://github.com/argoproj/argo-cd/releases/latest/download/argocd-linux-amd64
          chmod +x /tmp/argocd
          mkdir -p ~/.local/bin
          mv /tmp/argocd ~/.local/bin/argocd || true
          export PATH=$PATH:~/.local/bin
        fi

        # install helm if missing
        if ! command -v helm >/dev/null 2>&1; then
          curl -fsSL -o /tmp/get_helm.sh https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3
          chmod +x /tmp/get_helm.sh
          /tmp/get_helm.sh
        fi

        helm version --client || true
        argocd version --client || true
        '''
      }
    }

    stage('Lint Helm') {
      steps {
        sh '''
        # lint the Helm chart (non-fatal)
        helm lint charts/mysite || true
        '''
      }
    }

    stage('Trigger ArgoCD sync') {
      steps {
        withCredentials([string(credentialsId: 'ARGO_TOKEN', variable: 'ARGO_TOKEN')]) {
          sh '''
          set -e
          # login to argocd using token and sync the app
          # If ARGO_SERVER is not reachable from the Jenkins agent, replace with NODE_IP:NODEPORT (use the NodePort you used to access the UI)
          argocd login ${ARGO_SERVER} --insecure --auth-token ${ARGO_TOKEN} || true
          argocd app sync mysite
          argocd app wait mysite --health --timeout 120
          '''
        }
      }
    }
  }

  post {
    always {
      echo "Pipeline finished"
    }
  }
}
