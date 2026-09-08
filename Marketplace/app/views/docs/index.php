<?php /** @var array $data */ ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($data['title'] ?? 'PasarKita API Docs') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body {
            margin: 0;
            background: #f8fafc;
        }

        .api-header {
            border-bottom: 1px solid #e2e8f0;
            background: #0f172a;
            color: #fff;
            padding: 18px 28px;
        }

        .api-header h1 {
            font-family: Arial, sans-serif;
            font-size: 22px;
            margin: 0;
        }

        .api-header p {
            color: #cbd5e1;
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 6px 0 0;
        }

        .api-header a {
            color: #86efac;
        }

        #swagger-ui {
            max-width: 1280px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <header class="api-header">
        <h1>PasarKita API Testing</h1>
        <p>Standalone Swagger UI. OpenAPI JSON: <a href="<?= BASEURL ?>docs/openapi"><?= BASEURL ?>docs/openapi</a></p>
    </header>

    <div id="swagger-ui"></div>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.addEventListener('load', function () {
            window.ui = SwaggerUIBundle({
                url: '<?= BASEURL ?>docs/openapi',
                dom_id: '#swagger-ui',
                deepLinking: true,
                requestInterceptor: function (request) {
                    request.credentials = 'include';
                    return request;
                },
                presets: [SwaggerUIBundle.presets.apis],
                layout: 'BaseLayout'
            });
        });
    </script>
</body>
</html>
