{{- define "mysite.name" -}}
{{- .Chart.Name -}}
{{- end -}}

{{- define "mysite.fullname" -}}
{{- printf "%s-%s" .Release.Name .Chart.Name -}}
{{- end -}}
