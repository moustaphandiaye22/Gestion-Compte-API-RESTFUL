<!DOCTYPE html>
<html>
<head>
  <title>API Documentation</title>
  <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui.css" />
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui-bundle.js"></script>
  <script>
    const ui = SwaggerUIBundle({
      url: "{{ url('/api.json') }}",
      dom_id: '#swagger-ui',
      presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIBundle.presets.standalone
      ],
      requestInterceptor: (request) => {
        if (request.url.startsWith('http://')) {
          request.url = request.url.replace('http://', 'https://');
        }
        return request;
      }
    });
  </script>
</body>
</html>
